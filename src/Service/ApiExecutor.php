<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Service;

use Bluehost\VerifactiApi\Config\VerifactiConfig;
use Bluehost\VerifactiApi\Dto\ApiResponse;
use Bluehost\VerifactiApi\Exception\ApiException;
use Bluehost\VerifactiApi\Exception\AuthenticationException;
use Bluehost\VerifactiApi\Exception\HttpException;
use Bluehost\VerifactiApi\Exception\SerializationException;
use Bluehost\VerifactiApi\Serializer\JsonSerializer;
use Bluehost\VerifactiApi\Support\ResponseAccessor;
use Bluehost\VerifactiApi\Transport\HttpRequest;
use Bluehost\VerifactiApi\Transport\HttpResponse;
use Bluehost\VerifactiApi\Transport\HttpTransportInterface;

final class ApiExecutor
{
    private VerifactiConfig $config;
    private HttpTransportInterface $transport;
    private JsonSerializer $serializer;

    public function __construct(VerifactiConfig $config, HttpTransportInterface $transport, JsonSerializer $serializer)
    {
        $this->config = $config;
        $this->transport = $transport;
        $this->serializer = $serializer;
    }

    /**
     * @param array<string, string> $query
     */
    public function get(string $path, array $query = array()): ApiResponse
    {
        return $this->request('GET', $path, null, $query);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     */
    public function post(string $path, array $payload = array(), array $headers = array()): ApiResponse
    {

        return $this->request('POST', $path, $payload, array(), $headers);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     */
    public function put(string $path, array $payload = array(), array $headers = array()): ApiResponse
    {
        return $this->request('PUT', $path, $payload, array(), $headers);
    }

    /**
     * @param array<string, mixed>|null $payload
     * @param array<string, string> $query
     * @param array<string, string> $headers
     */
    public function request(
        string $method,
        string $path,
        ?array $payload = null,
        array $query = array(),
        array $headers = array()
    ): ApiResponse {
        $requestHeaders = array_merge($this->config->getDefaultHeaders(), $headers);
        $body = $payload !== null ? $this->serializer->encode($payload) : null;

        if ($payload === null) {
            unset($requestHeaders['Content-Type']);
        }

        $response = $this->transport->send(
            new HttpRequest($method, $path, $requestHeaders, $query, $body, $this->config->getTimeoutSeconds()),
            $this->config
        );

        if (!$response->isSuccessful()) {
            $this->throwForErrorResponse($response);
        }

        $data = $this->decodeSuccessBody($response);

        return new ApiResponse(
            $response->getStatusCode(),
            $data,
            $response->getBody(),
            $response->getHeaders(),
            $response->getMetadata()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeSuccessBody(HttpResponse $response): array
    {
        $body = $response->getBody();

        if (trim($body) === '') {
            return array();
        }

        if ($this->looksLikeJson($response)) {
            return $this->serializer->decode($body);
        }

        return array('_raw' => $body);
    }

    private function throwForErrorResponse(HttpResponse $response): void
    {
        $body = $response->getBody();
        $headers = $response->getHeaders();
        $statusCode = $response->getStatusCode();

        if (!$this->looksLikeJson($response)) {
            $message = trim($body) !== '' ? trim($body) : sprintf('HTTP %d returned by the Verifacti API.', $statusCode);

            if ($statusCode === 401 || $statusCode === 403) {
                throw new AuthenticationException($message, $statusCode, $headers, $body);
            }

            throw new HttpException($message, $statusCode, $headers, $body);
        }

        try {
            $errorData = $this->serializer->decode($body);
        } catch (SerializationException $exception) {
            throw new HttpException(
                'The Verifacti API returned an invalid JSON error response.',
                $statusCode,
                $headers,
                $body,
                0,
                $exception
            );
        }

        $messageValue = ResponseAccessor::first(
            $errorData,
            array('message', 'error', 'detail', 'details.message'),
            sprintf('HTTP %d returned by the Verifacti API.', $statusCode)
        );

        $message = is_scalar($messageValue) ? (string) $messageValue : sprintf('HTTP %d returned by the Verifacti API.', $statusCode);

        if ($statusCode === 401 || $statusCode === 403) {
            throw new AuthenticationException($message, $statusCode, $headers, $body);
        }

        throw new ApiException($message, $statusCode, $headers, $body, $errorData);
    }

    private function looksLikeJson(HttpResponse $response): bool
    {
        $contentType = strtolower($response->getContentType());
        if (strpos($contentType, 'application/json') !== false) {
            return true;
        }

        $body = ltrim($response->getBody());

        return $body !== '' && ($body[0] === '{' || $body[0] === '[');
    }
}
