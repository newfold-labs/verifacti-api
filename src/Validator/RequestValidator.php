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
use Bluehost\VerifactiApi\Exception\ValidationException;
use Bluehost\VerifactiApi\Support\DateHelper;

final class RequestValidator
{
    public function validateRecordStatusLookup(RecordStatusLookupRequest $request): void
    {
        $this->throwIfErrors($this->collectNotBlankErrors(array(
            'uuid' => $request->getUuid(),
        )));
    }

    public function validateInvoiceStatusLookup(InvoiceStatusLookupRequest $request): void
    {
        $payload = $request->toArray();
        $errors = $this->collectInvoiceIdentityErrors($payload['serie'], $payload['numero'], $payload['fecha_expedicion']);

        if (isset($payload['fecha_operacion']) && !DateHelper::isValidApiDate((string) $payload['fecha_operacion'])) {
            $errors[] = 'fecha_operacion must use the d-m-Y format.';
        }

        $this->throwIfErrors($errors);
    }

    public function validateCreate(AbstractInvoiceRequest $request): void
    {
        $this->throwIfErrors($this->validateInvoicePayload($request));
    }

    public function validateModify(InvoiceModifyRequest $request): void
    {
        $errors = $this->validateInvoicePayload($request);
        $previousRejectionStatus = $request->getPreviousRejectionStatus();

        if ($previousRejectionStatus !== null && !in_array($previousRejectionStatus, array('N', 'X', 'S'), true)) {
            $errors[] = 'rechazo_previo must be one of N, X or S.';
        }

        $this->throwIfErrors($errors);
    }

    public function validateBulkCreate(BulkInvoiceCreateRequest $request): void
    {
        $errors = array();
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

    public function validateCancel(InvoiceCancelRequest $request): void
    {
        $payload = $request->toArray();
        $errors = $this->collectInvoiceIdentityErrors(
            (string) $payload['serie'],
            (string) $payload['numero'],
            (string) $payload['fecha_expedicion']
        );

        if (isset($payload['rechazo_previo']) && !in_array($payload['rechazo_previo'], array('N', 'X', 'S'), true)) {
            $errors[] = 'rechazo_previo must be one of N, X or S.';
        }

        $this->throwIfErrors($errors);
    }

    public function validateList(InvoiceListRequest $request): void
    {
        $payload = $request->toArray();
        $errors = $this->collectNotBlankErrors(array(
            'ejercicio' => (string) $payload['ejercicio'],
            'periodo' => (string) $payload['periodo'],
        ));

        if (isset($payload['numero']) && !isset($payload['serie'])) {
            $errors[] = 'serie is required when numero is present.';
        }

        if (isset($payload['fecha_expedicion']) && !DateHelper::isValidApiDate((string) $payload['fecha_expedicion'])) {
            $errors[] = 'fecha_expedicion must use the d-m-Y format.';
        }

        $this->throwIfErrors($errors);
    }

    public function validateExport(XmlExportRequest $request): void
    {
        $this->throwIfErrors($this->collectNotBlankErrors($request->toArray()));
    }

    public function validateDownload(XmlDownloadRequest $request): void
    {
        $this->throwIfErrors($this->collectNotBlankErrors(array(
            'numero' => $request->toArray()['numero'],
        )));
    }

    /**
     * @return array<int, string>
     */
    private function validateInvoicePayload(AbstractInvoiceRequest $request): array
    {
        $errors = $this->collectInvoiceIdentityErrors(
            $request->getSeries(),
            $request->getNumber(),
            $request->getIssueDate()
        );

        $errors = array_merge($errors, $this->collectNotBlankErrors(array(
            'tipo_factura' => $request->getInvoiceType(),
            'descripcion' => $request->getDescription(),
            'importe_total' => $request->getTotalAmount(),
        )));

        if (!DateHelper::isToday($request->getIssueDate())) {
            $errors[] = 'fecha_expedicion must match the current date.';
        }

        $lines = $request->getLines();
        if ($lines === array()) {
            $errors[] = 'At least one invoice line is required.';
        }

        if (count($lines) > 12) {
            $errors[] = 'A maximum of 12 invoice lines is allowed.';
        }

        foreach ($lines as $index => $line) {
            foreach ($this->validateLine($line) as $lineError) {
                $errors[] = sprintf('lineas[%d]: %s', $index, $lineError);
            }
        }

        if ($request->getOperationDate() !== null && !DateHelper::isValidApiDate($request->getOperationDate())) {
            $errors[] = 'fecha_operacion must use the d-m-Y format.';
        }

        $invoiceType = strtoupper($request->getInvoiceType());
        $isRectificative = strpos($invoiceType, 'R') === 0;

        if ($isRectificative && $request->getRectificationType() === null) {
            $errors[] = 'tipo_rectificativa is required for rectificative invoices.';
        }

        if ($request->getRectificationType() === 'S' && $request->getRectificationAmounts() === null) {
            $errors[] = 'importe_rectificativa is required when tipo_rectificativa is S.';
        }

        return $errors;
    }

    /**
     * @return array<int, string>
     */
    private function validateLine(InvoiceLine $line): array
    {
        $payload = $line->toArray();
        $errors = $this->collectNotBlankErrors(array(
            'base_imponible' => isset($payload['base_imponible']) ? (string) $payload['base_imponible'] : '',
        ));

        $hasTax = isset($payload['tipo_impositivo']) && isset($payload['cuota_repercutida']);
        $hasSpecialClassification = isset($payload['operacion_exenta']) || isset($payload['calificacion_operacion']);

        if (!$hasTax && !$hasSpecialClassification) {
            $errors[] = 'Either tax values or an exempt/non-subject classification must be provided.';
        }

        return $errors;
    }

    /**
     * @return array<int, string>
     */
    private function collectInvoiceIdentityErrors(string $series, string $number, string $issueDate): array
    {
        $errors = $this->collectNotBlankErrors(array(
            'numero' => $number,
            'fecha_expedicion' => $issueDate,
        ));

        if (!DateHelper::isValidApiDate($issueDate)) {
            $errors[] = 'fecha_expedicion must use the d-m-Y format.';
        }

        return $errors;
    }

    /**
     * @param array<string, string> $values
     *
     * @return array<int, string>
     */
    private function collectNotBlankErrors(array $values): array
    {
        $errors = array();

        foreach ($values as $field => $value) {
            if (trim($value) === '') {
                $errors[] = sprintf('%s cannot be empty.', $field);
            }
        }

        return $errors;
    }

    /**
     * @param array<int, string> $errors
     */
    private function throwIfErrors(array $errors): void
    {
        if ($errors !== array()) {
            throw new ValidationException('The request payload is invalid.', $errors);
        }
    }
}
