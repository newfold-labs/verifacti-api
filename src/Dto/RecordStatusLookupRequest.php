<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Dto;

use Bluehost\VerifactiApi\Support\Arrayable;

final class RecordStatusLookupRequest implements Arrayable
{
    private string $uuid;

    /**
     * @var array<string, string>
     */
    private array $query;

    /**
     * @param array<string, string> $query
     */
    public function __construct(string $uuid, array $query = array())
    {
        $this->uuid = $uuid;
        $this->query = $query;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_merge(array('uuid' => $this->uuid), $this->query);
    }
}
