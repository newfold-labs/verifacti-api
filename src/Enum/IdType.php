<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Enum;

/**
 * Values of `id_otro.id_type`.
 *
 * Source: Verifacti OpenAPI spec, schema `id_otro`.
 */
final class IdType extends AbstractCodeList
{
    /** VAT identification number (NIF-IVA). `codigo_pais` optional. */
    public const VAT = '02';
    public const PASSPORT = '03';
    public const COUNTRY_OF_RESIDENCE_ID = '04';
    public const RESIDENCE_CERTIFICATE = '05';
    public const OTHER_DOCUMENT = '06';
    public const NOT_REGISTERED = '07';

    /**
     * {@inheritDoc}
     */
    public static function values(): array
    {
        return [self::VAT, self::PASSPORT, self::COUNTRY_OF_RESIDENCE_ID, self::RESIDENCE_CERTIFICATE, self::OTHER_DOCUMENT, self::NOT_REGISTERED];
    }
}
