<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Tests\Exception;

use Bluehost\VerifactiApi\Exception\HttpException;
use PHPUnit\Framework\TestCase;

final class HttpExceptionTest extends TestCase
{
    public function testGetResponseBodyReturnsTruncatedValue(): void
    {
        $body = str_repeat('x', 600);
        $exception = new HttpException('error', 500, [], $body);

        $this->assertSame(512, strlen($exception->getResponseBody()));
        $this->assertSame(600, strlen($exception->getFullResponseBody()));
        $this->assertSame($body, $exception->getFullResponseBody());
    }
}
