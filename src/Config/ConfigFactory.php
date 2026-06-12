<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Config;

final class ConfigFactory
{
    /**
     * @param array<string, mixed> $options
     */
    public static function production(string $apiKey, array $options = array()): VerifactiConfig
    {
        $options['environment'] = Environment::PRODUCTION;

        return self::fromArray(array_merge(array('api_key' => $apiKey), $options));
    }

    /**
     * @param array<string, mixed> $options
     */
    public static function test(string $apiKey, array $options = array()): VerifactiConfig
    {
        $options['environment'] = Environment::TEST;

        return self::fromArray(array_merge(array('api_key' => $apiKey), $options));
    }

    /**
     * @param array<string, mixed> $options
     */
    public static function fromArray(array $options): VerifactiConfig
    {
        $timeout = isset($options['timeout']) ? (int) $options['timeout'] : 30;
        $environment = isset($options['environment']) ? (string) $options['environment'] : Environment::CUSTOM;
        $apiKey = isset($options['api_key']) ? (string) $options['api_key'] : '';

        return new VerifactiConfig(
            new AuthenticationConfig($apiKey),
            $timeout,
            $environment
        );
    }
}
