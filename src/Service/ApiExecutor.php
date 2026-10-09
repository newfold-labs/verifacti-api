<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Service;

use Bluehost\VerifactiApi\Config\VerifactiConfig;
use Bluehost\VerifactiApi\Dto\ApiResponse;
use Bluehost\VerifactiApi\Exception\ApiException;
use Bluehost\VerifactiApi\Exception\AuthenticationException;
use Bluehost\VerifactiApi\Exception\HttpException;
use Bluehost\VerifactiApi\Exception\SerializationException;
use Bluehost\VerifactiApi\Exception\TransportException;
use Bluehost\VerifactiApi\Serializer\JsonSerializer;
use Bluehost\VerifactiApi\Support\ResponseAccessor;
use Bluehost\VerifactiApi\Support\SensitiveDataHelper;
use Bluehost\VerifactiApi\Transport\HttpRequest;
use Bluehost\VerifactiApi\Transport\HttpResponse;
use Bluehost\VerifactiApi\Transport\HttpTransportInterface;

/**
 * Executes HTTP requests against the Verifacti API and maps responses to DTOs.
 */
final class ApiExecutor
{
    private RetryPolicy $retryPolicy;

    /**
     * @param VerifactiConfig        $config      Client configuration.
     * @param HttpTransportInterface $transport   HTTP transport.
     * @param JsonSerializer         $serializer  JSON serializer.
     * @param RetryPolicy|null       $retryPolicy Retry policy; defaults to the config's `max_retries`.
     */
    public function __construct(
        private VerifactiConfig $config,
        private HttpTransportInterface $transport,
        private JsonSerializer $serializer,
        ?RetryPolicy $retryPolicy = null
    ) {
        $this->retryPolicy = $retryPolicy ?? new RetryPolicy($config->getMaxRetries());
    }

    /**
     * Send a GET request.
     *
     * @param string               $path  API path.
     * @param array<string, string> $query Query string parameters.
     *
     * @return ApiResponse
     *
     * @throws AuthenticationException
     * @throws ApiException
     * @throws HttpException
     * @throws SerializationException
     * @throws TransportException
     */
    public function get(string $path, array $query = []): ApiResponse
    {
        return $this->request('GET', $path, null, $query);
    }

    /**
     * Send a POST request.
     *
     * @param string                $path    API path.
     * @param array<string, mixed>  $payload Request body.
     * @param array<string, string> $headers Additional request headers.
     *
     * @return ApiResponse
     *
     * @throws AuthenticationException
     * @throws ApiException
     * @throws HttpException
     * @throws SerializationException
     * @throws TransportException
     */
    public function post(string $path, array $payload = [], array $headers = []): ApiResponse
    {
        return $this->request('POST', $path, $payload, [], $headers);
    }

    /**
     * Send a PUT request.
     *
     * @param string                $path    API path.
     * @param array<string, mixed>  $payload Request body.
     * @param array<string, string> $headers Additional request headers.
     *
     * @return ApiResponse
     *
     * @throws AuthenticationException
     * @throws ApiException
     * @throws HttpException
     * @throws SerializationException
     * @throws TransportException
     */
    public function put(string $path, array $payload = [], array $headers = []): ApiResponse
    {
        return $this->request('PUT', $path, $payload, [], $headers);
    }

    /**
     * Send an HTTP request to the Verifacti API.
     *
     * @param string                $method  HTTP method.
     * @param string                $path    API path.
     * @param array<string, mixed>|null $payload Request body.
     * @param array<string, string> $query   Query string parameters.
     * @param array<string, string> $headers Additional request headers.
     *
     * @return ApiResponse
     *
     * @throws AuthenticationException
     * @throws ApiException
     * @throws HttpException
     * @throws SerializationException
     * @throws TransportException
     */
    public function request(
        string $method,
        string $path,
        ?array $payload = null,
        array $query = [],
        array $headers = []
    ): ApiResponse {
        $requestHeaders = array_merge($this->config->getDefaultHeaders(), $headers);
        $body = $payload !== null ? $this->serializer->encode($payload) : null;

        if ($payload === null) {
            unset($requestHeaders['Content-Type']);
        }

        $response = $this->sendWithRetries(
            new HttpRequest($method, $path, $requestHeaders, $query, $body, $this->config->getTimeoutSeconds())
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
     * Send a request, retrying transient failures when it is safe to do so.
     *
     * @param HttpRequest $request Outbound request.
     *
     * @return HttpResponse
     *
     * @throws TransportException When every attempt fails at transport level.
     */
    private function sendWithRetries(HttpRequest $request): HttpResponse
    {
        $retrySafe = $this->retryPolicy->isRetrySafe($request->getMethod(), $request->getHeaders());
        $maxRetries = $retrySafe ? $this->retryPolicy->getMaxRetries() : 0;

        for ($retry = 0; ; $retry++) {
            try {
                $response = $this->transport->send($request, $this->config);
            } catch (TransportException $exception) {
                if ($retry >= $maxRetries) {
                    throw $exception;
                }

                $this->retryPolicy->wait($this->retryPolicy->delayFor($retry + 1));
                continue;
            }

            if ($retry >= $maxRetries || !$this->retryPolicy->isRetryableStatus($response->getStatusCode())) {
                return $response;
            }

            $this->retryPolicy->wait($this->retryPolicy->delayFor($retry + 1, $this->headerValue($response, 'retry-after')));
        }
    }

    /**
     * Read a response header case-insensitively.
     *
     * @param HttpResponse $response HTTP response.
     * @param string       $name     Lower-case header name.
     *
     * @return string|null
     */
    private function headerValue(HttpResponse $response, string $name): ?string
    {
        foreach ($response->getHeaders() as $headerName => $value) {
            if (strtolower((string) $headerName) === $name) {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * Decode a successful response body.
     *
     * @param HttpResponse $response HTTP response.
     *
     * @return array<string, mixed>
     *
     * @throws SerializationException
     */
    private function decodeSuccessBody(HttpResponse $response): array
    {
        $body = $response->getBody();

        if (trim($body) === '') {
            return [];
        }

        if ($this->looksLikeJson($response)) {
            return $this->serializer->decode($body);
        }

        return ['_raw' => $body];
    }

    /**
     * Throw the appropriate exception for an error response.
     *
     * @param HttpResponse $response HTTP response.
     *
     * @throws AuthenticationException
     * @throws ApiException
     * @throws HttpException
     * @throws SerializationException
     */
    private function throwForErrorResponse(HttpResponse $response): void
    {
        $body = $response->getBody();
        $headers = $response->getHeaders();
        $statusCode = $response->getStatusCode();

        if (!$this->looksLikeJson($response)) {
            $message = trim($body) !== ''
                ? SensitiveDataHelper::sanitizeForMessage(trim($body))
                : sprintf('HTTP %d returned by the Verifacti API.', $statusCode);

            throw match (true) {
                $statusCode === 401, $statusCode === 403 => new AuthenticationException($message, $statusCode, $headers, $body),
                default => new HttpException($message, $statusCode, $headers, $body),
            };
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
            ['message', 'error', 'detail', 'details.message'],
            sprintf('HTTP %d returned by the Verifacti API.', $statusCode)
        );

        $message = is_scalar($messageValue)
            ? SensitiveDataHelper::sanitizeForMessage((string) $messageValue)
            : sprintf('HTTP %d returned by the Verifacti API.', $statusCode);

        throw match (true) {
            $statusCode === 401, $statusCode === 403 => new AuthenticationException($message, $statusCode, $headers, $body),
            default => new ApiException($message, $statusCode, $headers, $body, $errorData),
        };
    }

    /**
     * Determine whether the response body appears to contain JSON.
     *
     * @param HttpResponse $response HTTP response.
     *
     * @return bool
     */
    private function looksLikeJson(HttpResponse $response): bool
    {
        $contentType = strtolower($response->getContentType());
        if (str_contains($contentType, 'application/json')) {
            return true;
        }

        $body = ltrim($response->getBody());

        return $body !== '' && ($body[0] === '{' || $body[0] === '[');
    }
}
