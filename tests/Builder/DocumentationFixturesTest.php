<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Tests\Builder;

use Bluehost\VerifactiApi\Builder\CorrectiveInvoiceBuilder;
use Bluehost\VerifactiApi\Builder\InvoiceBuilder;
use Bluehost\VerifactiApi\Builder\InvoiceLineBuilder;
use Bluehost\VerifactiApi\Builder\InvoiceReferenceBuilder;
use Bluehost\VerifactiApi\Dto\InvoiceCreateRequest;
use Bluehost\VerifactiApi\Dto\InvoiceReference;
use Bluehost\VerifactiApi\Enum\InvoiceType;
use Bluehost\VerifactiApi\Enum\TaxType;
use Bluehost\VerifactiApi\Validator\RequestValidator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Builds each documented Verifacti example with the library builders and
 * compares the payload with the documentation JSON field by field.
 */
final class DocumentationFixturesTest extends TestCase
{
    private const TODAY = '08-10-2026';

    /**
     * @return array<string, array{0: string, 1: callable(): InvoiceCreateRequest}>
     */
    public function documentedExamples(): array
    {
        return [
            'F1 normal' => ['factura_normal.json', fn (): InvoiceCreateRequest => $this->base('A', '1', InvoiceType::F1, 'Normal invoice')
                ->withRecipient('A15022510', 'Customer name')
                ->addLine((new InvoiceLineBuilder())->withTaxableBase('200')->withTax('21', '42')->build())
                ->withTotalAmount('242')
                ->buildCreate()],
            'IGIC' => ['factura_con_igic.json', fn (): InvoiceCreateRequest => $this->base('A', '1', InvoiceType::F1, 'Operation with IGIC')
                ->withRecipient('A15022510', 'Customer name')
                ->addLine((new InvoiceLineBuilder())->withTaxType(TaxType::IGIC)->withTaxableBase('200')->withTax('7', '14')->build())
                ->withTotalAmount('214')
                ->buildCreate()],
            'IPSI' => ['factura_con_ipsi.json', fn (): InvoiceCreateRequest => $this->base('A', '1', InvoiceType::F1, 'Operation with IPSI')
                ->withRecipient('A15022510', 'Customer name')
                ->addLine((new InvoiceLineBuilder())->withTaxType(TaxType::IPSI)->withTaxableBase('200')->withTax('4', '8')->build())
                ->withTotalAmount('208')
                ->buildCreate()],
            'R1 by substitution, one step' => ['rectificativa_por_sustitucion.json', fn (): InvoiceCreateRequest => $this->base('R', '1', InvoiceType::R1, 'Correction by substitution in one step')
                ->withOperationDate('01-04-2025')
                ->withRecipient('A15022510', 'Customer name')
                ->addLine((new InvoiceLineBuilder())->withTaxableBase('800')->withTax('21', '168')->build())
                ->withTotalAmount('968')
                ->withCorrectiveData((new CorrectiveInvoiceBuilder())->bySubstitution()->withRectifiedAmounts('1000', '210')->addCorrectedInvoice($this->ref('A', '1', '07-04-2025')))
                ->buildCreate()],
            'R1 by substitution, two steps' => ['rectificativa_por_sustitucion_dos_pasos.json', fn (): InvoiceCreateRequest => $this->base('RECTIFICATIVA', '1', InvoiceType::R1, 'Correction by substitution in two steps')
                ->withOperationDate('01-04-2025')
                ->withRecipient('A15022510', 'Recipient name')
                ->addLine((new InvoiceLineBuilder())->withTaxableBase('800')->withTax('21', '168')->build())
                ->withTotalAmount('968')
                ->withCorrectiveData((new CorrectiveInvoiceBuilder())->bySubstitution()->withRectifiedAmounts('0', '0')->addCorrectedInvoice($this->ref('A', '1', '07-04-2025')))
                ->buildCreate()],
            'R1 by differences' => ['rectificativa_por_diferencias.json', fn (): InvoiceCreateRequest => $this->base('R', '1', InvoiceType::R1, 'Operation description: correction by differences')
                ->withRecipient('A15022510', 'Customer name')
                ->addLine((new InvoiceLineBuilder())->withTaxableBase('-500')->withTax('21', '-105')->build())
                ->withTotalAmount('-605')
                ->withCorrectiveData((new CorrectiveInvoiceBuilder())->byDifference()->addCorrectedInvoice($this->ref('A', '1', '07-04-2025')))
                ->buildCreate()],
            'R3 by differences, non-payment' => ['rectificativa_por_diferencias_impago.json', fn (): InvoiceCreateRequest => $this->base('RECTIFICATIVA', '6', InvoiceType::R3, 'Operation description: correction by differences for non-payment')
                ->withRecipient('A15022510', 'Recipient name')
                ->addLine((new InvoiceLineBuilder())->withTaxableBase('0')->withTax('21', '-210')->build())
                ->withTotalAmount('-210')
                ->withCorrectiveData((new CorrectiveInvoiceBuilder())->byDifference()->addCorrectedInvoice($this->ref('A', '1', '07-04-2025')))
                ->buildCreate()],
            'F2 simplified' => ['factura_simplificada.json', fn (): InvoiceCreateRequest => $this->base('Ejemplos', '1', InvoiceType::F2, 'Simplified invoice')
                ->addLine((new InvoiceLineBuilder())->withTaxableBase('200')->withTax('21', '42')->build())
                ->addLine((new InvoiceLineBuilder())->withTaxableBase('100')->withTax('10', '10')->build())
                ->withTotalAmount('352')
                ->buildCreate()],
            'F3 replacing simplified' => ['factura_de_canje.json', fn (): InvoiceCreateRequest => $this->base('A', '1', InvoiceType::F3, 'Operation description')
                ->withRecipient('A15022510', 'Customer name')
                ->addLine((new InvoiceLineBuilder())->withTaxableBase('200')->withTax('21', '42')->build())
                ->withTotalAmount('242')
                ->withCorrectiveData((new CorrectiveInvoiceBuilder())->addReplacedInvoice($this->ref('SIMPLE', '1', '19-06-2025')))
                ->buildCreate()],
        ];
    }

    /**
     * @dataProvider documentedExamples
     */
    public function testBuilderOutputMatchesDocumentation(string $fixture, callable $build): void
    {
        $expected = $this->loadFixture($fixture);
        $actual = $build()->toArray();

        self::assertSame($this->canonical($expected), $this->canonical($actual));
        self::assertSame(
            json_encode($this->canonical($expected), JSON_THROW_ON_ERROR),
            json_encode($this->canonical($actual), JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @dataProvider documentedExamples
     */
    public function testDocumentedExamplesPassValidation(string $fixture, callable $build): void
    {
        $this->expectNotToPerformAssertions();

        (new RequestValidator(clock: new DateTimeImmutable('2026-10-08 12:00:00')))->validateCreate($build());
    }

    private function base(string $series, string $number, string $type, string $description): InvoiceBuilder
    {
        return (new InvoiceBuilder())
            ->withSeries($series)
            ->withNumber($number)
            ->withIssueDate(self::TODAY)
            ->withInvoiceType($type)
            ->withDescription($description);
    }

    private function ref(string $series, string $number, string $date): InvoiceReference
    {
        return (new InvoiceReferenceBuilder())->withSeries($series)->withNumber($number)->withIssueDate($date)->build();
    }

    /**
     * @return array<string, mixed>
     */
    private function loadFixture(string $name): array
    {
        $json = (string) file_get_contents(__DIR__ . '/../fixtures/docs/' . $name);

        return json_decode(str_replace('CURRENT_DATE', self::TODAY, $json), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Sort associative keys recursively; JSON object key order is not significant.
     *
     * @param array<mixed> $value
     *
     * @return array<mixed>
     */
    private function canonical(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->canonical($item);
            }
        }

        // array_is_list() is PHP 8.1+; the library supports 8.0.
        if ($value !== [] && array_keys($value) !== range(0, count($value) - 1)) {
            ksort($value);
        }

        return $value;
    }
}
