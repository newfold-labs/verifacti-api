<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Client;

use Bluehost\VerifactiApi\Config\ConfigFactory;
use Bluehost\VerifactiApi\Config\VerifactiConfig;
use Bluehost\VerifactiApi\Transport\CurlHttpTransport;
use Bluehost\VerifactiApi\Transport\HttpTransportInterface;

final class ClientFactory
{
    /**
     * @param array<string, mixed> $options
     */
    public static function fromApiKey(string $apiKey, array $options = array(), ?HttpTransportInterface $transport = null): VerifactiClient
    {
        return self::fromConfig(
            ConfigFactory::fromArray(array_merge(array('api_key' => $apiKey), $options)),
            $transport
        );
    }

    public static function fromConfig(VerifactiConfig $config, ?HttpTransportInterface $transport = null): VerifactiClient
    {
        return new VerifactiClient($config, $transport ?: new CurlHttpTransport());
    }
}
