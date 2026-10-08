<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Date parsing and validation helpers for Verifacti API payloads.
 */
final class DateHelper
{
    public const API_DATE_FORMAT = 'd-m-Y';

    public const SPAIN_TIMEZONE = 'Europe/Madrid';

    /**
     * Today's date in Spain in API format, the value expected for fecha_expedicion.
     *
     * @return string
     */
    public static function todayInSpain(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone(self::SPAIN_TIMEZONE)))->format(self::API_DATE_FORMAT);
    }

    /**
     * Determine whether a value matches the Verifacti API date format.
     *
     * @param string $value Date string candidate.
     *
     * @return bool
     */
    public static function isValidApiDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat(self::API_DATE_FORMAT, $value);

        return $date instanceof DateTimeImmutable && $date->format(self::API_DATE_FORMAT) === $value;
    }

    /**
     * Determine whether a value represents today's date in API format.
     *
     * @param string                 $value Date string candidate.
     * @param DateTimeInterface|null $clock Optional clock for testing.
     *
     * @return bool
     */
    public static function isToday(string $value, ?DateTimeInterface $clock = null): bool
    {
        if (!self::isValidApiDate($value)) {
            return false;
        }

        if ($clock !== null) {
            return $clock->format(self::API_DATE_FORMAT) === $value;
        }

        // Verifacti rejects records whose fecha_expedicion is not the current date.
        // The reference timezone is not documented (UNVERIFIED): accept today's date
        // both in the server timezone and in Spain, so a server running in UTC does
        // not reject a valid Spanish date around midnight.
        foreach ([null, self::SPAIN_TIMEZONE] as $timezone) {
            $now = new DateTimeImmutable('now', $timezone !== null ? new DateTimeZone($timezone) : null);

            if ($now->format(self::API_DATE_FORMAT) === $value) {
                return true;
            }
        }

        return false;
    }
}
