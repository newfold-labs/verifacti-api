<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

final class InvoiceModifyRequest extends AbstractInvoiceRequest
{
    private ?string $previousRejectionStatus;

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
        array $extraFields = array(),
        ?string $previousRejectionStatus = null
    ) {
        parent::__construct(
            $series,
            $number,
            $issueDate,
            $invoiceType,
            $description,
            $lines,
            $totalAmount,
            $operationDate,
            $nif,
            $otherIdentifier,
            $recipientName,
            $validateRecipient,
            $rectificationType,
            $rectificationAmounts,
            $rectifiedInvoices,
            $replacedInvoices,
            $incidentCode,
            $specialData,
            $extraFields
        );

        $this->previousRejectionStatus = $previousRejectionStatus;
    }

    public function getPreviousRejectionStatus(): ?string
    {
        return $this->previousRejectionStatus;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = parent::toArray();

        if ($this->previousRejectionStatus !== null) {
            $payload['rechazo_previo'] = $this->previousRejectionStatus;
        }

        return $payload;
    }
}
