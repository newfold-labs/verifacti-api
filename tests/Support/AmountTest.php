<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Tests\Support;

use Bluehost\VerifactiApi\Support\Amount;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AmountTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public function amounts(): array
    {
        return [
            'integer' => ['200', true],
            'two decimals' => ['12.34', true],
            'one decimal' => ['12.3', true],
            'trailing dot' => ['12.', true],
            'negative' => ['-605', true],
            'explicit plus' => ['+1.00', true],
            'three decimals' => ['1.005', false],
            'comma' => ['1,00', false],
            'thirteen digits' => ['1234567890123', false],
            'empty' => ['', false],
            'text' => ['abc', false],
        ];
    }

    /**
     * @dataProvider amounts
     */
    public function testIsValid(string $value, bool $expected): void
    {
        self::assertSame($expected, Amount::isValid($value));
    }

    public function testCentsRoundTrip(): void
    {
        self::assertSame(1234, Amount::toCents('12.34'));
        self::assertSame(1230, Amount::toCents('12.3'));
        self::assertSame(-60500, Amount::toCents('-605'));
        self::assertSame(1200, Amount::toCents('12.'));
        self::assertSame('12.34', Amount::fromCents(1234));
        self::assertSame('-0.05', Amount::fromCents(-5));
        self::assertSame('0.00', Amount::fromCents(0));
    }

    public function testToCentsRejectsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Amount::toCents('1.005');
    }

    public function testFormatRoundsHalfAwayFromZero(): void
    {
        self::assertSame('1.01', Amount::format(1.005));
        self::assertSame('-1.01', Amount::format(-1.005));
        self::assertSame('0.30', Amount::format(0.1 + 0.2));
        self::assertSame('2.68', Amount::format('2.675'));
        self::assertSame('42.00', Amount::format(42));
        self::assertSame('12.30', Amount::format('12.3'));
    }

    public function testFormatRejectsNonNumeric(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Amount::format('twelve');
    }

    public function testFormatRate(): void
    {
        self::assertSame('21', Amount::formatRate(21.0));
        self::assertSame('7.5', Amount::formatRate('7.5000'));
        self::assertSame('0', Amount::formatRate(0));
        self::assertSame('9.5', Amount::formatRate(9.5));
    }

    public function testFormatRateRejectsNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Amount::formatRate(-1);
    }

    public function testSumIsExact(): void
    {
        $values = array_fill(0, 10, '0.10');

        self::assertSame('1.00', Amount::sum(...$values));
        self::assertSame('242.00', Amount::sum('200', '42'));
        self::assertSame('-605.00', Amount::sum('-500', '-105'));
    }

    public function testExpectedQuota(): void
    {
        self::assertSame(4200, Amount::expectedQuotaCents('200', '21'));
        self::assertSame(-10500, Amount::expectedQuotaCents('-500', '21'));
        self::assertSame(1, Amount::expectedQuotaCents('0.05', '10'));
        self::assertTrue(Amount::isValidRate('7.5'));
        self::assertFalse(Amount::isValidRate('-7'));
        self::assertFalse(Amount::isValidRate('1000'));
    }
}
