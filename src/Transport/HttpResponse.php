<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Transport;

final class HttpResponse
{
    private int $statusCode;

    /**
     * @var array<string, string>
     */
    private array $headers;

    private string $body;

    /**
     * @var array<string, mixed>
     */
    private array $metadata;

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $metadata
     */
    public function __construct(int $statusCode, array $headers, string $body, array $metadata = array())
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
        $this->metadata = $metadata;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function getContentType(): string
    {
        return isset($this->headers['content-type']) ? $this->headers['content-type'] : '';
    }
}
