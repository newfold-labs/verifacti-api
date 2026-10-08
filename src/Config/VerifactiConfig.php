<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Config;

use Bluehost\VerifactiApi\Exception\ConfigurationException;

/**
 * Runtime configuration for the Verifacti API client.
 */
final class VerifactiConfig
{
    public const BASE_URL = 'https://api.verifacti.com';

    private AuthenticationConfig $authentication;
    private int $timeoutSeconds;
    private string $environment;
    private int $maxRetries;

    /**
     * @param AuthenticationConfig $authentication API authentication settings.
     * @param int                  $timeoutSeconds HTTP request timeout in seconds.
     * @param string               $environment    Environment identifier.
     *
     * @throws ConfigurationException When timeout or environment values are invalid.
     */
    public function __construct(
        AuthenticationConfig $authentication,
        int $timeoutSeconds = 30,
        string $environment = Environment::CUSTOM,
        int $maxRetries = 2
    ) {
        if ($maxRetries < 0 || $maxRetries > 5) {
            throw new ConfigurationException('max_retries must be between 0 and 5.');
        }

        if ($timeoutSeconds <= 0) {
            throw new ConfigurationException('The timeout must be greater than zero.');
        }

        if (!Environment::isValid($environment)) {
            throw new ConfigurationException(sprintf('Unsupported environment "%s".', $environment));
        }

        $this->authentication = $authentication;
        $this->timeoutSeconds = $timeoutSeconds;
        $this->environment = $environment;
        $this->maxRetries = $maxRetries;
    }

    /**
     * Return how many times a retry-safe request is retried on transient failures.
     *
     * @return int
     */
    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    /**
     * Return the fixed Verifacti API base URL.
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return self::BASE_URL;
    }

    /**
     * Return the authentication configuration.
     *
     * @return AuthenticationConfig
     */
    public function getAuthentication(): AuthenticationConfig
    {
        return $this->authentication;
    }

    /**
     * Return the HTTP request timeout in seconds.
     *
     * @return int
     */
    public function getTimeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }

    /**
     * Return the configured environment identifier.
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Return default HTTP headers applied to every API request.
     *
     * @return array<string, string>
     */
    public function getDefaultHeaders(): array
    {
        return [
            AuthenticationConfig::HEADER_NAME => $this->authentication->formatHeaderValue(),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
