<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Client;

use Bluehost\VerifactiApi\Config\VerifactiConfig;
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
use Bluehost\VerifactiApi\Serializer\JsonSerializer;
use Bluehost\VerifactiApi\Service\ApiExecutor;
use Bluehost\VerifactiApi\Service\DeclarationService;
use Bluehost\VerifactiApi\Service\ExportService;
use Bluehost\VerifactiApi\Service\HealthService;
use Bluehost\VerifactiApi\Service\InvoiceService;
use Bluehost\VerifactiApi\Service\StatusService;
use Bluehost\VerifactiApi\Transport\HttpTransportInterface;
use Bluehost\VerifactiApi\Validator\RequestValidator;

final class VerifactiClient implements ClientInterface
{
    private VerifactiConfig $config;
    private HealthService $healthService;
    private StatusService $statusService;
    private InvoiceService $invoiceService;
    private ExportService $exportService;
    private DeclarationService $declarationService;

    public function __construct(VerifactiConfig $config, HttpTransportInterface $transport)
    {
        $this->config = $config;

        $validator = new RequestValidator();
        $executor = new ApiExecutor($config, $transport, new JsonSerializer());

        $this->healthService = new HealthService($executor);
        $this->statusService = new StatusService($executor, $validator);
        $this->invoiceService = new InvoiceService($executor, $validator);
        $this->exportService = new ExportService($executor, $validator);
        $this->declarationService = new DeclarationService($executor);
    }

    public function getConfig(): VerifactiConfig
    {
        return $this->config;
    }

    public function healthCheck(): HealthResponse
    {
        return $this->healthService->check();
    }

    public function getRecordStatus(RecordStatusLookupRequest $request): StatusResponse
    {
        return $this->statusService->getRecordStatus($request);
    }

    public function getInvoiceStatus(InvoiceStatusLookupRequest $request): StatusResponse
    {
        return $this->statusService->getInvoiceStatus($request);
    }

    public function createInvoice(InvoiceCreateRequest $request, ?string $idempotencyKey = null): InvoiceOperationResponse
    {
        return $this->invoiceService->create($request, $idempotencyKey);
    }

    public function createBulkInvoices(BulkInvoiceCreateRequest $request): BulkCreateResponse
    {
        return $this->invoiceService->createBulk($request);
    }

    public function modifyInvoice(InvoiceModifyRequest $request): InvoiceOperationResponse
    {
        return $this->invoiceService->modify($request);
    }

    public function cancelInvoice(InvoiceCancelRequest $request): InvoiceOperationResponse
    {
        return $this->invoiceService->cancel($request);
    }

    public function listInvoices(InvoiceListRequest $request): InvoiceListResponse
    {
        return $this->invoiceService->list($request);
    }

    public function exportXml(XmlExportRequest $request): XmlExportResponse
    {
        return $this->exportService->export($request);
    }

    public function downloadXml(XmlDownloadRequest $request): XmlDownloadResponse
    {
        return $this->exportService->download($request);
    }

    public function fetchDeclaration(): DeclarationResponse
    {
        return $this->declarationService->fetch();
    }

    public function health(): HealthService
    {
        return $this->healthService;
    }

    public function status(): StatusService
    {
        return $this->statusService;
    }

    public function invoices(): InvoiceService
    {
        return $this->invoiceService;
    }

    public function exports(): ExportService
    {
        return $this->exportService;
    }

    public function declaration(): DeclarationService
    {
        return $this->declarationService;
    }
}
