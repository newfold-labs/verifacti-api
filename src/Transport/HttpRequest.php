<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Transport;

final class HttpRequest
{
    private string $method;
    private string $path;

    /**
     * @var array<string, string>
     */
    private array $headers;

    /**
     * @var array<string, string>
     */
    private array $query;

    private ?string $body;
    private int $timeoutSeconds;

    /**
     * @param array<string, string> $headers
     * @param array<string, string> $query
     */
    public function __construct(
        string $method,
        string $path,
        array $headers = array(),
        array $query = array(),
        ?string $body = null,
        int $timeoutSeconds = 30
    ) {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->headers = $headers;
        $this->query = $query;
        $this->body = $body;
        $this->timeoutSeconds = $timeoutSeconds;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array<string, string>
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getTimeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }
}
