<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Exception;

class ApiException extends HttpException
{
    /**
     * @var array<string, mixed>
     */
    private array $errorData;

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $errorData
     */
    public function __construct(
        string $message,
        int $statusCode,
        array $headers = array(),
        string $responseBody = '',
        array $errorData = array(),
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $headers, $responseBody, $code, $previous);
        $this->errorData = $errorData;
    }

    /**
     * @return array<string, mixed>
     */
    public function getErrorData(): array
    {
        return $this->errorData;
    }
}
