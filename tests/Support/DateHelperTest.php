<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Tests\Support;

use Bluehost\VerifactiApi\Support\DateHelper;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class DateHelperTest extends TestCase
{
    public function testValidApiDate(): void
    {
        self::assertTrue(DateHelper::isValidApiDate('08-10-2026'));
        self::assertFalse(DateHelper::isValidApiDate('2026-10-08'));
        self::assertFalse(DateHelper::isValidApiDate('31-02-2026'));
        self::assertFalse(DateHelper::isValidApiDate('8-10-2026'));
    }

    public function testIsTodayWithClock(): void
    {
        $clock = new DateTimeImmutable('2026-10-08 23:30:00');

        self::assertTrue(DateHelper::isToday('08-10-2026', $clock));
        self::assertFalse(DateHelper::isToday('09-10-2026', $clock));
        self::assertFalse(DateHelper::isToday('not-a-date', $clock));
    }

    public function testTodayInSpainIsAcceptedWithoutClock(): void
    {
        self::assertTrue(DateHelper::isToday(DateHelper::todayInSpain()));
        self::assertTrue(DateHelper::isToday((new DateTimeImmutable('now'))->format('d-m-Y')));
        self::assertSame(
            (new DateTimeImmutable('now', new DateTimeZone('Europe/Madrid')))->format('d-m-Y'),
            DateHelper::todayInSpain()
        );
    }
}
