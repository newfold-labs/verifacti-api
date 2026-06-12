<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\Arrayable;

final class InvoiceListRequest implements Arrayable
{
    private string $fiscalYear;
    private string $period;
    private ?string $series;
    private ?string $number;
    private ?DateRange $issueDateRange;
    private ?string $issueDate;
    private ?Pagination $pagination;

    /**
     * @var array<string, mixed>
     */
    private array $extraFields;

    /**
     * @param array<string, mixed> $extraFields
     */
    public function __construct(
        string $fiscalYear,
        string $period,
        ?string $series = null,
        ?string $number = null,
        ?DateRange $issueDateRange = null,
        ?string $issueDate = null,
        ?Pagination $pagination = null,
        array $extraFields = array()
    ) {
        $this->fiscalYear = $fiscalYear;
        $this->period = $period;
        $this->series = $series;
        $this->number = $number;
        $this->issueDateRange = $issueDateRange;
        $this->issueDate = $issueDate;
        $this->pagination = $pagination;
        $this->extraFields = $extraFields;
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

        if ($this->series !== null) {
            $payload['serie'] = $this->series;
        }

        if ($this->number !== null) {
            $payload['numero'] = $this->number;
        }

        if ($this->issueDateRange !== null) {
            $payload['rango_fecha_expedicion'] = $this->issueDateRange->toArray();
        }

        if ($this->issueDate !== null) {
            $payload['fecha_expedicion'] = $this->issueDate;
        }

        if ($this->pagination !== null) {
            $payload['paginacion'] = $this->pagination->toArray();
        }

        return array_merge($payload, $this->extraFields);
    }
}
