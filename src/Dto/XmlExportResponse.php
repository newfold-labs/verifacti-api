<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\ResponseAccessor;

final class XmlExportResponse extends ApiResponse
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
    public function getFiles(): array
    {
        $value = ResponseAccessor::first($this->getData(), array('files', 'items', 'data.files', 'data.items'), array());

        return is_array($value) ? array_values($value) : array();
    }

    public function getToken(): ?string
    {
        $value = ResponseAccessor::first($this->getData(), array('token', 'next_token', 'data.token'));

        return is_scalar($value) ? (string) $value : null;
    }
}
