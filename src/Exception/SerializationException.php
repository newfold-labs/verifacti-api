<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Exception;

class SerializationException extends VerifactiException
{
    private string $payload;

    public function __construct(string $message, string $payload = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->payload = $payload;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }
}
