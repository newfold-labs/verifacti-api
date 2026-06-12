<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\ResponseAccessor;

final class XmlDownloadResponse extends ApiResponse
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
        $value = ResponseAccessor::first($this->getData(), array('files', 'xmls', 'data.files', 'data.xmls'), array());

        return is_array($value) ? array_values($value) : array();
    }

    public function getRequestXml(): ?string
    {
        $value = ResponseAccessor::first($this->getData(), array('request_xml', 'xml_request', 'data.request_xml'));

        return is_scalar($value) ? (string) $value : null;
    }

    public function getResponseXml(): ?string
    {
        $value = ResponseAccessor::first($this->getData(), array('response_xml', 'xml_response', 'data.response_xml'));

        return is_scalar($value) ? (string) $value : null;
    }
}
