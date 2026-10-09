<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Support;

use InvalidArgumentException;

/**
 * Decimal helpers for Verifacti amount fields.
 *
 * Amounts are sent as JSON strings with at most 2 decimals and `.` as the
 * separator, matching the pattern `(\+|-)?\d{1,12}(\.\d{0,2})?`
 * (Verifacti OpenAPI spec, `base_imponible` / `importe_total`). All
 * arithmetic is done on integer cents to avoid float drift.
 */
final class Amount
{
    public const AMOUNT_PATTERN = '/^[+-]?\d{1,12}(\.\d{0,2})?$/';

    /**
     * Pattern of `tipo_impositivo` (non-negative, up to 3 integer digits).
     */
    public const RATE_PATTERN = '/^\d{1,3}(\.\d{0,2})?$/';

    /**
     * Prevent instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Whether a string is a valid API amount.
     *
     * @param string $value Candidate amount.
     *
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return preg_match(self::AMOUNT_PATTERN, $value) === 1;
    }

    /**
     * Whether a string is a valid API tax rate.
     *
     * @param string $value Candidate rate.
     *
     * @return bool
     */
    public static function isValidRate(string $value): bool
    {
        return preg_match(self::RATE_PATTERN, $value) === 1;
    }

    /**
     * Convert a valid API amount string to integer cents without using floats.
     *
     * @param string $value Amount string.
     *
     * @return int
     *
     * @throws InvalidArgumentException When the value is not a valid amount.
     */
    public static function toCents(string $value): int
    {
        if (!self::isValid($value)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid amount.', $value));
        }

        $negative = $value[0] === '-';
        $unsigned = ltrim($value, '+-');
        $parts = explode('.', $unsigned, 2);
        $fraction = str_pad($parts[1] ?? '', 2, '0');
        $cents = ((int) $parts[0]) * 100 + (int) $fraction;

        return $negative ? -$cents : $cents;
    }

    /**
     * Format integer cents as an API amount string with exactly 2 decimals.
     *
     * @param int $cents Amount in cents.
     *
     * @return string
     */
    public static function fromCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $abs = abs($cents);

        return sprintf('%s%d.%02d', $sign, intdiv($abs, 100), $abs % 100);
    }

    /**
     * Round a number (half away from zero) to cents and format it.
     *
     * @param float|int|string $value Numeric value.
     *
     * @return string
     *
     * @throws InvalidArgumentException When the value is not numeric.
     */
    public static function format(float|int|string $value): string
    {
        return self::fromCents(self::roundToCents($value));
    }

    /**
     * Round a number (half away from zero) to integer cents.
     *
     * @param float|int|string $value Numeric value.
     *
     * @return int
     *
     * @throws InvalidArgumentException When the value is not numeric.
     */
    public static function roundToCents(float|int|string $value): int
    {
        if (is_string($value)) {
            if (self::isValid($value)) {
                return self::toCents($value);
            }

            if (!is_numeric($value)) {
                throw new InvalidArgumentException(sprintf('"%s" is not numeric.', $value));
            }
        }

        // round() to 2 decimals first: PHP pre-rounding turns 1.005 into 1.01,
        // whereas 1.005 * 100 is 100.4999... in binary floating point.
        return (int) round(round((float) $value, 2, PHP_ROUND_HALF_UP) * 100);
    }

    /**
     * Format a tax rate: up to 2 decimals, trailing zeros removed ("21", "7.5").
     *
     * @param float|int|string $rate Tax rate percentage.
     *
     * @return string
     *
     * @throws InvalidArgumentException When the rate is negative or not numeric.
     */
    public static function formatRate(float|int|string $rate): string
    {
        if (!is_numeric($rate) || (float) $rate < 0) {
            throw new InvalidArgumentException('The tax rate must be a non-negative number.');
        }

        $formatted = number_format(round((float) $rate, 2), 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    /**
     * Sum valid API amounts exactly.
     *
     * @param string ...$values Amount strings.
     *
     * @return string
     */
    public static function sum(string ...$values): string
    {
        $total = 0;
        foreach ($values as $value) {
            $total += self::toCents($value);
        }

        return self::fromCents($total);
    }

    /**
     * Expected quota in cents for a base and a rate (half away from zero).
     *
     * @param string $base Taxable base.
     * @param string $rate Tax rate percentage.
     *
     * @return int
     */
    public static function expectedQuotaCents(string $base, string $rate): int
    {
        return (int) round(self::toCents($base) * ((float) $rate) / 100, 0, PHP_ROUND_HALF_UP);
    }
}
