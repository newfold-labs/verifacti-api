<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\Arrayable;

/**
 * Rectified base and tax amounts for corrective invoices.
 */
final class CorrectiveAmounts implements Arrayable
{
    /**
     * @param string $baseRectificada  Rectified taxable base.
     * @param string $cuotaRectificada Rectified tax amount.
     * @param string|null $cuotaRecargoRectificada Rectified equivalence surcharge amount.
     */
    public function __construct(
        private string $baseRectificada,
        private string $cuotaRectificada,
        private ?string $cuotaRecargoRectificada = null
    ) {
    }

    /**
     * Return the rectified taxable base.
     *
     * @return string
     */
    public function getBaseRectificada(): string
    {
        return $this->baseRectificada;
    }

    /**
     * Return the rectified tax amount.
     *
     * @return string
     */
    public function getCuotaRectificada(): string
    {
        return $this->cuotaRectificada;
    }

    /**
     * Return the rectified equivalence surcharge amount, if set.
     *
     * @return string|null
     */
    public function getCuotaRecargoRectificada(): ?string
    {
        return $this->cuotaRecargoRectificada;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $payload = [
            'base_rectificada' => $this->baseRectificada,
            'cuota_rectificada' => $this->cuotaRectificada,
        ];

        if ($this->cuotaRecargoRectificada !== null) {
            $payload['cuota_recargo_rectificada'] = $this->cuotaRecargoRectificada;
        }

        return $payload;
    }
}
