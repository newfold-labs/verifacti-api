<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Client;

use Bluehost\VerifactiApi\Dto\BulkCreateResponse;
use Bluehost\VerifactiApi\Dto\BulkInvoiceCreateRequest;
use Bluehost\VerifactiApi\Dto\DeclarationResponse;
use Bluehost\VerifactiApi\Dto\HealthResponse;
use Bluehost\VerifactiApi\Dto\InvoiceCancelRequest;
use Bluehost\VerifactiApi\Dto\InvoiceCreateRequest;
use Bluehost\VerifactiApi\Dto\InvoiceListRequest;
use Bluehost\VerifactiApi\Dto\InvoiceListResponse;
use Bluehost\VerifactiApi\Dto\InvoiceModifyRequest;
use Bluehost\VerifactiApi\Dto\InvoiceOperationResponse;
use Bluehost\VerifactiApi\Dto\InvoiceStatusLookupRequest;
use Bluehost\VerifactiApi\Dto\RecordStatusLookupRequest;
use Bluehost\VerifactiApi\Dto\StatusResponse;
use Bluehost\VerifactiApi\Dto\XmlDownloadRequest;
use Bluehost\VerifactiApi\Dto\XmlDownloadResponse;
use Bluehost\VerifactiApi\Dto\XmlExportRequest;
use Bluehost\VerifactiApi\Dto\XmlExportResponse;
use Bluehost\VerifactiApi\Service\DeclarationService;
use Bluehost\VerifactiApi\Service\ExportService;
use Bluehost\VerifactiApi\Service\HealthService;
use Bluehost\VerifactiApi\Service\InvoiceService;
use Bluehost\VerifactiApi\Service\StatusService;

interface ClientInterface
{
    public function healthCheck(): HealthResponse;

    public function getRecordStatus(RecordStatusLookupRequest $request): StatusResponse;

    public function getInvoiceStatus(InvoiceStatusLookupRequest $request): StatusResponse;

    public function createInvoice(InvoiceCreateRequest $request, ?string $idempotencyKey = null): InvoiceOperationResponse;

    public function createBulkInvoices(BulkInvoiceCreateRequest $request): BulkCreateResponse;

    public function modifyInvoice(InvoiceModifyRequest $request): InvoiceOperationResponse;

    public function cancelInvoice(InvoiceCancelRequest $request): InvoiceOperationResponse;

    public function listInvoices(InvoiceListRequest $request): InvoiceListResponse;

    public function exportXml(XmlExportRequest $request): XmlExportResponse;

    public function downloadXml(XmlDownloadRequest $request): XmlDownloadResponse;

    public function fetchDeclaration(): DeclarationResponse;

    public function health(): HealthService;

    public function status(): StatusService;

    public function invoices(): InvoiceService;

    public function exports(): ExportService;

    public function declaration(): DeclarationService;
}
