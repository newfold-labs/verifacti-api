<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\ResponseAccessor;

final class InvoiceOperationResponse extends ApiResponse
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
        $value = ResponseAccessor::first($this->getData(), array('uuid', 'data.uuid'));

        return is_scalar($value) ? (string) $value : null;
    }

    public function getStatus(): ?string
    {
        $value = ResponseAccessor::first($this->getData(), array('status', 'estado', 'data.status', 'data.estado'));

        return is_scalar($value) ? (string) $value : null;
    }

    public function getQrCodeBase64(): ?string
    {
        $value = ResponseAccessor::first($this->getData(), array('qr', 'qr_base64', 'data.qr', 'data.qr_base64'));

        return is_scalar($value) ? (string) $value : null;
    }

    public function getQrCodeUrl(): ?string
    {
        $value = ResponseAccessor::first($this->getData(), array('qr_url', 'data.qr_url', 'url_qr'));

        return is_scalar($value) ? (string) $value : null;
    }
}
