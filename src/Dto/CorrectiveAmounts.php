<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\Arrayable;

final class CorrectiveAmounts implements Arrayable
{
    private string $baseRectificada;
    private string $cuotaRectificada;

    public function __construct(string $baseRectificada, string $cuotaRectificada)
    {
        $this->baseRectificada = $baseRectificada;
        $this->cuotaRectificada = $cuotaRectificada;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array(
            'base_rectificada' => $this->baseRectificada,
            'cuota_rectificada' => $this->cuotaRectificada,
        );
    }
}
