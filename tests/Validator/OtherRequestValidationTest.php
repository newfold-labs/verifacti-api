<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Tests\Validator;

use Bluehost\VerifactiApi\Dto\BulkInvoiceCreateRequest;
use Bluehost\VerifactiApi\Dto\InvoiceCreateRequest;
use Bluehost\VerifactiApi\Dto\InvoiceLine;
use Bluehost\VerifactiApi\Dto\InvoiceListRequest;
use Bluehost\VerifactiApi\Dto\InvoiceStatusLookupRequest;
use Bluehost\VerifactiApi\Dto\RecordStatusLookupRequest;
use Bluehost\VerifactiApi\Dto\XmlDownloadRequest;
use Bluehost\VerifactiApi\Dto\XmlExportRequest;
use Bluehost\VerifactiApi\Exception\ValidationException;
use Bluehost\VerifactiApi\Validator\RequestValidator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class OtherRequestValidationTest extends TestCase
{
    private RequestValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new RequestValidator(clock: new DateTimeImmutable('2026-10-08'));
    }

    public function testBulkLimitsAndPrefixesErrors(): void
    {
        $errors = $this->errors(fn () => $this->validator->validateBulkCreate(new BulkInvoiceCreateRequest([])));
        self::assertContains('At least one invoice is required for create_bulk.', $errors);

        $invoices = array_fill(0, 51, $this->invoice('F1'));
        $errors = $this->errors(fn () => $this->validator->validateBulkCreate(new BulkInvoiceCreateRequest($invoices)));
        self::assertContains('create_bulk supports at most 50 invoices per request.', $errors);

        $errors = $this->errors(fn () => $this->validator->validateBulkCreate(new BulkInvoiceCreateRequest([$this->invoice('F9')])));
        self::assertContains('Invoice 1: tipo_factura must be one of F1, F2, F3, R1, R2, R3, R4, R5.', $errors);
    }

    public function testStatusLookups(): void
    {
        $errors = $this->errors(fn () => $this->validator->validateRecordStatusLookup(new RecordStatusLookupRequest(' ')));
        self::assertContains('uuid cannot be empty.', $errors);

        $errors = $this->errors(fn () => $this->validator->validateInvoiceStatusLookup(new InvoiceStatusLookupRequest('A', '1', '2026-10-08', '1/10/2026')));
        self::assertContains('fecha_expedicion must use the d-m-Y format.', $errors);
        self::assertContains('fecha_operacion must use the d-m-Y format.', $errors);
    }

    public function testListExportDownload(): void
    {
        $errors = $this->errors(fn () => $this->validator->validateList(new InvoiceListRequest('', '', null, '1', null, '8/10/2026')));
        self::assertContains('ejercicio cannot be empty.', $errors);
        self::assertContains('serie is required when numero is present.', $errors);
        self::assertContains('fecha_expedicion must use the d-m-Y format.', $errors);

        $errors = $this->errors(fn () => $this->validator->validateExport(new XmlExportRequest('2026', '')));
        self::assertContains('periodo cannot be empty.', $errors);

        $errors = $this->errors(fn () => $this->validator->validateDownload(new XmlDownloadRequest('A', '')));
        self::assertContains('numero cannot be empty.', $errors);
    }

    /**
     * @return array<int, string>
     */
    private function errors(callable $callback): array
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            return $exception->getErrors();
        }

        return [];
    }

    private function invoice(string $type): InvoiceCreateRequest
    {
        return new InvoiceCreateRequest('A', '1', '08-10-2026', $type, 'Desc', [new InvoiceLine('100', '21', '21')], '121', null, 'A15022510', null, 'Customer');
    }
}
