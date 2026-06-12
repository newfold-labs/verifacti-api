<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\ResponseAccessor;

final class InvoiceListResponse extends ApiResponse
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

    /**
     * @return array<int, mixed>
     */
    public function getInvoices(): array
    {
        $value = ResponseAccessor::first($this->getData(), array('items', 'facturas', 'data.items', 'data.facturas'), array());

        return is_array($value) ? array_values($value) : array();
    }

    public function getNextPageToken(): ?string
    {
        $value = ResponseAccessor::first($this->getData(), array('token', 'next_token', 'pagination.token'));

        return is_scalar($value) ? (string) $value : null;
    }
}
