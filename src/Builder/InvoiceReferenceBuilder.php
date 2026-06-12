<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Builder;

use Bluehost\VerifactiApi\Dto\InvoiceReference;

final class InvoiceReferenceBuilder
{
    private string $series = '';
    private string $number = '';
    private string $issueDate = '';

    public function withSeries(string $series): self
    {
        $this->series = $series;

        return $this;
    }

    public function withNumber(string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function withIssueDate(string $issueDate): self
    {
        $this->issueDate = $issueDate;

        return $this;
    }

    public function build(): InvoiceReference
    {
        return new InvoiceReference($this->series, $this->number, $this->issueDate);
    }
}
