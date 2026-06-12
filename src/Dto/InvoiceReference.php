<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\Arrayable;

final class InvoiceReference implements Arrayable
{
    private string $series;
    private string $number;
    private string $issueDate;

    public function __construct(string $series, string $number, string $issueDate)
    {
        $this->series = $series;
        $this->number = $number;
        $this->issueDate = $issueDate;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array(
            'serie' => $this->series,
            'numero' => $this->number,
            'fecha_expedicion' => $this->issueDate,
        );
    }
}
