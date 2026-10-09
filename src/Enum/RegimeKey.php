<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Enum;

/**
 * Values of line field `clave_regimen`. When omitted the API assumes `01`.
 *
 * Source: Verifacti OpenAPI spec, schema `lineas`, field `clave_regimen`.
 * UNVERIFIED: `21` appears in the field description (IGIC simplified regime)
 * and in error `vf-verifactu-clave_regimen_impuesto_03` but not in the enum.
 */
final class RegimeKey extends AbstractCodeList
{
    public const GENERAL = '01';
    public const EXPORT = '02';
    public const USED_GOODS = '03';
    public const INVESTMENT_GOLD = '04';
    public const TRAVEL_AGENCIES = '05';
    public const ENTITY_GROUP = '06';
    public const CASH_BASIS = '07';
    public const IPSI_IGIC_OPERATIONS = '08';
    public const TRAVEL_AGENCIES_INTERMEDIARY = '09';
    public const THIRD_PARTY_COLLECTIONS = '10';
    public const BUSINESS_PREMISES_LEASE = '11';
    public const PENDING_ACCRUAL_PUBLIC_WORKS = '14';
    public const PENDING_ACCRUAL_SUCCESSIVE = '15';
    public const OSS_IOSS = '17';
    public const EQUIVALENCE_SURCHARGE = '18';
    public const REAGYP = '19';
    public const SIMPLIFIED = '20';
    public const IGIC_SIMPLIFIED = '21';

    /**
     * Keys for which the API skips the lines-vs-total consistency check.
     *
     * Source: Verifacti error catalogue, `vf-verifactu-importe_total`.
     */
    public const SKIP_TOTAL_CHECK = [self::USED_GOODS, self::TRAVEL_AGENCIES, self::ENTITY_GROUP, self::IPSI_IGIC_OPERATIONS, self::TRAVEL_AGENCIES_INTERMEDIARY];

    /**
     * {@inheritDoc}
     */
    public static function values(): array
    {
        return [
            self::GENERAL, self::EXPORT, self::USED_GOODS, self::INVESTMENT_GOLD, self::TRAVEL_AGENCIES,
            self::ENTITY_GROUP, self::CASH_BASIS, self::IPSI_IGIC_OPERATIONS, self::TRAVEL_AGENCIES_INTERMEDIARY,
            self::THIRD_PARTY_COLLECTIONS, self::BUSINESS_PREMISES_LEASE, self::PENDING_ACCRUAL_PUBLIC_WORKS,
            self::PENDING_ACCRUAL_SUCCESSIVE, self::OSS_IOSS, self::EQUIVALENCE_SURCHARGE, self::REAGYP,
            self::SIMPLIFIED, self::IGIC_SIMPLIFIED,
        ];
    }
}
