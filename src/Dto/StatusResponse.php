<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\ResponseAccessor;

/**
 * Response wrapper for record and invoice status lookups.
 */
final class StatusResponse extends ApiResponse
{
    public const STATUS_PENDING = 'Pendiente';
    public const STATUS_CORRECT = 'Correcto';
    public const STATUS_ACCEPTED_WITH_ERRORS = 'Aceptado con errores';
    public const STATUS_INCORRECT = 'Incorrecto';
    public const STATUS_DUPLICATE = 'Duplicado';
    public const STATUS_CANCELLED = 'Anulado';
    public const STATUS_NOT_FOUND = 'Factura inexistente';
    public const STATUS_NOT_REGISTERED = 'No registrado';
    public const STATUS_AEAT_SERVER_ERROR = 'Error servidor AEAT';

    /**
     * Create a typed response from a generic API response.
     *
     * @param ApiResponse $response Generic API response.
     *
     * @return self
     */
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
     * Return the record UUID, if present.
     *
     * @return string|null
     */
    public function getUuid(): ?string
    {
        $value = ResponseAccessor::first($this->getData(), ['uuid', 'data.uuid', 'registro.uuid']);

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * Return the record status, if present.
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        $value = ResponseAccessor::first($this->getData(), ['status', 'estado', 'data.status', 'data.estado']);

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * Return whether the record status is pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        $status = $this->getStatus();

        return $status !== null && strtolower($status) === 'pendiente';
    }

    /**
     * Return the AEAT verification URL (`url`), if present.
     *
     * @return string|null
     */
    public function getVerificationUrl(): ?string
    {
        $value = ResponseAccessor::first($this->getData(), ['url', 'data.url']);

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * Return the QR code as a base64 string, if present.
     *
     * @return string|null
     */
    public function getQrCodeBase64(): ?string
    {
        $value = ResponseAccessor::first($this->getData(), ['qr', 'data.qr']);

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * Return the AEAT error code (`codigo_error`), if present.
     *
     * @return string|null
     */
    public function getErrorCode(): ?string
    {
        $value = ResponseAccessor::first($this->getData(), ['codigo_error', 'data.codigo_error']);

        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }

    /**
     * Return the AEAT error message (`mensaje_error`), if present.
     *
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        $value = ResponseAccessor::first($this->getData(), ['mensaje_error', 'data.mensaje_error']);

        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }

    /**
     * Whether AEAT accepted the record (`Correcto` or `Aceptado con errores`).
     *
     * Source: Verifacti OpenAPI spec, GET /verifactu/status, field `estado`.
     *
     * @return bool
     */
    public function isAccepted(): bool
    {
        return in_array($this->getStatus(), [self::STATUS_CORRECT, self::STATUS_ACCEPTED_WITH_ERRORS], true);
    }
}
