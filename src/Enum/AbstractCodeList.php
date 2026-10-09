<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Enum;

/**
 * Base class for closed lists of Verifacti API codes.
 *
 * PHP 8.0 has no native enums, so each list is a final class of string
 * constants. Subclasses implement {@see values()}.
 */
abstract class AbstractCodeList
{
    /**
     * Prevent instantiation.
     */
    final private function __construct()
    {
    }

    /**
     * Return every accepted code.
     *
     * @return array<int, string>
     */
    abstract public static function values(): array;

    /**
     * Determine whether a value is an accepted code.
     *
     * @param string|null $value Candidate value.
     *
     * @return bool
     */
    public static function isValid(?string $value): bool
    {
        return $value !== null && in_array($value, static::values(), true);
    }
}
