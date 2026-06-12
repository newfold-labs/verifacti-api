<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\ResponseAccessor;

final class StatusResponse extends ApiResponse
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

    public function getUuid(): ?string
    {
        $value = ResponseAccessor::first($this->getData(), array('uuid', 'data.uuid', 'registro.uuid'));

        return is_scalar($value) ? (string) $value : null;
    }

    public function getStatus(): ?string
    {
        $value = ResponseAccessor::first($this->getData(), array('status', 'estado', 'data.status', 'data.estado'));

        return is_scalar($value) ? (string) $value : null;
    }

    public function isPending(): bool
    {
        $status = $this->getStatus();

        return $status !== null && strtolower($status) === 'pendiente';
    }
}
