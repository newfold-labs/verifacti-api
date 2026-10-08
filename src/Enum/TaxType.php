<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Enum;

/**
 * Values of line field `impuesto`. When omitted the API assumes `01` (IVA).
 *
 * Source: Verifacti OpenAPI spec, schema `lineas`, field `impuesto`; examples
 * "Factura IGIC" and "Factura IPSI".
 */
final class TaxType extends AbstractCodeList
{
    /** Impuesto sobre el Valor Añadido. */
    public const IVA = '01';

    /** Impuesto sobre la Producción, los Servicios y la Importación (Ceuta y Melilla). */
    public const IPSI = '02';

    /** Impuesto General Indirecto Canario. */
    public const IGIC = '03';

    /** Other taxes. */
    public const OTHER = '05';

    /**
     * {@inheritDoc}
     */
    public static function values(): array
    {
        return [self::IVA, self::IPSI, self::IGIC, self::OTHER];
    }
}
