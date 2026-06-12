<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\Arrayable;

final class DateRange implements Arrayable
{
    /**
     * @var array<string, mixed>
     */
    private array $fields;

    /**
     * @param array<string, mixed> $fields
     */
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->fields;
    }
}
