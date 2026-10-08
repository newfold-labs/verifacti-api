<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Enum;

/**
 * Values of `rechazo_previo` (modify and cancel).
 *
 * Source: Verifacti OpenAPI spec, PUT /verifactu/modify and POST /verifactu/cancel.
 */
final class PreviousRejection extends AbstractCodeList
{
    /** Original record accepted (default). */
    public const NONE = 'N';

    /** Original record rejected. */
    public const REJECTED = 'X';

    /** A previous amendment was rejected. */
    public const AMENDMENT_REJECTED = 'S';

    /**
     * {@inheritDoc}
     */
    public static function values(): array
    {
        return [self::NONE, self::REJECTED, self::AMENDMENT_REJECTED];
    }
}
