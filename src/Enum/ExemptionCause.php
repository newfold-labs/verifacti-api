<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Enum;

/**
 * Values of line field `operacion_exenta`.
 *
 * Source: Verifacti OpenAPI spec, schema `lineas`, field `operacion_exenta`.
 * IVA and IPSI accept E1–E6; IGIC accepts E1–E8.
 */
final class ExemptionCause extends AbstractCodeList
{
    /** Exempt by art. 20 LIVA. */
    public const E1 = 'E1';

    /** Exempt by art. 21 LIVA (exports). */
    public const E2 = 'E2';

    /** Exempt by art. 22 LIVA. */
    public const E3 = 'E3';

    /** Exempt by art. 23 and 24 LIVA. */
    public const E4 = 'E4';

    /** Exempt by art. 25 LIVA (intra-EU supplies). */
    public const E5 = 'E5';

    /** Exempt for other reasons. */
    public const E6 = 'E6';

    /** IGIC only. */
    public const E7 = 'E7';

    /** IGIC only. */
    public const E8 = 'E8';

    /**
     * {@inheritDoc}
     */
    public static function values(): array
    {
        return [self::E1, self::E2, self::E3, self::E4, self::E5, self::E6, self::E7, self::E8];
    }

    /**
     * Whether the exemption cause is allowed for the given tax type.
     *
     * @param string $cause   Exemption cause.
     * @param string $taxType Tax type (`impuesto`).
     *
     * @return bool
     */
    public static function isAllowedFor(string $cause, string $taxType): bool
    {
        if (!self::isValid($cause)) {
            return false;
        }

        if ($taxType === TaxType::IGIC) {
            return true;
        }

        return !in_array($cause, [self::E7, self::E8], true);
    }
}
