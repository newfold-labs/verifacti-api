<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Service;

use Bluehost\VerifactiApi\Dto\DeclarationResponse;

final class DeclarationService
{
    private ApiExecutor $executor;

    public function __construct(ApiExecutor $executor)
    {
        $this->executor = $executor;
    }

    public function fetch(): DeclarationResponse
    {
        return DeclarationResponse::fromApiResponse($this->executor->get('/verifactu/declaracion'));
    }
}
