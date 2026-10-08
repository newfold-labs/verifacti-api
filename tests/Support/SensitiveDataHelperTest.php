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

    public function testRedactMasksBearerTokensAndKeys(): void
    {
        $value = 'Authorization: Bearer vf_live_abc.DEF-123 {"api_key":"secret1","token": "t0k"} apikey=zzz';

        $redacted = SensitiveDataHelper::redact($value);

        $this->assertStringNotContainsString('vf_live_abc', $redacted);
        $this->assertStringNotContainsString('secret1', $redacted);
        $this->assertStringNotContainsString('t0k', $redacted);
        $this->assertStringNotContainsString('zzz', $redacted);
        $this->assertStringContainsString('[REDACTED]', $redacted);
    }

    public function testSanitizeForMessageRedactsThenTruncates(): void
    {
        $value = 'Bearer secret ' . str_repeat('b', 600);

        $sanitized = SensitiveDataHelper::sanitizeForMessage($value, 100);

        $this->assertSame(100, strlen($sanitized));
        $this->assertStringStartsWith('Bearer [REDACTED]', $sanitized);
    }
}
