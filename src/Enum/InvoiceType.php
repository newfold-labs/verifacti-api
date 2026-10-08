<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Enum;

/**
 * Values of `tipo_factura`.
 *
 * Source: Verifacti OpenAPI spec, POST /verifactu/create, field `tipo_factura`
 * (https://www.verifacti.com/en/docs) and RD 1007/2023 / Orden HAC/1177/2024 (L2 list).
 */
final class InvoiceType extends AbstractCodeList
{
    /** Full invoice (art. 6, 7.2 and 7.3 RD 1619/2012). */
    public const F1 = 'F1';

    /** Simplified invoice (art. 6.1.d RD 1619/2012) and invoices without recipient identification (art. 61.d RD 1624/1992). */
    public const F2 = 'F2';

    /** Invoice issued in substitution of previously declared simplified invoices. */
    public const F3 = 'F3';

    /** Corrective invoice: art. 80.1, 80.2 LIVA and error based on law. */
    public const R1 = 'R1';

    /** Corrective invoice: art. 80.3 LIVA (insolvency proceedings). */
    public const R2 = 'R2';

    /** Corrective invoice: art. 80.4 LIVA (uncollectable debts). */
    public const R3 = 'R3';

    /** Corrective invoice: other causes. */
    public const R4 = 'R4';

    /** Corrective invoice of a simplified invoice (any cause). */
    public const R5 = 'R5';

    /**
     * {@inheritDoc}
     */
    public static function values(): array
    {
        return [self::F1, self::F2, self::F3, self::R1, self::R2, self::R3, self::R4, self::R5];
    }

    /**
     * Whether the type is a corrective (rectificativa) invoice.
     *
     * @param string $type Invoice type code.
     *
     * @return bool
     */
    public static function isCorrective(string $type): bool
    {
        return in_array($type, [self::R1, self::R2, self::R3, self::R4, self::R5], true);
    }

    /**
     * Whether the type must NOT carry recipient data (`nif`, `nombre`, `id_otro`).
     *
     * Source: Verifacti error code `vf-verifactu-destinatario_no_aplica`.
     *
     * @param string $type Invoice type code.
     *
     * @return bool
     */
    public static function forbidsRecipient(string $type): bool
    {
        return in_array($type, [self::F2, self::R5], true);
    }

    /**
     * Whether the type requires recipient data (`nombre` plus `nif` or `id_otro`).
     *
     * @param string $type Invoice type code.
     *
     * @return bool
     */
    public static function requiresRecipient(string $type): bool
    {
        return in_array($type, [self::F1, self::F3, self::R1, self::R2, self::R3, self::R4], true);
    }
}
