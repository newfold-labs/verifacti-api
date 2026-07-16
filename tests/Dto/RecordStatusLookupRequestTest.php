<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Tests\Dto;

use Bluehost\VerifactiApi\Dto\RecordStatusLookupRequest;
use Bluehost\VerifactiApi\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

final class RecordStatusLookupRequestTest extends TestCase
{
    public function testConstructorAcceptsSafeQueryParameters(): void
    {
        $request = new RecordStatusLookupRequest('uuid-123', ['filter' => 'active']);

        $this->assertSame(
            ['uuid' => 'uuid-123', 'filter' => 'active'],
            $request->toArray()
        );
    }

    public function testConstructorRejectsUnsafeQueryValues(): void
    {
        $this->expectException(ValidationException::class);

        new RecordStatusLookupRequest('uuid-123', ['filter' => "bad\r\nvalue"]);
    }

    public function testConstructorRejectsUnsafeQueryKeys(): void
    {
        $this->expectException(ValidationException::class);

        new RecordStatusLookupRequest('uuid-123', ["bad\r\nkey" => 'value']);
    }
}
