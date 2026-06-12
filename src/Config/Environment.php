<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Config;

final class Environment
{
    public const TEST = 'test';
    public const PRODUCTION = 'production';
    public const CUSTOM = 'custom';

    public static function isValid(string $environment): bool
    {
        return in_array($environment, array(self::TEST, self::PRODUCTION, self::CUSTOM), true);
    }
}
