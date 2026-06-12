<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\ResponseAccessor;

final class HealthResponse extends ApiResponse
{
    public static function fromApiResponse(ApiResponse $response): self
    {
        return new self(
            $response->getStatusCode(),
            $response->getData(),
            $response->getRawBody(),
            $response->getHeaders(),
            $response->getMetadata()
        );
    }

    public function getHealthStatus(): ?string
    {
        $value = ResponseAccessor::first($this->getData(), array('status', 'estado', 'api.status'));

        return is_scalar($value) ? (string) $value : null;
    }
}
