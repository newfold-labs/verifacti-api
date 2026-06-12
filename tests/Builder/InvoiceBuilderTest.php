<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Tests\Builder;

use Bluehost\VerifactiApi\Builder\CorrectiveInvoiceBuilder;
use Bluehost\VerifactiApi\Builder\InvoiceBuilder;
use Bluehost\VerifactiApi\Builder\InvoiceLineBuilder;
use Bluehost\VerifactiApi\Builder\InvoiceReferenceBuilder;
use PHPUnit\Framework\TestCase;

final class InvoiceBuilderTest extends TestCase
{
    public function testBuildCreateRequestProducesExpectedPayload(): void
    {
        $line = (new InvoiceLineBuilder())
            ->withTaxableBase('200')
            ->withTax('21', '42')
            ->build();

        $corrective = (new CorrectiveInvoiceBuilder())
            ->bySubstitution()
            ->withRectifiedAmounts('0', '0')
            ->addCorrectedInvoice(
                (new InvoiceReferenceBuilder())
                    ->withSeries('A')
                    ->withNumber('1')
                    ->withIssueDate('07-04-2025')
                    ->build()
            );

        $request = (new InvoiceBuilder())
            ->withSeries('RECTIFICATIVA')
            ->withNumber('2')
            ->withIssueDate('19-05-2026')
            ->withOperationDate('01-04-2025')
            ->withInvoiceType('R1')
            ->withDescription('Correction')
            ->withRecipient('A15022510', 'Test customer')
            ->addLine($line)
            ->withTotalAmount('242')
            ->withCorrectiveData($corrective)
            ->buildCreate();

        $payload = $request->toArray();

        self::assertSame('RECTIFICATIVA', $payload['serie']);
        self::assertSame('R1', $payload['tipo_factura']);
        self::assertSame('S', $payload['tipo_rectificativa']);
        self::assertCount(1, $payload['lineas']);
        self::assertCount(1, $payload['facturas_rectificadas']);
    }
}
