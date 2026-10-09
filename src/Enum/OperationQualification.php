<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Enum;

/**
 * Values of line field `calificacion_operacion`.
 *
 * Source: Verifacti OpenAPI spec, schema `lineas`, field `calificacion_operacion`.
 */
final class OperationQualification extends AbstractCodeList
{
    /** Subject and not exempt, no reverse charge. */
    public const S1 = 'S1';

    /** Subject and not exempt, reverse charge (rate and quota must be 0). */
    public const S2 = 'S2';

    /** Not subject: art. 7, 14 and others. */
    public const N1 = 'N1';

    /** Not subject because of location rules. */
    public const N2 = 'N2';

    /**
     * {@inheritDoc}
     */
    public static function values(): array
    {
        return [self::S1, self::S2, self::N1, self::N2];
    }
}
