<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Support;

/**
 * Utilities for reducing sensitive data exposure in logs and exception messages.
 */
final class SensitiveDataHelper
{
    private const DEFAULT_MAX_LENGTH = 512;

    /**
     * Truncate a string to a maximum length, appending an ellipsis when truncated.
     *
     * @param string $value    The value to truncate.
     * @param int    $maxLength Maximum number of characters to retain.
     *
     * @return string
     */
    public static function truncate(string $value, int $maxLength = self::DEFAULT_MAX_LENGTH): string
    {
        if ($maxLength <= 0) {
            return '';
        }

        if (strlen($value) <= $maxLength) {
            return $value;
        }

        if ($maxLength <= 3) {
            return substr($value, 0, $maxLength);
        }

        return substr($value, 0, $maxLength - 3) . '...';
    }

    /**
     * Mask credentials that may appear in free text (error messages, bodies).
     *
     * Masks `Bearer <token>` values and `api_key`/`apikey`/`token` assignments.
     *
     * @param string $value Free text.
     *
     * @return string
     */
    public static function redact(string $value): string
    {
        $value = (string) preg_replace('/(Bearer\s+)[A-Za-z0-9._~+\/=-]+/i', '$1[REDACTED]', $value);

        return (string) preg_replace(
            '/("?(?:api[_-]?key|token|authorization)"?\s*[:=]\s*"?)[^"\s,}]+/i',
            '$1[REDACTED]',
            $value
        );
    }

    /**
     * Redact and truncate a value so it is safe to place in an exception message or log.
     *
     * @param string $value     Free text.
     * @param int    $maxLength Maximum length.
     *
     * @return string
     */
    public static function sanitizeForMessage(string $value, int $maxLength = self::DEFAULT_MAX_LENGTH): string
    {
        return self::truncate(self::redact($value), $maxLength);
    }
}
