<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\Arrayable;

final class InvoiceStatusLookupRequest implements Arrayable
{
    private string $series;
    private string $number;
    private string $issueDate;
    private ?string $operationDate;

    public function __construct(string $series, string $number, string $issueDate, ?string $operationDate = null)
    {
        $this->series = $series;
        $this->number = $number;
        $this->issueDate = $issueDate;
        $this->operationDate = $operationDate;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = array(
            'serie' => $this->series,
            'numero' => $this->number,
            'fecha_expedicion' => $this->issueDate,
        );

        if ($this->operationDate !== null) {
            $payload['fecha_operacion'] = $this->operationDate;
        }

        return $payload;
    }
}
