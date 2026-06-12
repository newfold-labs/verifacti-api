<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\ResponseAccessor;

final class BulkCreateResponse extends ApiResponse
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
    public function getItems(): array
    {
        $value = ResponseAccessor::first($this->getData(), array('items', 'facturas', 'data', 'data.items'), array());

        return is_array($value) ? array_values($value) : array();
    }
}
