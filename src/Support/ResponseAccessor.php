<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Support;

final class ResponseAccessor
{
    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $paths
     * @param mixed $default
     *
     * @return mixed
     */
    public static function first(array $data, array $paths, $default = null)
    {
        foreach ($paths as $path) {
            $value = self::get($data, $path);
            if ($value !== null) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * @param array<string, mixed> $data
     * @param mixed $default
     *
     * @return mixed
     */
    public static function get(array $data, string $path, $default = null)
    {
        $segments = explode('.', $path);
        $value = $data;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
