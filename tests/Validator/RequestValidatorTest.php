<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Tests\Validator;

use Bluehost\VerifactiApi\Dto\InvoiceCreateRequest;
use Bluehost\VerifactiApi\Dto\InvoiceLine;
use Bluehost\VerifactiApi\Exception\ValidationException;
use Bluehost\VerifactiApi\Validator\RequestValidator;
use PHPUnit\Framework\TestCase;

final class RequestValidatorTest extends TestCase
{
    public function testValidatorRejectsTooManyLines(): void
    {
        $lines = array();
        for ($i = 0; $i < 13; $i++) {
            $lines[] = new InvoiceLine('10', '21', '2.1');
        }

        $request = new InvoiceCreateRequest(
            'A',
            '100',
            date('d-m-Y'),
            'F1',
            'Test invoice',
            $lines,
            '121',
            null,
            'A15022510',
            null,
            'Customer'
        );

        try {
            (new RequestValidator())->validateCreate($request);
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('A maximum of 12 invoice lines is allowed.', $exception->getMessage());
            $this->assertSame(
                array('A maximum of 12 invoice lines is allowed.'),
                $exception->getErrors()
            );

            return;
        }

        $this->fail('Expected ValidationException to be thrown.');
    }

    public function testValidatorIncludesAllErrorsInExceptionMessage(): void
    {
        $request = new InvoiceCreateRequest(
            'A',
            '100',
            date('d-m-Y'),
            'F1',
            '',
            array(new InvoiceLine('10', '21', '2.1')),
            '121',
            null,
            'A15022510',
            null,
            'Customer'
        );

        try {
            (new RequestValidator())->validateCreate($request);
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('descripcion cannot be empty.', $exception->getMessage());
            $this->assertContains('descripcion cannot be empty.', $exception->getErrors());

            return;
        }

        $this->fail('Expected ValidationException to be thrown.');
    }
}
