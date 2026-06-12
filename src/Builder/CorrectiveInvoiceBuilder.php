<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Builder;

use Bluehost\VerifactiApi\Dto\CorrectiveAmounts;
use Bluehost\VerifactiApi\Dto\InvoiceReference;

final class CorrectiveInvoiceBuilder
{
    private ?string $rectificationType = null;
    private ?CorrectiveAmounts $rectificationAmounts = null;

    /**
     * @var array<int, InvoiceReference>
     */
    private array $rectifiedInvoices = array();

    /**
     * @var array<int, InvoiceReference>
     */
    private array $replacedInvoices = array();

    public function bySubstitution(): self
    {
        $this->rectificationType = 'S';

        return $this;
    }

    public function byDifference(): self
    {
        $this->rectificationType = 'I';

        return $this;
    }

    public function withRectifiedAmounts(string $baseRectificada, string $cuotaRectificada): self
    {
        $this->rectificationAmounts = new CorrectiveAmounts($baseRectificada, $cuotaRectificada);

        return $this;
    }

    public function addCorrectedInvoice(InvoiceReference $reference): self
    {
        $this->rectifiedInvoices[] = $reference;

        return $this;
    }

    public function addReplacedInvoice(InvoiceReference $reference): self
    {
        $this->replacedInvoices[] = $reference;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $payload = array();

        if ($this->rectificationType !== null) {
            $payload['tipo_rectificativa'] = $this->rectificationType;
        }

        if ($this->rectificationAmounts !== null) {
            $payload['importe_rectificativa'] = $this->rectificationAmounts;
        }

        if ($this->rectifiedInvoices !== array()) {
            $payload['facturas_rectificadas'] = $this->rectifiedInvoices;
        }

        if ($this->replacedInvoices !== array()) {
            $payload['facturas_sustituidas'] = $this->replacedInvoices;
        }

        return $payload;
    }
}
