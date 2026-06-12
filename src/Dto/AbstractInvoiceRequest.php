<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\Arrayable;

abstract class AbstractInvoiceRequest implements Arrayable
{
    protected string $series;
    protected string $number;
    protected string $issueDate;
    protected ?string $operationDate;
    protected string $invoiceType;
    protected string $description;

    /**
     * @var array<int, InvoiceLine>
     */
    protected array $lines;

    protected string $totalAmount;
    protected ?string $nif;
    protected ?OtherIdentifier $otherIdentifier;
    protected ?string $recipientName;
    protected ?bool $validateRecipient;
    protected ?string $rectificationType;
    protected ?CorrectiveAmounts $rectificationAmounts;

    /**
     * @var array<int, InvoiceReference>
     */
    protected array $rectifiedInvoices;

    /**
     * @var array<int, InvoiceReference>
     */
    protected array $replacedInvoices;

    protected ?string $incidentCode;
    protected ?SpecialInvoiceData $specialData;

    /**
     * @var array<string, mixed>
     */
    protected array $extraFields;

    /**
     * @param array<int, InvoiceLine> $lines
     * @param array<int, InvoiceReference> $rectifiedInvoices
     * @param array<int, InvoiceReference> $replacedInvoices
     * @param array<string, mixed> $extraFields
     */
    public function __construct(
        string $series,
        string $number,
        string $issueDate,
        string $invoiceType,
        string $description,
        array $lines,
        string $totalAmount,
        ?string $operationDate = null,
        ?string $nif = null,
        ?OtherIdentifier $otherIdentifier = null,
        ?string $recipientName = null,
        ?bool $validateRecipient = null,
        ?string $rectificationType = null,
        ?CorrectiveAmounts $rectificationAmounts = null,
        array $rectifiedInvoices = array(),
        array $replacedInvoices = array(),
        ?string $incidentCode = null,
        ?SpecialInvoiceData $specialData = null,
        array $extraFields = array()
    ) {
        $this->series = $series;
        $this->number = $number;
        $this->issueDate = $issueDate;
        $this->invoiceType = $invoiceType;
        $this->description = $description;
        $this->lines = $lines;
        $this->totalAmount = $totalAmount;
        $this->operationDate = $operationDate;
        $this->nif = $nif;
        $this->otherIdentifier = $otherIdentifier;
        $this->recipientName = $recipientName;
        $this->validateRecipient = $validateRecipient;
        $this->rectificationType = $rectificationType;
        $this->rectificationAmounts = $rectificationAmounts;
        $this->rectifiedInvoices = $rectifiedInvoices;
        $this->replacedInvoices = $replacedInvoices;
        $this->incidentCode = $incidentCode;
        $this->specialData = $specialData;
        $this->extraFields = $extraFields;
    }

    public function getSeries(): string
    {
        return $this->series;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getIssueDate(): string
    {
        return $this->issueDate;
    }

    public function getInvoiceType(): string
    {
        return $this->invoiceType;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return array<int, InvoiceLine>
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function getOperationDate(): ?string
    {
        return $this->operationDate;
    }

    public function getRectificationType(): ?string
    {
        return $this->rectificationType;
    }

    public function getRectificationAmounts(): ?CorrectiveAmounts
    {
        return $this->rectificationAmounts;
    }

    /**
     * @return array<int, InvoiceReference>
     */
    public function getRectifiedInvoices(): array
    {
        return $this->rectifiedInvoices;
    }

    /**
     * @return array<int, InvoiceReference>
     */
    public function getReplacedInvoices(): array
    {
        return $this->replacedInvoices;
    }

    /**
     * Build a modify (PUT /verifactu/modify) request from this invoice payload.
     *
     * @param string|null $series                   Override serie (e.g. stored AEAT identifiers).
     * @param string|null $number                   Override numero.
     * @param string|null $issueDate                Override fecha_expedicion.
     * @param string|null $previousRejectionStatus  rechazo_previo (typically "N" or "S").
     */
    public function toModifyRequest(
        ?string $series = null,
        ?string $number = null,
        ?string $issueDate = null,
        ?string $previousRejectionStatus = null
    ): InvoiceModifyRequest {
        return new InvoiceModifyRequest(
            $series ?? $this->series,
            $number ?? $this->number,
            $issueDate ?? $this->issueDate,
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
            $previousRejectionStatus
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = array(
            'serie' => $this->series,
            'numero' => $this->number,
            'fecha_expedicion' => $this->issueDate,
            'tipo_factura' => $this->invoiceType,
            'descripcion' => $this->description,
            'lineas' => array_map(static function (InvoiceLine $line): array {
                return $line->toArray();
            }, $this->lines),
            'importe_total' => $this->totalAmount,
        );

        if ($this->operationDate !== null) {
            $payload['fecha_operacion'] = $this->operationDate;
        }

        if ($this->nif !== null) {
            $payload['nif'] = $this->nif;
        }

        if ($this->otherIdentifier !== null) {
            $payload['id_otro'] = $this->otherIdentifier->toArray();
        }

        if ($this->recipientName !== null) {
            $payload['nombre'] = $this->recipientName;
        }

        if ($this->validateRecipient !== null) {
            $payload['validar_destinatario'] = $this->validateRecipient;
        }

        if ($this->rectificationType !== null) {
            $payload['tipo_rectificativa'] = $this->rectificationType;
        }

        if ($this->rectificationAmounts !== null) {
            $payload['importe_rectificativa'] = $this->rectificationAmounts->toArray();
        }

        if ($this->rectifiedInvoices !== array()) {
            $payload['facturas_rectificadas'] = array_map(static function (InvoiceReference $reference): array {
                return $reference->toArray();
            }, $this->rectifiedInvoices);
        }

        if ($this->replacedInvoices !== array()) {
            $payload['facturas_sustituidas'] = array_map(static function (InvoiceReference $reference): array {
                return $reference->toArray();
            }, $this->replacedInvoices);
        }

        if ($this->incidentCode !== null) {
            $payload['incidencia'] = $this->incidentCode;
        }

        if ($this->specialData !== null) {
            $payload['especial'] = $this->specialData->toArray();
        }

        return array_merge($payload, $this->extraFields);
    }
}
