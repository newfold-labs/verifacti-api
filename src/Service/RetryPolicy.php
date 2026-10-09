<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Service;

use Closure;

/**
 * Bounded retry policy with exponential backoff for transient API failures.
 *
 * A request is retried only when repeating it cannot create a duplicate
 * record: GET requests, or requests carrying an `Idempotency-Key` header
 * (the API replays the stored response for 24 h; see Verifacti docs,
 * "Idempotency"). Retryable outcomes are transport failures (timeouts,
 * connection errors) and HTTP 409 (same key still in progress), 429, 500,
 * 502, 503 and 504.
 */
final class RetryPolicy
{
    private const RETRYABLE_STATUS_CODES = [409, 429, 500, 502, 503, 504];

    private Closure $sleeper;

    /**
     * @param int           $maxRetries  Extra attempts after the first one (0 disables retries).
     * @param int           $baseDelayMs Delay before the first retry; doubled on each retry.
     * @param int           $maxDelayMs  Upper bound for any single delay, including `Retry-After`.
     * @param callable|null $sleeper     Receives the delay in milliseconds (tests inject a no-op).
     */
    public function __construct(
        private int $maxRetries = 2,
        private int $baseDelayMs = 500,
        private int $maxDelayMs = 4000,
        ?callable $sleeper = null
    ) {
        $this->maxRetries = max(0, min($this->maxRetries, 5));
        $this->baseDelayMs = max(0, $this->baseDelayMs);
        $this->maxDelayMs = max($this->baseDelayMs, $this->maxDelayMs);
        $this->sleeper = $sleeper !== null
            ? Closure::fromCallable($sleeper)
            : static function (int $milliseconds): void {
                usleep($milliseconds * 1000);
            };
    }

    /**
     * Policy that never retries.
     *
     * @return self
     */
    public static function none(): self
    {
        return new self(0);
    }

    /**
     * Return the maximum number of retries.
     *
     * @return int
     */
    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    /**
     * Whether a request may be retried at all.
     *
     * @param string                $method  HTTP method.
     * @param array<string, string> $headers Request headers.
     *
     * @return bool
     */
    public function isRetrySafe(string $method, array $headers): bool
    {
        if (strtoupper($method) === 'GET') {
            return true;
        }

        foreach ($headers as $name => $value) {
            if (strtolower((string) $name) === 'idempotency-key' && trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether an HTTP status code is transient.
     *
     * @param int $statusCode HTTP status code.
     *
     * @return bool
     */
    public function isRetryableStatus(int $statusCode): bool
    {
        return in_array($statusCode, self::RETRYABLE_STATUS_CODES, true);
    }

    /**
     * Compute the delay before retry number `$retry` (1-based).
     *
     * @param int         $retry      Retry number, starting at 1.
     * @param string|null $retryAfter Value of the `Retry-After` header (seconds), if any.
     *
     * @return int Delay in milliseconds.
     */
    public function delayFor(int $retry, ?string $retryAfter = null): int
    {
        if ($retryAfter !== null && ctype_digit(trim($retryAfter))) {
            return min((int) trim($retryAfter) * 1000, $this->maxDelayMs);
        }

        return min($this->baseDelayMs * (2 ** max(0, $retry - 1)), $this->maxDelayMs);
    }

    /**
     * Wait before the next attempt.
     *
     * @param int $milliseconds Delay in milliseconds.
     *
     * @return void
     */
    public function wait(int $milliseconds): void
    {
        if ($milliseconds > 0) {
            ($this->sleeper)($milliseconds);
        }
    }
}
