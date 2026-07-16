<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Tests\Support;

use Bluehost\VerifactiApi\Support\SensitiveDataHelper;
use PHPUnit\Framework\TestCase;

final class SensitiveDataHelperTest extends TestCase
{
    public function testTruncateReturnsOriginalValueWhenWithinLimit(): void
    {
        $value = 'short payload';

        $this->assertSame($value, SensitiveDataHelper::truncate($value));
    }

    public function testTruncateAppendsEllipsisWhenExceedingLimit(): void
    {
        $value = str_repeat('a', 600);

        $truncated = SensitiveDataHelper::truncate($value, 512);

        $this->assertSame(512, strlen($truncated));
        $this->assertStringEndsWith('...', $truncated);
    }
}
