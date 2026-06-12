<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Serializer;

use Bluehost\VerifactiApi\Exception\SerializationException;
use JsonException;

final class JsonSerializer
{
    /**
     * @param array<string, mixed> $payload
     */
    public function encode(array $payload): string
    {
        try {
            return json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new SerializationException('Unable to encode the request payload as JSON.', '', 0, $exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function decode(string $payload): array
    {
        if (trim($payload) === '') {
            return array();
        }

        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new SerializationException('Unable to decode the response JSON payload.', $payload, 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new SerializationException('Expected a JSON object or array in the response.', $payload);
        }

        return $decoded;
    }
}
