<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Tests\Validator;

use Bluehost\VerifactiApi\Builder\CorrectiveInvoiceBuilder;
use Bluehost\VerifactiApi\Builder\InvoiceBuilder;
use Bluehost\VerifactiApi\Builder\InvoiceLineBuilder;
use Bluehost\VerifactiApi\Builder\SpecialInvoiceDataBuilder;
use Bluehost\VerifactiApi\Dto\AbstractInvoiceRequest;
use Bluehost\VerifactiApi\Dto\InvoiceCancelRequest;
use Bluehost\VerifactiApi\Dto\InvoiceLine;
use Bluehost\VerifactiApi\Dto\InvoiceReference;
use Bluehost\VerifactiApi\Dto\OtherIdentifier;
use Bluehost\VerifactiApi\Exception\ValidationException;
use Bluehost\VerifactiApi\Validator\RequestValidator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class InvoiceValidationRulesTest extends TestCase
{
    private const TODAY = '08-10-2026';

    private RequestValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new RequestValidator(clock: new DateTimeImmutable('2026-10-08 09:00:00'));
    }

    public function testValidF1Passes(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validator->validateCreate($this->f1()->buildCreate());
    }

    public function testIssueDateMustBeToday(): void
    {
        $this->assertErrorContains('fecha_expedicion must match the current date.', $this->f1()->withIssueDate('07-10-2026')->buildCreate());
    }

    public function testUnknownInvoiceTypeIsRejected(): void
    {
        $this->assertErrorContains('tipo_factura must be one of', $this->f1()->withInvoiceType('Rx')->buildCreate());
    }

    public function testCorrectiveRequiresRectificationType(): void
    {
        $this->assertErrorContains('tipo_rectificativa is required', $this->f1()->withInvoiceType('R1')->buildCreate());
    }

    public function testRectificationTypeNotAllowedOnF1(): void
    {
        $request = $this->f1()->withCorrectiveData((new CorrectiveInvoiceBuilder())->byDifference())->buildCreate();

        $this->assertErrorContains('tipo_rectificativa is only allowed for rectificative invoices', $request);
    }

    public function testInvalidRectificationTypeIsRejected(): void
    {
        $request = new \Bluehost\VerifactiApi\Dto\InvoiceCreateRequest(
            'R', '1', self::TODAY, 'R4', 'Desc', [new InvoiceLine('100', '21', '21')], '121', null, 'A15022510', null, 'Customer', null, 'X'
        );

        $this->assertErrorContains('tipo_rectificativa must be one of S or I.', $request);
    }

    public function testSubstitutionRequiresRectifiedAmounts(): void
    {
        $request = $this->f1()->withInvoiceType('R1')->withCorrectiveData((new CorrectiveInvoiceBuilder())->bySubstitution())->buildCreate();

        $this->assertErrorContains('importe_rectificativa is required when tipo_rectificativa is S.', $request);
    }

    public function testDifferencesForbidsRectifiedAmounts(): void
    {
        $corrective = (new CorrectiveInvoiceBuilder())->byDifference()->withRectifiedAmounts('100', '21');
        $request = $this->f1()->withInvoiceType('R1')->withCorrectiveData($corrective)->buildCreate();

        $this->assertErrorContains('importe_rectificativa is only allowed when tipo_rectificativa is S.', $request);
    }

    public function testRectifiedAmountsMustBeValidNumbers(): void
    {
        $corrective = (new CorrectiveInvoiceBuilder())->bySubstitution()->withRectifiedAmounts('100.001', '21');
        $request = $this->f1()->withInvoiceType('R1')->withCorrectiveData($corrective)->buildCreate();

        $this->assertErrorContains('importe_rectificativa.base_rectificada must be a number', $request);
    }

    public function testRectifiedInvoicesOnlyOnCorrective(): void
    {
        $corrective = (new CorrectiveInvoiceBuilder())->addCorrectedInvoice(new InvoiceReference('A', '1', '01-10-2026'));

        $this->assertErrorContains('facturas_rectificadas is only allowed', $this->f1()->withCorrectiveData($corrective)->buildCreate());
    }

    public function testReplacedInvoicesOnlyOnF3(): void
    {
        $corrective = (new CorrectiveInvoiceBuilder())->addReplacedInvoice(new InvoiceReference('S', '1', '01-10-2026'));

        $this->assertErrorContains('facturas_sustituidas is only allowed for F3 invoices.', $this->f1()->withCorrectiveData($corrective)->buildCreate());
    }

    public function testInvoiceReferencesAreValidated(): void
    {
        $corrective = (new CorrectiveInvoiceBuilder())->byDifference()->addCorrectedInvoice(new InvoiceReference('A', '', '2026-10-01'));
        $request = $this->negativeLines($this->f1()->withInvoiceType('R4')->withCorrectiveData($corrective))->buildCreate();

        $errors = $this->errorsFor($request);
        self::assertContains('facturas_rectificadas[0].numero cannot be empty.', $errors);
        self::assertContains('facturas_rectificadas[0].fecha_expedicion must use the d-m-Y format.', $errors);
    }

    public function testF2ForbidsRecipient(): void
    {
        $this->assertErrorContains('nif, nombre and id_otro are not allowed for F2 invoices.', $this->f1()->withInvoiceType('F2')->buildCreate());
    }

    public function testR5ForbidsRecipient(): void
    {
        $request = $this->negativeLines($this->f1()->withInvoiceType('R5')->withCorrectiveData((new CorrectiveInvoiceBuilder())->byDifference()))->buildCreate();

        $this->assertErrorContains('nif, nombre and id_otro are not allowed for R5 invoices.', $request);
    }

    public function testF1RequiresRecipient(): void
    {
        $request = $this->builder()->withInvoiceType('F1')->buildCreate();
        $errors = $this->errorsFor($request);

        self::assertContains('nombre is required for F1 invoices.', $errors);
        self::assertContains('nif or id_otro is required for F1 invoices.', $errors);
    }

    public function testNifMustHaveNineCharacters(): void
    {
        $this->assertErrorContains('nif must be exactly 9 characters.', $this->f1()->withRecipient('ESA15022510', 'Customer')->buildCreate());
    }

    public function testOtherIdentifierRules(): void
    {
        $request = $this->builder()->withInvoiceType('F1')
            ->withOtherIdentifier(new OtherIdentifier('', '99', str_repeat('9', 21)), 'Customer')
            ->buildCreate();
        $errors = $this->errorsFor($request);

        self::assertContains('id_otro.id_type must be one of 02, 03, 04, 05, 06, 07.', $errors);
        self::assertContains('id_otro.codigo_pais is required unless id_type is 02.', $errors);
        self::assertContains('id_otro.id must contain between 1 and 20 characters.', $errors);
    }

    public function testVatIdentifierDoesNotNeedCountry(): void
    {
        $this->expectNotToPerformAssertions();
        $request = $this->builder()->withInvoiceType('F1')
            ->withOtherIdentifier(new OtherIdentifier('', '02', 'FR12345678901'), 'Client SARL')
            ->addLine((new InvoiceLineBuilder())->withTaxableBase('100')->asExempt('E5')->build())
            ->withTotalAmount('100')
            ->buildCreate();

        $this->validator->validateCreate($request);
    }

    public function testRecipientNameMaxLength(): void
    {
        $this->assertErrorContains('nombre cannot exceed 120 characters.', $this->f1()->withRecipient('A15022510', str_repeat('n', 121))->buildCreate());
    }

    public function testDescriptionAndSeriesLength(): void
    {
        $request = $this->f1()->withDescription(str_repeat('d', 501))->withSeries(str_repeat('S', 60))->buildCreate();
        $errors = $this->errorsFor($request);

        self::assertContains('descripcion cannot exceed 500 characters.', $errors);
        self::assertContains('serie and numero together cannot exceed 60 characters.', $errors);
    }

    public function testAmountFormatIsValidated(): void
    {
        $request = $this->f1()->withTotalAmount('121,00')->buildCreate();

        $this->assertErrorContains('importe_total must be a number with at most 2 decimals.', $request);
    }

    public function testLineAmountPrecisionIsValidated(): void
    {
        $request = $this->recipient($this->builder()->withInvoiceType('F1'))
            ->addLine(new InvoiceLine('100.005', '21', '21'))
            ->withTotalAmount('121')
            ->buildCreate();

        $this->assertErrorContains('lineas[0]: base_imponible must be a number with at most 2 decimals.', $request);
    }

    public function testRateFormatIsValidated(): void
    {
        $request = $this->recipient($this->builder()->withInvoiceType('F1'))
            ->addLine(new InvoiceLine('100', '-21', '-21'))
            ->withTotalAmount('79')
            ->buildCreate();

        $this->assertErrorContains('lineas[0]: tipo_impositivo must be a non-negative number', $request);
    }

    public function testTotalMustMatchLines(): void
    {
        $request = $this->f1()->withTotalAmount('150')->buildCreate();
        $strict = new RequestValidator(1, 1, new DateTimeImmutable('2026-10-08'));

        try {
            $strict->validateCreate($request);
            self::fail('Expected ValidationException.');
        } catch (ValidationException $exception) {
            self::assertContains('importe_total (150) does not match the sum of the lines (121.00).', $exception->getErrors());
        }
    }

    public function testTotalWithinApiToleranceIsAccepted(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validator->validateCreate($this->f1()->withTotalAmount('125')->buildCreate());
    }

    public function testTotalCheckSkippedForSpecialRegimes(): void
    {
        $this->expectNotToPerformAssertions();
        $request = $this->recipient($this->builder()->withInvoiceType('F1'))
            ->addLine((new InvoiceLineBuilder())->withTaxableBase('100')->withTax('21', '21')->withRegimeKey('03')->build())
            ->withTotalAmount('900')
            ->buildCreate();

        $this->validator->validateCreate($request);
    }

    public function testQuotaMustMatchRate(): void
    {
        $request = $this->recipient($this->builder()->withInvoiceType('F1'))
            ->addLine(new InvoiceLine('100', '21', '7'))
            ->withTotalAmount('107')
            ->buildCreate();
        $strict = new RequestValidator(1, 1, new DateTimeImmutable('2026-10-08'));

        try {
            $strict->validateCreate($request);
            self::fail('Expected ValidationException.');
        } catch (ValidationException $exception) {
            self::assertContains('lineas[0]: cuota_repercutida (7) does not match base_imponible x tipo_impositivo (21.00).', $exception->getErrors());
        }
    }

    public function testQuotaSignMustMatchBase(): void
    {
        $request = $this->recipient($this->builder()->withInvoiceType('F1'))
            ->addLine(new InvoiceLine('-100', '21', '21'))
            ->withTotalAmount('-79')
            ->buildCreate();

        $this->assertErrorContains('lineas[0]: cuota_repercutida must have the same sign as base_imponible.', $request);
    }

    public function testNegativeF1CreditNoteIsValid(): void
    {
        $this->expectNotToPerformAssertions();
        $request = $this->negativeLines($this->recipient($this->builder()->withInvoiceType('F1')))->buildCreate();

        $this->validator->validateCreate($request);
    }

    public function testRateAndQuotaMustBePaired(): void
    {
        $request = $this->recipient($this->builder()->withInvoiceType('F1'))
            ->addLine(new InvoiceLine('100', '21', null, null, 'S1'))
            ->withTotalAmount('100')
            ->buildCreate();

        $this->assertErrorContains('lineas[0]: tipo_impositivo and cuota_repercutida must be provided together.', $request);
    }

    public function testLineNeedsTaxOrClassification(): void
    {
        $request = $this->recipient($this->builder()->withInvoiceType('F1'))
            ->addLine(new InvoiceLine('100'))
            ->withTotalAmount('100')
            ->buildCreate();

        $this->assertErrorContains('Either tax values or an exempt/non-subject classification must be provided.', $request);
    }

    public function testSurchargeMustBePaired(): void
    {
        $request = $this->recipient($this->builder()->withInvoiceType('F1'))
            ->addLine(new InvoiceLine('100', '21', '21', null, null, null, [], null, '5.2'))
            ->withTotalAmount('121')
            ->buildCreate();

        $this->assertErrorContains('tipo_recargo_equivalencia and cuota_recargo_equivalencia must be provided together.', $request);
    }

    public function testSurchargeCountsTowardsTotal(): void
    {
        $this->expectNotToPerformAssertions();
        $request = $this->recipient($this->builder()->withInvoiceType('F1'))
            ->addLine((new InvoiceLineBuilder())->withTaxableBase('100')->withTax('21', '21')->withEquivalenceSurcharge('5.2', '5.2')->build())
            ->withTotalAmount('126.20')
            ->buildCreate();

        (new RequestValidator(1, 1, new DateTimeImmutable('2026-10-08')))->validateCreate($request);
    }

    public function testExemptionAndQualificationAreMutuallyExclusive(): void
    {
        $request = $this->recipient($this->builder()->withInvoiceType('F1'))
            ->addLine(new InvoiceLine('100', null, null, 'E5', 'N2'))
            ->withTotalAmount('100')
            ->buildCreate();

        $this->assertErrorContains('operacion_exenta and calificacion_operacion cannot be combined.', $request);
    }

    public function testExemptLineCannotCarryTax(): void
    {
        $request = $this->recipient($this->builder()->withInvoiceType('F1'))
            ->addLine(new InvoiceLine('100', '21', '21', 'E1'))
            ->withTotalAmount('121')
            ->buildCreate();

        $this->assertErrorContains('Exempt lines cannot carry tipo_impositivo', $request);
    }

    public function testE7OnlyAllowedForIgic(): void
    {
        $iva = $this->recipient($this->builder()->withInvoiceType('F1'))
            ->addLine((new InvoiceLineBuilder())->withTaxableBase('100')->asExempt('E7')->build())
            ->withTotalAmount('100')
            ->buildCreate();
        $this->assertErrorContains('operacion_exenta E7 is not valid for impuesto 01.', $iva);

        $igic = $this->recipient($this->builder()->withInvoiceType('F1'))
            ->addLine((new InvoiceLineBuilder())->withTaxType('03')->withTaxableBase('100')->asExempt('E7')->build())
            ->withTotalAmount('100')
            ->buildCreate();
        $this->validator->validateCreate($igic);
    }

    public function testUnknownEnumsOnLineAreRejected(): void
    {
        $request = $this->recipient($this->builder()->withInvoiceType('F1'))
            ->addLine((new InvoiceLineBuilder())->withTaxType('04')->withTaxableBase('100')->withOperationClassification('S9')->withRegimeKey('99')->build())
            ->withTotalAmount('100')
            ->buildCreate();
        $errors = $this->errorsFor($request);

        self::assertContains('lineas[0]: impuesto must be one of 01, 02, 03, 05.', $errors);
        self::assertContains('lineas[0]: calificacion_operacion must be one of S1, S2, N1, N2.', $errors);
        self::assertContains('lineas[0]: clave_regimen is not a valid regime key.', $errors);
    }

    public function testReverseChargeRequiresZeroQuota(): void
    {
        $request = $this->recipient($this->builder()->withInvoiceType('F1'))
            ->addLine((new InvoiceLineBuilder())->withTaxableBase('100')->withTax('21', '21')->withOperationClassification('S2')->build())
            ->withTotalAmount('121')
            ->buildCreate();

        $this->assertErrorContains('S2 (reverse charge) lines must have tipo_impositivo and cuota_repercutida equal to 0.', $request);
    }

    public function testF2LimitOf3000(): void
    {
        $request = $this->builder()->withInvoiceType('F2')
            ->addLine(new InvoiceLine('2500', '21', '525'))
            ->withTotalAmount('3025')
            ->buildCreate();

        $this->assertErrorContains('F2 invoices cannot exceed 3000.00', $request);
    }

    public function testF2LimitDoesNotApplyWithArt61d(): void
    {
        $this->expectNotToPerformAssertions();
        $request = $this->builder()->withInvoiceType('F2')
            ->addLine(new InvoiceLine('2500', '21', '525'))
            ->withTotalAmount('3025')
            ->withSpecialData((new SpecialInvoiceDataBuilder())->withField('factura_sin_identif_destinatario_art_61d', 'S')->build())
            ->buildCreate();

        $this->validator->validateCreate($request);
    }

    public function testModifyRejectsUnknownPreviousRejection(): void
    {
        $request = $this->f1()->withPreviousRejectionStatus('Z')->buildModify();

        try {
            $this->validator->validateModify($request);
            self::fail('Expected ValidationException.');
        } catch (ValidationException $exception) {
            self::assertContains('rechazo_previo must be one of N, X or S.', $exception->getErrors());
        }
    }

    public function testCancelRejectsUnknownPreviousRejection(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validateCancel(new InvoiceCancelRequest('A', '1', '01-10-2026', 'Z'));
    }

    private function builder(): InvoiceBuilder
    {
        return (new InvoiceBuilder())
            ->withSeries('A')
            ->withNumber('1')
            ->withIssueDate(self::TODAY)
            ->withDescription('Test invoice');
    }

    private function recipient(InvoiceBuilder $builder): InvoiceBuilder
    {
        return $builder->withRecipient('A15022510', 'Customer');
    }

    private function f1(): InvoiceBuilder
    {
        return $this->recipient($this->builder()->withInvoiceType('F1'))
            ->addLine(new InvoiceLine('100', '21', '21'))
            ->withTotalAmount('121');
    }

    private function negativeLines(InvoiceBuilder $builder): InvoiceBuilder
    {
        return $builder->addLine(new InvoiceLine('-100', '21', '-21'))->withTotalAmount('-121');
    }

    /**
     * @return array<int, string>
     */
    private function errorsFor(AbstractInvoiceRequest $request): array
    {
        try {
            $this->validator->validateCreate($request);
        } catch (ValidationException $exception) {
            return $exception->getErrors();
        }

        return [];
    }

    private function assertErrorContains(string $needle, AbstractInvoiceRequest $request): void
    {
        $errors = $this->errorsFor($request);

        foreach ($errors as $error) {
            if (str_contains($error, $needle)) {
                self::assertTrue(true);

                return;
            }
        }

        self::fail(sprintf("Expected an error containing \"%s\"; got:\n%s", $needle, implode("\n", $errors)));
    }
}
