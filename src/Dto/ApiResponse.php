<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\ResponseAccessor;

class ApiResponse
{
    private int $statusCode;

    /**
     * @var array<string, mixed>
     */
    private array $data;

    private string $rawBody;

    /**
     * @var array<string, string>
     */
    private array $headers;

    /**
     * @var array<string, mixed>
     */
    private array $metadata;

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     * @param array<string, mixed> $metadata
     */
    public function __construct(int $statusCode, array $data, string $rawBody, array $headers = array(), array $metadata = array())
    {
        $this->statusCode = $statusCode;
        $this->data = $data;
        $this->rawBody = $rawBody;
        $this->headers = $headers;
        $this->metadata = $metadata;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getRawBody(): string
    {
        return $this->rawBody;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $path, $default = null)
    {
        return ResponseAccessor::get($this->data, $path, $default);
    }
}
