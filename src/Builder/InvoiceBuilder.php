<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Builder;

use Bluehost\VerifactiApi\Dto\CorrectiveAmounts;
use Bluehost\VerifactiApi\Dto\InvoiceCreateRequest;
use Bluehost\VerifactiApi\Dto\InvoiceLine;
use Bluehost\VerifactiApi\Dto\InvoiceModifyRequest;
use Bluehost\VerifactiApi\Dto\InvoiceReference;
use Bluehost\VerifactiApi\Dto\OtherIdentifier;
use Bluehost\VerifactiApi\Dto\SpecialInvoiceData;

final class InvoiceBuilder
{
    private string $series = '';
    private string $number = '';
    private string $issueDate = '';
    private ?string $operationDate = null;
    private string $invoiceType = '';
    private string $description = '';

    /**
     * @var array<int, InvoiceLine>
     */
    private array $lines = array();

    private string $totalAmount = '';
    private ?string $nif = null;
    private ?OtherIdentifier $otherIdentifier = null;
    private ?string $recipientName = null;
    private ?bool $validateRecipient = null;
    private ?string $rectificationType = null;
    private ?CorrectiveAmounts $rectificationAmounts = null;

    /**
     * @var array<int, InvoiceReference>
     */
    private array $rectifiedInvoices = array();

    /**
     * @var array<int, InvoiceReference>
     */
    private array $replacedInvoices = array();

    private ?string $incidentCode = null;
    private ?SpecialInvoiceData $specialData = null;
    private ?string $previousRejectionStatus = null;

    /**
     * @var array<string, mixed>
     */
    private array $extraFields = array();

    public function withSeries(string $series): self
    {
        $this->series = $series;

        return $this;
    }

    public function withNumber(string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function withIssueDate(string $issueDate): self
    {
        $this->issueDate = $issueDate;

        return $this;
    }

    public function withOperationDate(string $operationDate): self
    {
        $this->operationDate = $operationDate;

        return $this;
    }

    public function withInvoiceType(string $invoiceType): self
    {
        $this->invoiceType = $invoiceType;

        return $this;
    }

    public function withDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function addLine(InvoiceLine $line): self
    {
        $this->lines[] = $line;

        return $this;
    }

    public function withTotalAmount(string $totalAmount): self
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function withRecipient(string $nif, string $recipientName): self
    {
        $this->nif = $nif;
        $this->recipientName = $recipientName;

        return $this;
    }

    public function withOtherIdentifier(OtherIdentifier $identifier, ?string $recipientName = null): self
    {
        $this->otherIdentifier = $identifier;
        $this->recipientName = $recipientName;

        return $this;
    }

    public function validateRecipient(bool $enabled = true): self
    {
        $this->validateRecipient = $enabled;

        return $this;
    }

    public function withCorrectiveData(CorrectiveInvoiceBuilder $builder): self
    {
        $payload = $builder->build();
        $this->rectificationType = isset($payload['tipo_rectificativa']) ? (string) $payload['tipo_rectificativa'] : null;
        $this->rectificationAmounts = isset($payload['importe_rectificativa']) ? $payload['importe_rectificativa'] : null;
        $this->rectifiedInvoices = isset($payload['facturas_rectificadas']) ? $payload['facturas_rectificadas'] : array();
        $this->replacedInvoices = isset($payload['facturas_sustituidas']) ? $payload['facturas_sustituidas'] : array();

        return $this;
    }

    public function withSpecialData(SpecialInvoiceData $specialData): self
    {
        $this->specialData = $specialData;

        return $this;
    }

    public function withIncidentCode(string $incidentCode): self
    {
        $this->incidentCode = $incidentCode;

        return $this;
    }

    public function withPreviousRejectionStatus(string $previousRejectionStatus): self
    {
        $this->previousRejectionStatus = $previousRejectionStatus;

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

    public function buildCreate(): InvoiceCreateRequest
    {
        return new InvoiceCreateRequest(
            $this->series,
            $this->number,
            $this->issueDate,
            $this->invoiceType,
            $this->description,
            $this->lines,
            $this->totalAmount,
            $this->operationDate,
            $this->nif,
            $this->otherIdentifier,
            $this->recipientName,
            $this->validateRecipient,
            $this->rectificationType,
            $this->rectificationAmounts,
            $this->rectifiedInvoices,
            $this->replacedInvoices,
            $this->incidentCode,
            $this->specialData,
            $this->extraFields
        );
    }

    public function buildModify(): InvoiceModifyRequest
    {
        return new InvoiceModifyRequest(
            $this->series,
            $this->number,
            $this->issueDate,
            $this->invoiceType,
            $this->description,
            $this->lines,
            $this->totalAmount,
            $this->operationDate,
            $this->nif,
            $this->otherIdentifier,
            $this->recipientName,
            $this->validateRecipient,
            $this->rectificationType,
            $this->rectificationAmounts,
            $this->rectifiedInvoices,
            $this->replacedInvoices,
            $this->incidentCode,
            $this->specialData,
            $this->extraFields,
            $this->previousRejectionStatus
        );
    }
}
