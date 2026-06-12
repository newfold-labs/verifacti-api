<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\ResponseAccessor;

final class DeclarationResponse extends ApiResponse
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

    public function getDocumentUrl(): ?string
    {
        $value = ResponseAccessor::first($this->getData(), array('url', 'document_url', 'data.url', 'data.document_url'));

        return is_scalar($value) ? (string) $value : null;
    }
}
