<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Tests\Config;

use Bluehost\VerifactiApi\Config\ConfigFactory;
use Bluehost\VerifactiApi\Exception\ConfigurationException;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = ConfigFactory::test('key');

        self::assertSame(30, $config->getTimeoutSeconds());
        self::assertSame(2, $config->getMaxRetries());
        self::assertSame('https://api.verifacti.com', $config->getBaseUrl());
        self::assertSame('test', $config->getEnvironment());
    }

    public function testMaxRetriesOption(): void
    {
        self::assertSame(0, ConfigFactory::production('key', ['max_retries' => 0])->getMaxRetries());
    }

    public function testMaxRetriesIsBounded(): void
    {
        $this->expectException(ConfigurationException::class);
        ConfigFactory::fromArray(['api_key' => 'key', 'max_retries' => 6]);
    }

    public function testTimeoutMustBePositive(): void
    {
        $this->expectException(ConfigurationException::class);
        ConfigFactory::fromArray(['api_key' => 'key', 'timeout' => 0]);
    }
}
