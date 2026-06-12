<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Builder;

use Bluehost\VerifactiApi\Dto\SpecialInvoiceData;

final class SpecialInvoiceDataBuilder
{
    /**
     * @var array<string, mixed>
     */
    private array $fields = array();

    /**
     * @param mixed $value
     */
    public function withField(string $key, $value): self
    {
        $this->fields[$key] = $value;

        return $this;
    }

    public function build(): SpecialInvoiceData
    {
        return new SpecialInvoiceData($this->fields);
    }
}
