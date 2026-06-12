<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Service;

use Bluehost\VerifactiApi\Dto\BulkCreateResponse;
use Bluehost\VerifactiApi\Dto\BulkInvoiceCreateRequest;
use Bluehost\VerifactiApi\Dto\InvoiceCancelRequest;
use Bluehost\VerifactiApi\Dto\InvoiceCreateRequest;
use Bluehost\VerifactiApi\Dto\InvoiceListRequest;
use Bluehost\VerifactiApi\Dto\InvoiceListResponse;
use Bluehost\VerifactiApi\Dto\InvoiceModifyRequest;
use Bluehost\VerifactiApi\Dto\InvoiceOperationResponse;
use Bluehost\VerifactiApi\Support\HttpHeaderHelper;
use Bluehost\VerifactiApi\Validator\RequestValidator;

final class InvoiceService
{
    private ApiExecutor $executor;
    private RequestValidator $validator;

    public function __construct(ApiExecutor $executor, RequestValidator $validator)
    {
        $this->executor = $executor;
        $this->validator = $validator;
    }

    public function create(InvoiceCreateRequest $request, ?string $idempotencyKey = null): InvoiceOperationResponse
    {
        $this->validator->validateCreate($request);

        $headers = array();
        if ($idempotencyKey !== null && trim($idempotencyKey) !== '') {
            $normalizedIdempotencyKey = trim($idempotencyKey);
            HttpHeaderHelper::assertSafeRequestHeaderValue($normalizedIdempotencyKey, 'Idempotency-Key');
            $headers['Idempotency-Key'] = $normalizedIdempotencyKey;
        }

        return InvoiceOperationResponse::fromApiResponse(
            $this->executor->post('/verifactu/create', $request->toArray(), $headers)
        );
    }

    public function createBulk(BulkInvoiceCreateRequest $request): BulkCreateResponse
    {
        $this->validator->validateBulkCreate($request);

        return BulkCreateResponse::fromApiResponse(
            $this->executor->post('/verifactu/create_bulk', $request->toArray())
        );
    }

    public function modify(InvoiceModifyRequest $request): InvoiceOperationResponse
    {
        $this->validator->validateModify($request);

        return InvoiceOperationResponse::fromApiResponse(
            $this->executor->put('/verifactu/modify', $request->toArray())
        );
    }

    public function cancel(InvoiceCancelRequest $request): InvoiceOperationResponse
    {
        $this->validator->validateCancel($request);

        return InvoiceOperationResponse::fromApiResponse(
            $this->executor->post('/verifactu/cancel', $request->toArray())
        );
    }

    public function list(InvoiceListRequest $request): InvoiceListResponse
    {
        $this->validator->validateList($request);

        return InvoiceListResponse::fromApiResponse(
            $this->executor->post('/verifactu/list', $request->toArray())
        );
    }
}
