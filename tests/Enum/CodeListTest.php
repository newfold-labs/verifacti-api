<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Tests\Enum;

use Bluehost\VerifactiApi\Enum\ExemptionCause;
use Bluehost\VerifactiApi\Enum\IdType;
use Bluehost\VerifactiApi\Enum\InvoiceType;
use Bluehost\VerifactiApi\Enum\OperationQualification;
use Bluehost\VerifactiApi\Enum\PreviousRejection;
use Bluehost\VerifactiApi\Enum\RectificationType;
use Bluehost\VerifactiApi\Enum\RegimeKey;
use Bluehost\VerifactiApi\Enum\TaxType;
use PHPUnit\Framework\TestCase;

final class CodeListTest extends TestCase
{
    public function testDocumentedValues(): void
    {
        self::assertSame(['F1', 'F2', 'F3', 'R1', 'R2', 'R3', 'R4', 'R5'], InvoiceType::values());
        self::assertSame(['S', 'I'], RectificationType::values());
        self::assertSame(['01', '02', '03', '05'], TaxType::values());
        self::assertSame(['S1', 'S2', 'N1', 'N2'], OperationQualification::values());
        self::assertSame(['02', '03', '04', '05', '06', '07'], IdType::values());
        self::assertSame(['N', 'X', 'S'], PreviousRejection::values());
        self::assertCount(8, ExemptionCause::values());
        self::assertCount(18, RegimeKey::values());
    }

    public function testIsValidIsStrict(): void
    {
        self::assertTrue(TaxType::isValid('03'));
        self::assertFalse(TaxType::isValid('3'));
        self::assertFalse(TaxType::isValid(null));
        self::assertFalse(InvoiceType::isValid('f1'));
    }

    public function testInvoiceTypeHelpers(): void
    {
        self::assertTrue(InvoiceType::isCorrective('R5'));
        self::assertFalse(InvoiceType::isCorrective('F3'));
        self::assertTrue(InvoiceType::forbidsRecipient('F2'));
        self::assertTrue(InvoiceType::forbidsRecipient('R5'));
        self::assertFalse(InvoiceType::forbidsRecipient('R1'));
        self::assertTrue(InvoiceType::requiresRecipient('F3'));
        self::assertFalse(InvoiceType::requiresRecipient('F2'));
    }

    public function testExemptionCausesPerTax(): void
    {
        self::assertTrue(ExemptionCause::isAllowedFor('E8', TaxType::IGIC));
        self::assertFalse(ExemptionCause::isAllowedFor('E8', TaxType::IPSI));
        self::assertFalse(ExemptionCause::isAllowedFor('E7', TaxType::IVA));
        self::assertTrue(ExemptionCause::isAllowedFor('E5', TaxType::IVA));
        self::assertFalse(ExemptionCause::isAllowedFor('E9', TaxType::IGIC));
    }
}
