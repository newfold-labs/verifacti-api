<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Service;

use Bluehost\VerifactiApi\Dto\XmlDownloadRequest;
use Bluehost\VerifactiApi\Dto\XmlDownloadResponse;
use Bluehost\VerifactiApi\Dto\XmlExportRequest;
use Bluehost\VerifactiApi\Dto\XmlExportResponse;
use Bluehost\VerifactiApi\Validator\RequestValidator;

final class ExportService
{
    private ApiExecutor $executor;
    private RequestValidator $validator;

    public function __construct(ApiExecutor $executor, RequestValidator $validator)
    {
        $this->executor = $executor;
        $this->validator = $validator;
    }

    public function export(XmlExportRequest $request): XmlExportResponse
    {
        $this->validator->validateExport($request);

        return XmlExportResponse::fromApiResponse(
            $this->executor->post('/verifactu/export', $request->toArray())
        );
    }

    public function download(XmlDownloadRequest $request): XmlDownloadResponse
    {
        $this->validator->validateDownload($request);

        return XmlDownloadResponse::fromApiResponse(
            $this->executor->post('/verifactu/downloadXML', $request->toArray())
        );
    }
}
