<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Tests\Support;

use Bluehost\VerifactiApi\Config\AuthenticationConfig;
use Bluehost\VerifactiApi\Exception\ConfigurationException;
use Bluehost\VerifactiApi\Exception\ValidationException;
use Bluehost\VerifactiApi\Support\HttpHeaderHelper;
use PHPUnit\Framework\TestCase;

final class HttpHeaderHelperTest extends TestCase
{
    public function testSanitizeRemovesUnsafeCharacters(): void
    {
        $this->assertSame(
            'Bearer secret-keyX-Evil: injected',
            HttpHeaderHelper::sanitize("Bearer secret-key\r\nX-Evil: injected")
        );
    }

    public function testAuthenticationConfigRejectsApiKeyWithCrlf(): void
    {
        $this->expectException(ConfigurationException::class);

        new AuthenticationConfig("test-key\r\nX-Evil: injected");
    }

    public function testAuthenticationConfigUsesBearerAuthorizationHeader(): void
    {
        $authentication = new AuthenticationConfig('test-key');

        $this->assertSame('Authorization', AuthenticationConfig::HEADER_NAME);
        $this->assertSame('Bearer', AuthenticationConfig::TOKEN_PREFIX);
        $this->assertSame('Bearer test-key', $authentication->formatHeaderValue());
    }

    public function testAssertSafeRequestHeaderValueThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);

        HttpHeaderHelper::assertSafeRequestHeaderValue("order-1\r\nX-Evil: injected", 'Idempotency-Key');
    }
}
