<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Enum;

/**
 * Values of `tipo_rectificativa`.
 *
 * Source: Verifacti OpenAPI spec, field `tipo_rectificativa`; examples page
 * section "Facturas rectificativas".
 */
final class RectificationType extends AbstractCodeList
{
    /** By substitution: requires `importe_rectificativa`. */
    public const SUBSTITUTION = 'S';

    /** By differences: signed deltas, `importe_rectificativa` not allowed. */
    public const DIFFERENCES = 'I';

    /**
     * {@inheritDoc}
     */
    public static function values(): array
    {
        return [self::SUBSTITUTION, self::DIFFERENCES];
    }
}
