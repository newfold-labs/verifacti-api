<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Support;

use DateTimeImmutable;
use DateTimeInterface;

final class DateHelper
{
    public const API_DATE_FORMAT = 'd-m-Y';

    public static function isValidApiDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat(self::API_DATE_FORMAT, $value);

        return $date instanceof DateTimeImmutable && $date->format(self::API_DATE_FORMAT) === $value;
    }

    public static function isToday(string $value, ?DateTimeInterface $clock = null): bool
    {
        if (!self::isValidApiDate($value)) {
            return false;
        }

        $now = $clock ?: new DateTimeImmutable('now');

        return $now->format(self::API_DATE_FORMAT) === $value;
    }
}
