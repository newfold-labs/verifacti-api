<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Service;

use Bluehost\VerifactiApi\Dto\HealthResponse;

final class HealthService
{
    private ApiExecutor $executor;

    public function __construct(ApiExecutor $executor)
    {
        $this->executor = $executor;
    }

    public function check(): HealthResponse
    {
        return HealthResponse::fromApiResponse($this->executor->get('/verifactu/health'));
    }
}
