<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\Arrayable;

final class InvoiceCancelRequest implements Arrayable
{
    private string $series;
    private string $number;
    private string $issueDate;
    private ?string $previousRejectionStatus;
    private ?string $withoutPreviousRecord;
    private ?string $incidentCode;

    public function __construct(
        string $series,
        string $number,
        string $issueDate,
        ?string $previousRejectionStatus = null,
        ?string $withoutPreviousRecord = null,
        ?string $incidentCode = null
    ) {
        $this->series = $series;
        $this->number = $number;
        $this->issueDate = $issueDate;
        $this->previousRejectionStatus = $previousRejectionStatus;
        $this->withoutPreviousRecord = $withoutPreviousRecord;
        $this->incidentCode = $incidentCode;
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

        if ($this->previousRejectionStatus !== null) {
            $payload['rechazo_previo'] = $this->previousRejectionStatus;
        }

        if ($this->withoutPreviousRecord !== null) {
            $payload['sin_registro_previo'] = $this->withoutPreviousRecord;
        }

        if ($this->incidentCode !== null) {
            $payload['incidencia'] = $this->incidentCode;
        }

        return $payload;
    }
}
