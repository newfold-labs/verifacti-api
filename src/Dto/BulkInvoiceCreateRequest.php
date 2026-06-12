<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\Arrayable;

final class BulkInvoiceCreateRequest implements Arrayable
{
    /**
     * @var array<int, InvoiceCreateRequest>
     */
    private array $invoices;

    private ?string $wrapperKey;

    /**
     * @param array<int, InvoiceCreateRequest> $invoices
     */
    public function __construct(array $invoices, ?string $wrapperKey = 'facturas')
    {
        $this->invoices = $invoices;
        $this->wrapperKey = $wrapperKey;
    }

    /**
     * @return array<int, InvoiceCreateRequest>
     */
    public function getInvoices(): array
    {
        return $this->invoices;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = array_map(static function (InvoiceCreateRequest $invoice): array {
            return $invoice->toArray();
        }, $this->invoices);

        if ($this->wrapperKey === null || $this->wrapperKey === '') {
            return $payload;
        }

        return array(
            $this->wrapperKey => $payload,
        );
    }
}
