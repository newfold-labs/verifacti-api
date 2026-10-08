<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Tests\Double;

use Bluehost\VerifactiApi\Config\VerifactiConfig;
use Bluehost\VerifactiApi\Exception\TransportException;
use Bluehost\VerifactiApi\Transport\HttpRequest;
use Bluehost\VerifactiApi\Transport\HttpResponse;
use Bluehost\VerifactiApi\Transport\HttpTransportInterface;

/**
 * Scripted transport: returns queued responses (or throws queued exceptions)
 * and records every request it receives.
 */
final class FakeTransport implements HttpTransportInterface
{
    /**
     * @var array<int, HttpResponse|TransportException>
     */
    private array $queue = [];

    /**
     * @var array<int, HttpRequest>
     */
    public array $requests = [];

    /**
     * Queue a JSON response.
     *
     * @param int                  $status  HTTP status.
     * @param array<mixed>|string  $body    Body (arrays are JSON-encoded).
     * @param array<string,string> $headers Response headers.
     */
    public function queue(int $status, array|string $body = [], array $headers = []): self
    {
        $raw = is_array($body) ? json_encode($body, JSON_THROW_ON_ERROR) : $body;
        $headers += is_array($body) ? ['content-type' => 'application/json'] : [];
        $this->queue[] = new HttpResponse($status, $headers, $raw);

        return $this;
    }

    /**
     * Queue a transport failure (e.g. a timeout).
     */
    public function queueFailure(string $message = 'Operation timed out after 30001 milliseconds'): self
    {
        $this->queue[] = new TransportException($message);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function send(HttpRequest $request, VerifactiConfig $config): HttpResponse
    {
        $this->requests[] = $request;
        $next = array_shift($this->queue);

        if ($next === null) {
            throw new \LogicException('FakeTransport: no response queued.');
        }

        if ($next instanceof TransportException) {
            throw $next;
        }

        return $next;
    }
}
