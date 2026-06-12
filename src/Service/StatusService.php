<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Service;

use Bluehost\VerifactiApi\Dto\InvoiceStatusLookupRequest;
use Bluehost\VerifactiApi\Dto\RecordStatusLookupRequest;
use Bluehost\VerifactiApi\Dto\StatusResponse;
use Bluehost\VerifactiApi\Validator\RequestValidator;

final class StatusService
{
    private ApiExecutor $executor;
    private RequestValidator $validator;

    public function __construct(ApiExecutor $executor, RequestValidator $validator)
    {
        $this->executor = $executor;
        $this->validator = $validator;
    }

    public function getRecordStatus(RecordStatusLookupRequest $request): StatusResponse
    {
        $this->validator->validateRecordStatusLookup($request);

        return StatusResponse::fromApiResponse($this->executor->get('/verifactu/status', $request->toArray()));
    }

    public function getInvoiceStatus(InvoiceStatusLookupRequest $request): StatusResponse
    {
        $this->validator->validateInvoiceStatusLookup($request);

        return StatusResponse::fromApiResponse($this->executor->post('/verifactu/status', $request->toArray()));
    }
}
