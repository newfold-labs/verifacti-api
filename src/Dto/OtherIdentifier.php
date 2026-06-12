<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\Arrayable;

final class OtherIdentifier implements Arrayable
{
    private string $countryCode;
    private string $idType;
    private string $identifier;

    public function __construct(string $countryCode, string $idType, string $identifier)
    {
        $this->countryCode = $countryCode;
        $this->idType = $idType;
        $this->identifier = $identifier;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array(
            'codigo_pais' => $this->countryCode,
            'id_type' => $this->idType,
            'id' => $this->identifier,
        );
    }
}
