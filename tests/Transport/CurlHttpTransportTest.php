<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Tests\Transport;

use PHPUnit\Framework\TestCase;

final class CurlHttpTransportTest extends TestCase
{
    public function testTransportSourceConfiguresSslVerificationOptions(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/src/Transport/CurlHttpTransport.php'
        );

        $this->assertStringContainsString('CURLOPT_SSL_VERIFYPEER', $source);
        $this->assertStringContainsString('CURLOPT_SSL_VERIFYHOST', $source);
        $this->assertStringContainsString('CURLOPT_FOLLOWLOCATION', $source);
    }
}
