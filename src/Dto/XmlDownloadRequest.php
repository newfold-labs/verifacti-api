<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\Arrayable;

final class XmlDownloadRequest implements Arrayable
{
    private string $series;
    private string $number;

    public function __construct(string $series, string $number)
    {
        $this->series = $series;
        $this->number = $number;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array(
            'serie' => $this->series,
            'numero' => $this->number,
        );
    }
}
