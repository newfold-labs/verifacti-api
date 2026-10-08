<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Tests\Dto;

use Bluehost\VerifactiApi\Builder\CorrectiveInvoiceBuilder;
use Bluehost\VerifactiApi\Builder\InvoiceBuilder;
use Bluehost\VerifactiApi\Builder\InvoiceLineBuilder;
use Bluehost\VerifactiApi\Dto\ApiResponse;
use Bluehost\VerifactiApi\Dto\InvoiceCancelRequest;
use Bluehost\VerifactiApi\Dto\InvoiceLine;
use Bluehost\VerifactiApi\Dto\OtherIdentifier;
use Bluehost\VerifactiApi\Dto\StatusResponse;
use PHPUnit\Framework\TestCase;

final class InvoicePayloadTest extends TestCase
{
    public function testLineSerializesImpuestoFirstAndSurcharge(): void
    {
        $line = (new InvoiceLineBuilder())
            ->withTaxType('03')
            ->withTaxableBase('100')
            ->withTax('7', '7')
            ->withRegimeKey('01')
            ->withEquivalenceSurcharge('0', '0')
            ->build();

        self::assertSame([
            'impuesto' => '03',
            'base_imponible' => '100',
            'tipo_impositivo' => '7',
            'cuota_repercutida' => '7',
            'clave_regimen' => '01',
            'tipo_recargo_equivalencia' => '0',
            'cuota_recargo_equivalencia' => '0',
        ], $line->toArray());
        self::assertSame('03', $line->getTaxType());
        self::assertSame('01', $line->getRegimeKey());
    }

    public function testLineWithoutTaxTypeOmitsImpuesto(): void
    {
        self::assertArrayNotHasKey('impuesto', (new InvoiceLine('10', '21', '2.10'))->toArray());
    }

    public function testRectifiedSurchargeIsOptional(): void
    {
        $request = (new InvoiceBuilder())
            ->withInvoiceType('R1')
            ->withCorrectiveData((new CorrectiveInvoiceBuilder())->bySubstitution()->withRectifiedAmounts('100', '21', '5.20'))
            ->buildCreate();

        self::assertSame(
            ['base_rectificada' => '100', 'cuota_rectificada' => '21', 'cuota_recargo_rectificada' => '5.20'],
            $request->toArray()['importe_rectificativa']
        );
    }

    public function testRecipientGettersAndOtherIdentifierPayload(): void
    {
        $identifier = new OtherIdentifier('FR', '02', 'FR12345678901');
        $request = (new InvoiceBuilder())->withOtherIdentifier($identifier, 'Client SARL')->withIncidentCode('S')->buildCreate();

        self::assertNull($request->getNif());
        self::assertSame($identifier, $request->getOtherIdentifier());
        self::assertSame('Client SARL', $request->getRecipientName());
        self::assertSame('S', $request->getIncidentCode());
        self::assertNull($request->getSpecialData());
        self::assertSame(['codigo_pais' => 'FR', 'id_type' => '02', 'id' => 'FR12345678901'], $request->toArray()['id_otro']);
    }

    public function testModifyRequestCarriesPreviousRejection(): void
    {
        $modify = (new InvoiceBuilder())->withSeries('A')->withNumber('1')->buildCreate()->toModifyRequest(null, null, '01-10-2026', 'X');

        self::assertSame('X', $modify->toArray()['rechazo_previo']);
        self::assertSame('01-10-2026', $modify->toArray()['fecha_expedicion']);
    }

    public function testCancelPayload(): void
    {
        self::assertSame(
            ['serie' => 'A', 'numero' => '1', 'fecha_expedicion' => '25-02-2025'],
            (new InvoiceCancelRequest('A', '1', '25-02-2025'))->toArray()
        );
    }

    public function testStatusResponseAccessors(): void
    {
        $status = StatusResponse::fromApiResponse(new ApiResponse(200, [
            'uuid' => 'u',
            'estado' => 'Incorrecto',
            'url' => 'https://www2.agenciatributaria.gob.es/x',
            'qr' => 'iVBOR',
            'codigo_error' => '1100',
            'mensaje_error' => 'Valor del campo NIF incorrecto',
        ], '', []));

        self::assertFalse($status->isAccepted());
        self::assertFalse($status->isPending());
        self::assertSame('1100', $status->getErrorCode());
        self::assertSame('Valor del campo NIF incorrecto', $status->getErrorMessage());
        self::assertSame('iVBOR', $status->getQrCodeBase64());
        self::assertSame('https://www2.agenciatributaria.gob.es/x', $status->getVerificationUrl());

        $accepted = StatusResponse::fromApiResponse(new ApiResponse(200, ['estado' => 'Aceptado con errores', 'codigo_error' => ''], '', []));
        self::assertTrue($accepted->isAccepted());
        self::assertNull($accepted->getErrorCode());
    }
}
