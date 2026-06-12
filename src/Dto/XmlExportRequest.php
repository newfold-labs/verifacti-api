<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\Arrayable;

final class XmlExportRequest implements Arrayable
{
    private string $fiscalYear;
    private string $period;
    private ?string $token;

    public function __construct(string $fiscalYear, string $period, ?string $token = null)
    {
        $this->fiscalYear = $fiscalYear;
        $this->period = $period;
        $this->token = $token;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = array(
            'ejercicio' => $this->fiscalYear,
            'periodo' => $this->period,
        );

        if ($this->token !== null) {
            $payload['token'] = $this->token;
        }

        return $payload;
    }
}
