<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\Arrayable;

final class InvoiceLine implements Arrayable
{
    private string $taxableBase;
    private ?string $taxRate;
    private ?string $taxAmount;
    private ?string $exemptOperationCode;
    private ?string $operationClassification;
    private ?string $regimeKey;

    /**
     * @var array<string, mixed>
     */
    private array $extraFields;

    /**
     * @param array<string, mixed> $extraFields
     */
    public function __construct(
        string $taxableBase,
        ?string $taxRate = null,
        ?string $taxAmount = null,
        ?string $exemptOperationCode = null,
        ?string $operationClassification = null,
        ?string $regimeKey = null,
        array $extraFields = array()
    ) {
        $this->taxableBase = $taxableBase;
        $this->taxRate = $taxRate;
        $this->taxAmount = $taxAmount;
        $this->exemptOperationCode = $exemptOperationCode;
        $this->operationClassification = $operationClassification;
        $this->regimeKey = $regimeKey;
        $this->extraFields = $extraFields;
    }

    public function getTaxableBase(): string
    {
        return $this->taxableBase;
    }

    public function getTaxRate(): ?string
    {
        return $this->taxRate;
    }

    public function getTaxAmount(): ?string
    {
        return $this->taxAmount;
    }

    public function getExemptOperationCode(): ?string
    {
        return $this->exemptOperationCode;
    }

    public function getOperationClassification(): ?string
    {
        return $this->operationClassification;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = array(
            'base_imponible' => $this->taxableBase,
        );

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

        return array_merge($payload, $this->extraFields);
    }
}
