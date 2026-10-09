<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Validator;

use Bluehost\VerifactiApi\Dto\AbstractInvoiceRequest;
use Bluehost\VerifactiApi\Dto\BulkInvoiceCreateRequest;
use Bluehost\VerifactiApi\Dto\InvoiceCancelRequest;
use Bluehost\VerifactiApi\Dto\InvoiceLine;
use Bluehost\VerifactiApi\Dto\InvoiceListRequest;
use Bluehost\VerifactiApi\Dto\InvoiceModifyRequest;
use Bluehost\VerifactiApi\Dto\InvoiceStatusLookupRequest;
use Bluehost\VerifactiApi\Dto\RecordStatusLookupRequest;
use Bluehost\VerifactiApi\Dto\XmlDownloadRequest;
use Bluehost\VerifactiApi\Dto\XmlExportRequest;
use Bluehost\VerifactiApi\Enum\ExemptionCause;
use Bluehost\VerifactiApi\Enum\IdType;
use Bluehost\VerifactiApi\Enum\InvoiceType;
use Bluehost\VerifactiApi\Enum\OperationQualification;
use Bluehost\VerifactiApi\Enum\PreviousRejection;
use Bluehost\VerifactiApi\Enum\RectificationType;
use Bluehost\VerifactiApi\Enum\RegimeKey;
use Bluehost\VerifactiApi\Enum\TaxType;
use Bluehost\VerifactiApi\Exception\ValidationException;
use Bluehost\VerifactiApi\Support\Amount;
use Bluehost\VerifactiApi\Support\DateHelper;
use DateTimeInterface;

/**
 * Validates request DTO payloads before they are sent to the API.
 */
final class RequestValidator
{
    /**
     * F2 limit: sum of base plus quota of all lines (Verifacti error
     * `vf-verifactu-importe_maximo_f2`; art. 4 RD 1619/2012 caps simplified invoices at 3000 EUR).
     */
    public const F2_MAX_TOTAL_CENTS = 300000;

    /**
     * Default tolerance used by the API for `importe_total` and quota checks (10 EUR).
     */
    public const API_TOLERANCE_CENTS = 1000;

    /**
     * @param int                    $totalToleranceCents Allowed gap between `importe_total` and the sum of the lines.
     * @param int                    $quotaToleranceCents Allowed gap between `cuota_repercutida` and base x rate.
     * @param DateTimeInterface|null $clock               Clock used for the issue-date check (tests).
     */
    public function __construct(
        private int $totalToleranceCents = self::API_TOLERANCE_CENTS,
        private int $quotaToleranceCents = self::API_TOLERANCE_CENTS,
        private ?DateTimeInterface $clock = null
    ) {
    }

    /**
     * Validate a record status lookup request.
     *
     * @param RecordStatusLookupRequest $request Lookup request.
     *
     * @return void
     *
     * @throws ValidationException When validation fails.
     */
    public function validateRecordStatusLookup(RecordStatusLookupRequest $request): void
    {
        $this->throwIfErrors($this->collectNotBlankErrors([
            'uuid' => $request->getUuid(),
        ]));
    }

    /**
     * Validate an invoice status lookup request.
     *
     * @param InvoiceStatusLookupRequest $request Lookup request.
     *
     * @return void
     *
     * @throws ValidationException When validation fails.
     */
    public function validateInvoiceStatusLookup(InvoiceStatusLookupRequest $request): void
    {
        $payload = $request->toArray();
        $errors = $this->collectInvoiceIdentityErrors($payload['serie'], $payload['numero'], $payload['fecha_expedicion']);

        if (isset($payload['fecha_operacion']) && !DateHelper::isValidApiDate((string) $payload['fecha_operacion'])) {
            $errors[] = 'fecha_operacion must use the d-m-Y format.';
        }

        $this->throwIfErrors($errors);
    }

    /**
     * Validate an invoice create request.
     *
     * @param AbstractInvoiceRequest $request Invoice payload.
     *
     * @return void
     *
     * @throws ValidationException When validation fails.
     */
    public function validateCreate(AbstractInvoiceRequest $request): void
    {
        $this->throwIfErrors($this->validateInvoicePayload($request));
    }

    /**
     * Validate an invoice modify request.
     *
     * @param InvoiceModifyRequest $request Modify payload.
     *
     * @return void
     *
     * @throws ValidationException When validation fails.
     */
    public function validateModify(InvoiceModifyRequest $request): void
    {
        $errors = $this->validateInvoicePayload($request);
        $previousRejectionStatus = $request->getPreviousRejectionStatus();

        if ($previousRejectionStatus !== null && !PreviousRejection::isValid($previousRejectionStatus)) {
            $errors[] = 'rechazo_previo must be one of N, X or S.';
        }

        $this->throwIfErrors($errors);
    }

    /**
     * Validate a bulk invoice create request.
     *
     * @param BulkInvoiceCreateRequest $request Bulk create payload.
     *
     * @return void
     *
     * @throws ValidationException When validation fails.
     */
    public function validateBulkCreate(BulkInvoiceCreateRequest $request): void
    {
        $errors = [];
        $invoices = $request->getInvoices();
        $count = count($invoices);

        if ($count === 0) {
            $errors[] = 'At least one invoice is required for create_bulk.';
        }

        if ($count > 50) {
            $errors[] = 'create_bulk supports at most 50 invoices per request.';
        }

        foreach ($invoices as $index => $invoice) {
            foreach ($this->validateInvoicePayload($invoice) as $error) {
                $errors[] = sprintf('Invoice %d: %s', $index + 1, $error);
            }
        }

        $this->throwIfErrors($errors);
    }

    /**
     * Validate an invoice cancel request.
     *
     * @param InvoiceCancelRequest $request Cancel payload.
     *
     * @return void
     *
     * @throws ValidationException When validation fails.
     */
    public function validateCancel(InvoiceCancelRequest $request): void
    {
        $payload = $request->toArray();
        $errors = $this->collectInvoiceIdentityErrors(
            (string) $payload['serie'],
            (string) $payload['numero'],
            (string) $payload['fecha_expedicion']
        );

        if (isset($payload['rechazo_previo']) && !PreviousRejection::isValid((string) $payload['rechazo_previo'])) {
            $errors[] = 'rechazo_previo must be one of N, X or S.';
        }

        $this->throwIfErrors($errors);
    }

    /**
     * Validate an invoice list request.
     *
     * @param InvoiceListRequest $request List filters.
     *
     * @return void
     *
     * @throws ValidationException When validation fails.
     */
    public function validateList(InvoiceListRequest $request): void
    {
        $payload = $request->toArray();
        $errors = $this->collectNotBlankErrors([
            'ejercicio' => (string) $payload['ejercicio'],
            'periodo' => (string) $payload['periodo'],
        ]);

        if (isset($payload['numero']) && !isset($payload['serie'])) {
            $errors[] = 'serie is required when numero is present.';
        }

        if (isset($payload['fecha_expedicion']) && !DateHelper::isValidApiDate((string) $payload['fecha_expedicion'])) {
            $errors[] = 'fecha_expedicion must use the d-m-Y format.';
        }

        $this->throwIfErrors($errors);
    }

    /**
     * Validate an XML export request.
     *
     * @param XmlExportRequest $request Export request.
     *
     * @return void
     *
     * @throws ValidationException When validation fails.
     */
    public function validateExport(XmlExportRequest $request): void
    {
        $this->throwIfErrors($this->collectNotBlankErrors($request->toArray()));
    }

    /**
     * Validate an XML download request.
     *
     * @param XmlDownloadRequest $request Download request.
     *
     * @return void
     *
     * @throws ValidationException When validation fails.
     */
    public function validateDownload(XmlDownloadRequest $request): void
    {
        $this->throwIfErrors($this->collectNotBlankErrors([
            'numero' => $request->toArray()['numero'],
        ]));
    }

    /**
     * Collect validation errors for a full invoice payload.
     *
     * Rules mirror the Verifacti OpenAPI spec for POST /verifactu/create and
     * its error catalogue (https://www.verifacti.com/openapi/codigos-verifactu.yaml).
     *
     * @param AbstractInvoiceRequest $request Invoice payload.
     *
     * @return array<int, string>
     */
    private function validateInvoicePayload(AbstractInvoiceRequest $request): array
    {
        $errors = $this->collectInvoiceIdentityErrors(
            $request->getSeries(),
            $request->getNumber(),
            $request->getIssueDate()
        );

        $errors = array_merge($errors, $this->collectNotBlankErrors([
            'tipo_factura' => $request->getInvoiceType(),
            'descripcion' => $request->getDescription(),
            'importe_total' => $request->getTotalAmount(),
        ]));

        // Only new records must be issued today; a modify request identifies an
        // existing record by its original fecha_expedicion.
        if (!$request instanceof InvoiceModifyRequest && !DateHelper::isToday($request->getIssueDate(), $this->clock)) {
            $errors[] = 'fecha_expedicion must match the current date.';
        }

        if (mb_strlen($request->getDescription()) > 500) {
            $errors[] = 'descripcion cannot exceed 500 characters.';
        }

        if (strlen($request->getSeries() . $request->getNumber()) > 60) {
            $errors[] = 'serie and numero together cannot exceed 60 characters.';
        }

        $lines = $request->getLines();
        if ($lines === []) {
            $errors[] = 'At least one invoice line is required.';
        }

        if (count($lines) > 12) {
            $errors[] = 'A maximum of 12 invoice lines is allowed.';
        }

        $invoiceType = $request->getInvoiceType();
        $rectificationType = $request->getRectificationType();

        foreach ($lines as $index => $line) {
            foreach ($this->validateLine($line, $invoiceType, $rectificationType) as $lineError) {
                $errors[] = sprintf('lineas[%d]: %s', $index, $lineError);
            }
        }

        if ($request->getOperationDate() !== null && !DateHelper::isValidApiDate($request->getOperationDate())) {
            $errors[] = 'fecha_operacion must use the d-m-Y format.';
        }

        if ($invoiceType !== '' && !InvoiceType::isValid($invoiceType)) {
            $errors[] = sprintf('tipo_factura must be one of %s.', implode(', ', InvoiceType::values()));
        }

        if (trim($request->getTotalAmount()) !== '' && !Amount::isValid($request->getTotalAmount())) {
            $errors[] = 'importe_total must be a number with at most 2 decimals.';
        }

        $errors = array_merge(
            $errors,
            $this->validateCorrectiveData($request),
            $this->validateRecipient($request),
            $this->validateTotals($request)
        );

        return $errors;
    }

    /**
     * Validate corrective (rectificativa) and substitution (F3) fields.
     *
     * @param AbstractInvoiceRequest $request Invoice payload.
     *
     * @return array<int, string>
     */
    private function validateCorrectiveData(AbstractInvoiceRequest $request): array
    {
        $errors = [];
        $invoiceType = $request->getInvoiceType();
        $rectificationType = $request->getRectificationType();
        $isCorrective = InvoiceType::isCorrective($invoiceType);

        if ($isCorrective && $rectificationType === null) {
            $errors[] = 'tipo_rectificativa is required for rectificative invoices.';
        }

        if (!$isCorrective && $rectificationType !== null) {
            $errors[] = 'tipo_rectificativa is only allowed for rectificative invoices (R1-R5).';
        }

        if ($rectificationType !== null && !RectificationType::isValid($rectificationType)) {
            $errors[] = 'tipo_rectificativa must be one of S or I.';
        }

        $amounts = $request->getRectificationAmounts();

        if ($rectificationType === RectificationType::SUBSTITUTION && $amounts === null) {
            $errors[] = 'importe_rectificativa is required when tipo_rectificativa is S.';
        }

        if ($rectificationType !== RectificationType::SUBSTITUTION && $amounts !== null) {
            $errors[] = 'importe_rectificativa is only allowed when tipo_rectificativa is S.';
        }

        if ($amounts !== null) {
            foreach (array_filter($amounts->toArray(), 'is_string') as $field => $value) {
                if (!Amount::isValid($value)) {
                    $errors[] = sprintf('importe_rectificativa.%s must be a number with at most 2 decimals.', $field);
                }
            }
        }

        if (!$isCorrective && $request->getRectifiedInvoices() !== []) {
            $errors[] = 'facturas_rectificadas is only allowed for rectificative invoices (R1-R5).';
        }

        if ($invoiceType !== InvoiceType::F3 && $request->getReplacedInvoices() !== []) {
            $errors[] = 'facturas_sustituidas is only allowed for F3 invoices.';
        }

        foreach (['facturas_rectificadas' => $request->getRectifiedInvoices(), 'facturas_sustituidas' => $request->getReplacedInvoices()] as $field => $references) {
            foreach ($references as $index => $reference) {
                if (trim($reference->getNumber()) === '') {
                    $errors[] = sprintf('%s[%d].numero cannot be empty.', $field, $index);
                }

                if (!DateHelper::isValidApiDate($reference->getIssueDate())) {
                    $errors[] = sprintf('%s[%d].fecha_expedicion must use the d-m-Y format.', $field, $index);
                }
            }
        }

        return $errors;
    }

    /**
     * Validate recipient fields against the invoice type.
     *
     * F2 and R5 must not identify the recipient; F1, F3 and R1-R4 must
     * carry `nombre` plus `nif` or `id_otro` (Verifacti error codes
     * `vf-verifactu-destinatario_no_aplica` and `vf-verifactu-destinatario_obligatorio`).
     *
     * @param AbstractInvoiceRequest $request Invoice payload.
     *
     * @return array<int, string>
     */
    private function validateRecipient(AbstractInvoiceRequest $request): array
    {
        $errors = [];
        $invoiceType = $request->getInvoiceType();
        $nif = $request->getNif();
        $otherIdentifier = $request->getOtherIdentifier();
        $name = $request->getRecipientName();
        $hasAnyRecipientField = $nif !== null || $otherIdentifier !== null || $name !== null;

        if (InvoiceType::forbidsRecipient($invoiceType) && $hasAnyRecipientField) {
            $errors[] = sprintf('nif, nombre and id_otro are not allowed for %s invoices.', $invoiceType);
        }

        if (InvoiceType::requiresRecipient($invoiceType)) {
            if ($name === null || trim($name) === '') {
                $errors[] = sprintf('nombre is required for %s invoices.', $invoiceType);
            }

            if (($nif === null || trim($nif) === '') && $otherIdentifier === null) {
                $errors[] = sprintf('nif or id_otro is required for %s invoices.', $invoiceType);
            }
        }

        if ($nif !== null && strlen($nif) !== 9) {
            $errors[] = 'nif must be exactly 9 characters.';
        }

        if ($name !== null && mb_strlen($name) > 120) {
            $errors[] = 'nombre cannot exceed 120 characters.';
        }

        if ($otherIdentifier !== null) {
            if (!IdType::isValid($otherIdentifier->getIdType())) {
                $errors[] = sprintf('id_otro.id_type must be one of %s.', implode(', ', IdType::values()));
            }

            if ($otherIdentifier->getIdType() !== IdType::VAT && trim($otherIdentifier->getCountryCode()) === '') {
                $errors[] = 'id_otro.codigo_pais is required unless id_type is 02.';
            }

            $identifier = trim($otherIdentifier->getIdentifier());
            if ($identifier === '' || strlen($identifier) > 20) {
                $errors[] = 'id_otro.id must contain between 1 and 20 characters.';
            }
        }

        return $errors;
    }

    /**
     * Validate amount consistency: lines vs `importe_total` and the F2 limit.
     *
     * Tolerances default to the API's own (10 EUR, error `vf-verifactu-importe_total`).
     *
     * @param AbstractInvoiceRequest $request Invoice payload.
     *
     * @return array<int, string>
     */
    private function validateTotals(AbstractInvoiceRequest $request): array
    {
        $linesTotal = 0;
        $skipTotalCheck = false;

        foreach ($request->getLines() as $line) {
            $values = [$line->getTaxableBase(), $line->getTaxAmount() ?? '0', $line->getSurchargeAmount() ?? '0'];
            foreach ($values as $value) {
                if (!Amount::isValid($value)) {
                    return [];
                }
            }

            $linesTotal += Amount::toCents($values[0]) + Amount::toCents($values[1]) + Amount::toCents($values[2]);

            if (in_array($line->getRegimeKey(), RegimeKey::SKIP_TOTAL_CHECK, true)) {
                $skipTotalCheck = true;
            }
        }

        if ($request->getLines() === [] || !Amount::isValid($request->getTotalAmount())) {
            return [];
        }

        $errors = [];
        $declaredTotal = Amount::toCents($request->getTotalAmount());

        if (!$skipTotalCheck && abs($declaredTotal - $linesTotal) > $this->totalToleranceCents) {
            $errors[] = sprintf(
                'importe_total (%s) does not match the sum of the lines (%s).',
                $request->getTotalAmount(),
                Amount::fromCents($linesTotal)
            );
        }

        $special = $request->getSpecialData() !== null ? $request->getSpecialData()->toArray() : [];
        $art61d = ($special['factura_sin_identif_destinatario_art_61d'] ?? null) === 'S';

        if ($request->getInvoiceType() === InvoiceType::F2 && !$art61d && abs($linesTotal) > self::F2_MAX_TOTAL_CENTS) {
            $errors[] = 'F2 invoices cannot exceed 3000.00 (base plus quota) unless especial.factura_sin_identif_destinatario_art_61d is S.';
        }

        return $errors;
    }

    /**
     * Collect validation errors for a single invoice line.
     *
     * @param InvoiceLine $line              Invoice line payload.
     * @param string      $invoiceType       Invoice type of the parent invoice.
     * @param string|null $rectificationType Rectification type of the parent invoice.
     *
     * @return array<int, string>
     */
    private function validateLine(InvoiceLine $line, string $invoiceType = '', ?string $rectificationType = null): array
    {
        $payload = $line->toArray();
        $errors = $this->collectNotBlankErrors([
            'base_imponible' => (string) ($payload['base_imponible'] ?? ''),
        ]);

        $hasRate = isset($payload['tipo_impositivo']);
        $hasQuota = isset($payload['cuota_repercutida']);
        $hasTax = $hasRate && $hasQuota;
        $exemption = $line->getExemptOperationCode();
        $qualification = $line->getOperationClassification();
        $taxType = $line->getTaxType() ?? TaxType::IVA;

        if (!$hasTax && $exemption === null && $qualification === null) {
            $errors[] = 'Either tax values or an exempt/non-subject classification must be provided.';
        }

        if ($hasRate !== $hasQuota) {
            $errors[] = 'tipo_impositivo and cuota_repercutida must be provided together.';
        }

        if (($line->getSurchargeRate() === null) !== ($line->getSurchargeAmount() === null)) {
            $errors[] = 'tipo_recargo_equivalencia and cuota_recargo_equivalencia must be provided together.';
        }

        if ($line->getTaxType() !== null && !TaxType::isValid($line->getTaxType())) {
            $errors[] = sprintf('impuesto must be one of %s.', implode(', ', TaxType::values()));
        }

        if ($line->getRegimeKey() !== null && !RegimeKey::isValid($line->getRegimeKey())) {
            $errors[] = 'clave_regimen is not a valid regime key.';
        }

        if ($qualification !== null && !OperationQualification::isValid($qualification)) {
            $errors[] = sprintf('calificacion_operacion must be one of %s.', implode(', ', OperationQualification::values()));
        }

        if ($exemption !== null) {
            if (!ExemptionCause::isAllowedFor($exemption, $taxType)) {
                $errors[] = sprintf('operacion_exenta %s is not valid for impuesto %s.', $exemption, $taxType);
            }

            if ($qualification !== null) {
                $errors[] = 'operacion_exenta and calificacion_operacion cannot be combined.';
            }

            if ($hasRate || $hasQuota || $line->getSurchargeRate() !== null) {
                $errors[] = 'Exempt lines cannot carry tipo_impositivo, cuota_repercutida or equivalence surcharge.';
            }
        }

        foreach (['base_imponible', 'cuota_repercutida', 'cuota_recargo_equivalencia'] as $field) {
            if (isset($payload[$field]) && is_string($payload[$field]) && trim($payload[$field]) !== '' && !Amount::isValid($payload[$field])) {
                $errors[] = sprintf('%s must be a number with at most 2 decimals.', $field);
            }
        }

        foreach (['tipo_impositivo', 'tipo_recargo_equivalencia'] as $field) {
            if (isset($payload[$field]) && is_string($payload[$field]) && !Amount::isValidRate($payload[$field])) {
                $errors[] = sprintf('%s must be a non-negative number with at most 2 decimals.', $field);
            }
        }

        if ($errors !== [] || !$hasTax) {
            return $errors;
        }

        $base = Amount::toCents($line->getTaxableBase());
        $quota = Amount::toCents((string) $line->getTaxAmount());

        if ($qualification === OperationQualification::S2 && ($quota !== 0 || (float) $line->getTaxRate() !== 0.0)) {
            $errors[] = 'S2 (reverse charge) lines must have tipo_impositivo and cuota_repercutida equal to 0.';
        }

        $isS1 = $qualification === null || $qualification === OperationQualification::S1;
        $signRuleExempt = $rectificationType === RectificationType::DIFFERENCES
            || in_array($invoiceType, [InvoiceType::R2, InvoiceType::R3], true);

        if ($isS1 && !$signRuleExempt && $quota !== 0 && ($base === 0 || ($base < 0) !== ($quota < 0))) {
            $errors[] = 'cuota_repercutida must have the same sign as base_imponible.';
        }

        if ($isS1 && !$signRuleExempt) {
            $expected = Amount::expectedQuotaCents($line->getTaxableBase(), (string) $line->getTaxRate());
            if (abs($expected - $quota) > $this->quotaToleranceCents) {
                $errors[] = sprintf(
                    'cuota_repercutida (%s) does not match base_imponible x tipo_impositivo (%s).',
                    $line->getTaxAmount(),
                    Amount::fromCents($expected)
                );
            }
        }

        return $errors;
    }

    /**
     * Collect validation errors for invoice identity fields.
     *
     * @param string $series    Invoice series.
     * @param string $number    Invoice number.
     * @param string $issueDate Issue date.
     *
     * @return array<int, string>
     */
    private function collectInvoiceIdentityErrors(string $series, string $number, string $issueDate): array
    {
        $errors = $this->collectNotBlankErrors([
            'numero' => $number,
            'fecha_expedicion' => $issueDate,
        ]);

        if (!DateHelper::isValidApiDate($issueDate)) {
            $errors[] = 'fecha_expedicion must use the d-m-Y format.';
        }

        return $errors;
    }

    /**
     * Collect errors for fields that must not be blank.
     *
     * @param array<string, string> $values Field values keyed by field name.
     *
     * @return array<int, string>
     */
    private function collectNotBlankErrors(array $values): array
    {
        $errors = [];

        foreach ($values as $field => $value) {
            if (trim($value) === '') {
                $errors[] = sprintf('%s cannot be empty.', $field);
            }
        }

        return $errors;
    }

    /**
     * Throw a validation exception when errors are present.
     *
     * @param array<int, string> $errors Validation error messages.
     *
     * @return void
     *
     * @throws ValidationException When one or more errors are present.
     */
    private function throwIfErrors(array $errors): void
    {
        if ($errors !== []) {
            throw new ValidationException('The request payload is invalid.', $errors);
        }
    }
}
