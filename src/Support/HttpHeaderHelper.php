<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Support;

use Bluehost\VerifactiApi\Exception\ConfigurationException;
use Bluehost\VerifactiApi\Exception\ValidationException;

final class HttpHeaderHelper
{
    public static function containsUnsafeCharacters(string $value): bool
    {
        return preg_match('/[\r\n\0]/', $value) === 1;
    }

    public static function sanitize(string $value): string
    {
        return str_replace(array("\r", "\n", "\0"), '', $value);
    }

    public static function assertValidHeaderName(string $name, string $field = 'authentication header name'): void
    {
        if (self::containsUnsafeCharacters($name)) {
            throw new ConfigurationException(
                sprintf('The %s cannot contain carriage return, newline, or null bytes.', $field)
            );
        }

        if (!preg_match('/^[A-Za-z0-9-]+$/', $name)) {
            throw new ConfigurationException(
                sprintf('The %s contains invalid characters.', $field)
            );
        }
    }

    public static function assertSafeConfigurationValue(string $value, string $field): void
    {
        if (self::containsUnsafeCharacters($value)) {
            throw new ConfigurationException(
                sprintf('"%s" cannot contain carriage return, newline, or null bytes.', $field)
            );
        }
    }

    public static function assertSafeRequestHeaderValue(string $value, string $field): void
    {
        if (self::containsUnsafeCharacters($value)) {
            throw new ValidationException(
                sprintf('"%s" cannot contain carriage return, newline, or null bytes.', $field)
            );
        }
    }
}
