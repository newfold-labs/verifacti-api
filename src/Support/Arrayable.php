<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Support;

interface Arrayable
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
