<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Config;

use Bluehost\VerifactiApi\Exception\ConfigurationException;
use Bluehost\VerifactiApi\Support\HttpHeaderHelper;

final class AuthenticationConfig
{
    public const HEADER_NAME = 'Authorization';
    public const TOKEN_PREFIX = 'Bearer';

    private string $apiKey;

    public function __construct(string $apiKey)
    {
        if (trim($apiKey) === '') {
            throw new ConfigurationException('The API key cannot be empty.');
        }

        $this->apiKey = trim($apiKey);

        HttpHeaderHelper::assertSafeConfigurationValue($this->apiKey, 'api_key');
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function formatHeaderValue(): string
    {
        return self::TOKEN_PREFIX . ' ' . $this->apiKey;
    }
}
