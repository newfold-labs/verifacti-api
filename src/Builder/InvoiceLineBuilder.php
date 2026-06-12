<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Builder;

use Bluehost\VerifactiApi\Dto\InvoiceLine;

final class InvoiceLineBuilder
{
    private string $taxableBase = '';
    private ?string $taxRate = null;
    private ?string $taxAmount = null;
    private ?string $exemptOperationCode = null;
    private ?string $operationClassification = null;
    private ?string $regimeKey = null;

    /**
     * @var array<string, mixed>
     */
    private array $extraFields = array();

    public function withTaxableBase(string $taxableBase): self
    {
        $this->taxableBase = $taxableBase;

        return $this;
    }

    public function withTax(string $taxRate, string $taxAmount): self
    {
        $this->taxRate = $taxRate;
        $this->taxAmount = $taxAmount;

        return $this;
    }

    public function asExempt(string $exemptOperationCode): self
    {
        $this->exemptOperationCode = $exemptOperationCode;

        return $this;
    }

    public function withOperationClassification(string $operationClassification): self
    {
        $this->operationClassification = $operationClassification;

        return $this;
    }

    public function withRegimeKey(string $regimeKey): self
    {
        $this->regimeKey = $regimeKey;

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function withExtraField(string $key, $value): self
    {
        $this->extraFields[$key] = $value;

        return $this;
    }

    public function build(): InvoiceLine
    {
        return new InvoiceLine(
            $this->taxableBase,
            $this->taxRate,
            $this->taxAmount,
            $this->exemptOperationCode,
            $this->operationClassification,
            $this->regimeKey,
            $this->extraFields
        );
    }
}
