<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Tests\Service;

use Bluehost\VerifactiApi\Builder\InvoiceBuilder;
use Bluehost\VerifactiApi\Builder\InvoiceLineBuilder;
use Bluehost\VerifactiApi\Client\ClientFactory;
use Bluehost\VerifactiApi\Client\VerifactiClient;
use Bluehost\VerifactiApi\Config\ConfigFactory;
use Bluehost\VerifactiApi\Dto\InvoiceCancelRequest;
use Bluehost\VerifactiApi\Dto\InvoiceCreateRequest;
use Bluehost\VerifactiApi\Dto\RecordStatusLookupRequest;
use Bluehost\VerifactiApi\Exception\ApiException;
use Bluehost\VerifactiApi\Exception\AuthenticationException;
use Bluehost\VerifactiApi\Exception\HttpException;
use Bluehost\VerifactiApi\Exception\SerializationException;
use Bluehost\VerifactiApi\Exception\TransportException;
use Bluehost\VerifactiApi\Exception\ValidationException;
use Bluehost\VerifactiApi\Service\RetryPolicy;
use Bluehost\VerifactiApi\Tests\Double\FakeTransport;
use PHPUnit\Framework\TestCase;

final class ApiExecutorTest extends TestCase
{
    private const API_KEY = 'vf_test_SECRETKEY123';

    private FakeTransport $transport;

    /**
     * @var array<int, int>
     */
    private array $sleeps = [];

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $this->sleeps = [];
    }

    public function testCreateReturnsParsedResponse(): void
    {
        $this->transport->queue(200, [
            'uuid' => 'abc-123',
            'estado' => 'Pendiente',
            'url' => 'https://prewww2.aeat.es/wlpl/TIKE-CONT/ValidarQR?nif=A15022510',
            'qr' => 'iVBORw0KGgo=',
            'huella' => str_repeat('a', 64),
        ]);

        $response = $this->client()->createInvoice($this->invoice(), 'order-1-invoice');

        self::assertSame('abc-123', $response->getUuid());
        self::assertSame('Pendiente', $response->getStatus());
        self::assertSame('iVBORw0KGgo=', $response->getQrCodeBase64());
        self::assertSame(str_repeat('a', 64), $response->getHash());
        self::assertStringStartsWith('https://prewww2.aeat.es/', (string) $response->getVerificationUrl());
        self::assertFalse($response->isIdempotentReplay());

        $request = $this->transport->requests[0];
        self::assertSame('POST', $request->getMethod());
        self::assertSame('/verifactu/create', $request->getPath());
        self::assertSame('order-1-invoice', $request->getHeaders()['Idempotency-Key']);
        self::assertSame('Bearer ' . self::API_KEY, $request->getHeaders()['Authorization']);
        self::assertSame($this->invoice()->toArray(), json_decode((string) $request->getBody(), true));
    }

    public function testIdempotentReplayHeaderIsExposed(): void
    {
        $this->transport->queue(200, ['uuid' => 'abc'], ['Idempotent-Replayed' => 'true']);

        self::assertTrue($this->client()->createInvoice($this->invoice(), 'k')->isIdempotentReplay());
    }

    public function testValidationErrorFromApiIsMappedWithCode(): void
    {
        $this->transport->queue(400, ['error' => 'La factura ya existe', 'codigo' => 'vf-verifactu-factura_duplicada']);

        try {
            $this->client()->createInvoice($this->invoice(), 'k');
            self::fail('Expected ApiException.');
        } catch (ApiException $exception) {
            self::assertSame(400, $exception->getStatusCode());
            self::assertSame('La factura ya existe', $exception->getMessage());
            self::assertSame('vf-verifactu-factura_duplicada', $exception->getErrorCode());
        }

        self::assertCount(1, $this->transport->requests, '4xx responses must not be retried.');
    }

    public function testUnauthorizedIsMappedToAuthenticationException(): void
    {
        $this->transport->queue(401, ['error' => 'API key no válida']);

        $this->expectException(AuthenticationException::class);
        $this->client()->createInvoice($this->invoice(), 'k');
    }

    public function testForbiddenNonJsonIsMappedToAuthenticationException(): void
    {
        $this->transport->queue(403, 'NIF desactivado');

        try {
            $this->client()->createInvoice($this->invoice(), 'k');
            self::fail('Expected AuthenticationException.');
        } catch (AuthenticationException $exception) {
            self::assertSame(403, $exception->getStatusCode());
            self::assertSame('NIF desactivado', $exception->getMessage());
        }
    }

    public function testRateLimitIsRetriedWithRetryAfter(): void
    {
        $this->transport
            ->queue(429, ['error' => 'Too many requests'], ['Retry-After' => '2'])
            ->queue(200, ['uuid' => 'ok']);

        $response = $this->client(2)->createInvoice($this->invoice(), 'k');

        self::assertSame('ok', $response->getUuid());
        self::assertCount(2, $this->transport->requests);
        self::assertSame([1000], $this->sleeps, 'Retry-After (2 s) is capped at the policy max delay (1 s).');
    }

    public function testServerErrorsAreRetriedWithExponentialBackoffThenFail(): void
    {
        $this->transport
            ->queue(500, ['error' => 'Internal'])
            ->queue(503, 'Service Unavailable')
            ->queue(502, ['error' => 'Bad gateway']);

        try {
            $this->client(2)->createInvoice($this->invoice(), 'k');
            self::fail('Expected ApiException.');
        } catch (ApiException $exception) {
            self::assertSame(502, $exception->getStatusCode());
        }

        self::assertCount(3, $this->transport->requests);
        self::assertSame([100, 200], $this->sleeps);
    }

    public function testCreateWithoutIdempotencyKeyIsNeverRetried(): void
    {
        $this->transport->queue(500, ['error' => 'Internal']);

        try {
            $this->client(3)->createInvoice($this->invoice());
            self::fail('Expected ApiException.');
        } catch (ApiException $exception) {
            self::assertSame(500, $exception->getStatusCode());
        }

        self::assertCount(1, $this->transport->requests);
    }

    public function testTimeoutIsRetriedThenSucceeds(): void
    {
        $this->transport->queueFailure()->queue(200, ['uuid' => 'ok']);

        self::assertSame('ok', $this->client(1)->createInvoice($this->invoice(), 'k')->getUuid());
        self::assertCount(2, $this->transport->requests);
    }

    public function testTimeoutIsRethrownWhenRetriesExhausted(): void
    {
        $this->transport->queueFailure()->queueFailure();

        $this->expectException(TransportException::class);
        $this->client(1)->createInvoice($this->invoice(), 'k');
    }

    public function testGetRequestsAreRetriedWithoutKey(): void
    {
        $this->transport->queue(503, 'busy')->queue(200, ['uuid' => 'u', 'estado' => 'Correcto']);

        $status = $this->client(1)->getRecordStatus(new RecordStatusLookupRequest('u'));

        self::assertTrue($status->isAccepted());
        self::assertSame('GET', $this->transport->requests[1]->getMethod());
    }

    public function testMalformedJsonSuccessBodyThrowsSerializationException(): void
    {
        $this->transport->queue(200, '{"uuid": ', ['content-type' => 'application/json']);

        $this->expectException(SerializationException::class);
        $this->client()->createInvoice($this->invoice(), 'k');
    }

    public function testMalformedJsonErrorBodyThrowsHttpException(): void
    {
        $this->transport->queue(400, '{"error": ', ['content-type' => 'application/json']);

        try {
            $this->client()->createInvoice($this->invoice(), 'k');
            self::fail('Expected HttpException.');
        } catch (HttpException $exception) {
            self::assertNotInstanceOf(ApiException::class, $exception);
            self::assertSame('The Verifacti API returned an invalid JSON error response.', $exception->getMessage());
        }
    }

    public function testNonJsonErrorMessageIsRedactedAndTruncated(): void
    {
        $body = 'Upstream echoed Authorization: Bearer ' . self::API_KEY . ' ' . str_repeat('x', 2000);
        $this->transport->queue(502, $body);

        try {
            $this->client()->createInvoice($this->invoice(), 'k');
            self::fail('Expected HttpException.');
        } catch (HttpException $exception) {
            self::assertStringNotContainsString(self::API_KEY, $exception->getMessage());
            self::assertLessThanOrEqual(512, strlen($exception->getMessage()));
        }
    }

    public function testValidationHappensBeforeAnyRequest(): void
    {
        $invalid = new InvoiceCreateRequest('A', '1', date('d-m-Y'), 'F2', 'x', [], '0', null, 'A15022510');

        try {
            $this->client()->createInvoice($invalid, 'k');
            self::fail('Expected ValidationException.');
        } catch (ValidationException $exception) {
            self::assertSame([], $this->transport->requests);
        }
    }

    public function testUnsafeIdempotencyKeyIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->client()->createInvoice($this->invoice(), str_repeat('k', 256));
    }

    public function testCancelSendsIdempotencyKey(): void
    {
        $this->transport->queue(200, ['estado' => 'Pendiente', 'uuid' => 'c', 'huella' => 'h']);

        $this->client()->cancelInvoice(new InvoiceCancelRequest('A', '1', '01-10-2026'), 'cancel-1');

        self::assertSame('/verifactu/cancel', $this->transport->requests[0]->getPath());
        self::assertSame('cancel-1', $this->transport->requests[0]->getHeaders()['Idempotency-Key']);
    }

    public function testModifySendsIdempotencyKey(): void
    {
        $this->transport->queue(200, ['estado' => 'Pendiente', 'uuid' => 'm']);

        $this->client()->modifyInvoice($this->invoice()->toModifyRequest(null, null, null, 'N'), 'modify-1');

        self::assertSame('PUT', $this->transport->requests[0]->getMethod());
        self::assertSame('modify-1', $this->transport->requests[0]->getHeaders()['Idempotency-Key']);
    }

    public function testDefaultClientUsesConfiguredMaxRetries(): void
    {
        $this->transport->queue(500, ['error' => 'x'])->queue(200, ['uuid' => 'ok']);
        $client = ClientFactory::fromApiKey(self::API_KEY, ['max_retries' => 0], $this->transport);

        $this->expectException(ApiException::class);
        $client->createInvoice($this->invoice(), 'k');
    }

    public function testRetryPolicyBounds(): void
    {
        $policy = new RetryPolicy(10, 100, 50, static function (): void {
        });

        self::assertSame(5, $policy->getMaxRetries());
        self::assertSame(100, $policy->delayFor(3), 'max delay is never below the base delay');
        self::assertSame(100, $policy->delayFor(1, '3600'));
        self::assertSame(0, RetryPolicy::none()->getMaxRetries());
        self::assertFalse($policy->isRetrySafe('POST', ['Idempotency-Key' => ' ']));
        self::assertTrue($policy->isRetrySafe('post', ['idempotency-key' => 'x']));
        self::assertFalse($policy->isRetryableStatus(400));
    }

    private function client(int $maxRetries = 0): VerifactiClient
    {
        $policy = new RetryPolicy($maxRetries, 100, 1000, function (int $ms): void {
            $this->sleeps[] = $ms;
        });

        return new VerifactiClient(ConfigFactory::test(self::API_KEY), $this->transport, $policy);
    }

    private function invoice(): InvoiceCreateRequest
    {
        return (new InvoiceBuilder())
            ->withSeries('A')
            ->withNumber('1')
            ->withIssueDate(date('d-m-Y'))
            ->withInvoiceType('F1')
            ->withDescription('Test')
            ->withRecipient('A15022510', 'Customer')
            ->addLine((new InvoiceLineBuilder())->withTaxableBase('100')->withTax('21', '21')->build())
            ->withTotalAmount('121')
            ->buildCreate();
    }
}
