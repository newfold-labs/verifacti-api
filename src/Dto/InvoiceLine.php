<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\Arrayable;

/**
 * Invoice line item payload.
 */
final class InvoiceLine implements Arrayable
{
    /**
     * @param array<string, mixed> $extraFields     Additional API fields.
     * @param string|null          $taxType         `impuesto` code, see {@see \Bluehost\VerifactiApi\Enum\TaxType}.
     * @param string|null          $surchargeRate   `tipo_recargo_equivalencia`.
     * @param string|null          $surchargeAmount `cuota_recargo_equivalencia`.
     */
    public function __construct(
        private string $taxableBase,
        private ?string $taxRate = null,
        private ?string $taxAmount = null,
        private ?string $exemptOperationCode = null,
        private ?string $operationClassification = null,
        private ?string $regimeKey = null,
        private array $extraFields = [],
        private ?string $taxType = null,
        private ?string $surchargeRate = null,
        private ?string $surchargeAmount = null
    ) {
    }

    /**
     * Return the taxable base amount.
     *
     * @return string
     */
    public function getTaxableBase(): string
    {
        return $this->taxableBase;
    }

    /**
     * Return the tax rate, if set.
     *
     * @return string|null
     */
    public function getTaxRate(): ?string
    {
        return $this->taxRate;
    }

    /**
     * Return the tax amount, if set.
     *
     * @return string|null
     */
    public function getTaxAmount(): ?string
    {
        return $this->taxAmount;
    }

    /**
     * Return the exempt operation code, if set.
     *
     * @return string|null
     */
    public function getExemptOperationCode(): ?string
    {
        return $this->exemptOperationCode;
    }

    /**
     * Return the operation classification, if set.
     *
     * @return string|null
     */
    public function getOperationClassification(): ?string
    {
        return $this->operationClassification;
    }

    /**
     * Return the regime key, if set.
     *
     * @return string|null
     */
    public function getRegimeKey(): ?string
    {
        return $this->regimeKey;
    }

    /**
     * Return the tax type (`impuesto`), if set. The API defaults to IVA (`01`).
     *
     * @return string|null
     */
    public function getTaxType(): ?string
    {
        return $this->taxType;
    }

    /**
     * Return the equivalence surcharge rate, if set.
     *
     * @return string|null
     */
    public function getSurchargeRate(): ?string
    {
        return $this->surchargeRate;
    }

    /**
     * Return the equivalence surcharge amount, if set.
     *
     * @return string|null
     */
    public function getSurchargeAmount(): ?string
    {
        return $this->surchargeAmount;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $payload = [];

        if ($this->taxType !== null) {
            $payload['impuesto'] = $this->taxType;
        }

        $payload['base_imponible'] = $this->taxableBase;

        if ($this->taxRate !== null) {
            $payload['tipo_impositivo'] = $this->taxRate;
        }

        if ($this->taxAmount !== null) {
            $payload['cuota_repercutida'] = $this->taxAmount;
        }

        if ($this->exemptOperationCode !== null) {
            $payload['operacion_exenta'] = $this->exemptOperationCode;
        }

        if ($this->operationClassification !== null) {
            $payload['calificacion_operacion'] = $this->operationClassification;
        }

        if ($this->regimeKey !== null) {
            $payload['clave_regimen'] = $this->regimeKey;
        }

        if ($this->surchargeRate !== null) {
            $payload['tipo_recargo_equivalencia'] = $this->surchargeRate;
        }

        if ($this->surchargeAmount !== null) {
            $payload['cuota_recargo_equivalencia'] = $this->surchargeAmount;
        }

        return array_merge($payload, $this->extraFields);
    }
}
