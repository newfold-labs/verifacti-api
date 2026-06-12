<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Exception;

class HttpException extends VerifactiException
{
    private int $statusCode;

    /**
     * @var array<string, string>
     */
    private array $headers;

    private string $responseBody;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        string $message,
        int $statusCode,
        array $headers = array(),
        string $responseBody = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->responseBody = $responseBody;
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

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }
}
