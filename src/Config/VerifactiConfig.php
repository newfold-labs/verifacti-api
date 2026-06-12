<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Config;

use Bluehost\VerifactiApi\Exception\ConfigurationException;

final class VerifactiConfig
{
    public const BASE_URL = 'https://api.verifacti.com';

    private AuthenticationConfig $authentication;
    private int $timeoutSeconds;
    private string $environment;

    public function __construct(
        AuthenticationConfig $authentication,
        int $timeoutSeconds = 30,
        string $environment = Environment::CUSTOM
    ) {
        if ($timeoutSeconds <= 0) {
            throw new ConfigurationException('The timeout must be greater than zero.');
        }

        if (!Environment::isValid($environment)) {
            throw new ConfigurationException(sprintf('Unsupported environment "%s".', $environment));
        }

        $this->authentication = $authentication;
        $this->timeoutSeconds = $timeoutSeconds;
        $this->environment = $environment;
    }

    public function getBaseUrl(): string
    {
        return self::BASE_URL;
    }

    public function getAuthentication(): AuthenticationConfig
    {
        return $this->authentication;
    }

    public function getTimeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * @return array<string, string>
     */
    public function getDefaultHeaders(): array
    {
        return array(
            AuthenticationConfig::HEADER_NAME => $this->authentication->formatHeaderValue(),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        );
    }
}
