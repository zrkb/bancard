<?php

declare(strict_types=1);

namespace Bancard\Tests;

use Bancard\Bancard;
use Bancard\Exception\ApiException;
use Bancard\Exception\BancardException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\RequestInterface;

class BancardTest extends TestCase
{
    /**
     * Bodies that are not a JSON object.
     *
     * @return array<string, array{int, string}>
     */
    public static function nonObjectBodies(): array
    {
        return [
            'html gateway error' => [502, '<html>Bad Gateway</html>'],
            'empty body' => [200, ''],
            'json array' => [200, '[]'],
            'json string' => [200, '"ok"'],
        ];
    }

    /**
     * Non-JSON-object responses raise BancardException with the HTTP status as code.
     */
    #[DataProvider('nonObjectBodies')]
    public function testRequestThrowsBancardExceptionOnNonObjectBody(int $status, string $body): void
    {
        $bancard = $this->makeBancard();
        $this->mockHandler->append(new Response($status, [], $body));

        try {
            $bancard->singleBuyGetConfirmation(['shop_process_id' => '123']);
            $this->fail('Expected BancardException was not thrown.');
        } catch (ApiException $e) {
            $this->fail('ApiException must not be thrown for a non-JSON-object body.');
        } catch (BancardException $e) {
            $this->assertSame($status, $e->getCode());
            $this->assertStringContainsString("HTTP {$status}", $e->getMessage());
        }
    }

    /**
     * HTTP error statuses with a Bancard JSON body surface as ApiException.
     */
    public function testHttpErrorWithJsonBodyThrowsApiException(): void
    {
        $bancard = $this->makeBancard();
        $this->mockResponse(401, [
            'status' => 'error',
            'messages' => [['key' => 'InvalidPublicKeyError', 'level' => 'error', 'dsc' => 'Invalid public key.']],
        ]);

        try {
            $bancard->singleBuyGetConfirmation(['shop_process_id' => '123']);
            $this->fail('Expected ApiException was not thrown.');
        } catch (ApiException $e) {
            $this->assertSame('InvalidPublicKeyError', $e->getErrorKey());
            $this->assertSame('Invalid public key.', $e->getMessage());
        }
    }

    /**
     * Default timeouts are applied when none are configured.
     */
    public function testDefaultTimeoutsAreApplied(): void
    {
        $options = $this->captureRequestOptions([]);

        $this->assertSame(Bancard::DEFAULT_TIMEOUT, $options['timeout']);
        $this->assertSame(Bancard::DEFAULT_CONNECT_TIMEOUT, $options['connect_timeout']);
    }

    /**
     * User-provided timeouts override the defaults.
     */
    public function testCustomTimeoutsOverrideDefaults(): void
    {
        $options = $this->captureRequestOptions(['timeout' => 5, 'connect_timeout' => 2]);

        $this->assertSame(5, $options['timeout']);
        $this->assertSame(2, $options['connect_timeout']);
    }

    /**
     * The private key does not appear in var_dump/print_r output.
     */
    public function testPrivateKeyIsRedactedInDebugOutput(): void
    {
        $bancard = new Bancard(publicKey: 'pk', privateKey: 'super_secret_key');

        ob_start();
        var_dump($bancard);
        $dump = (string) ob_get_clean();

        $this->assertStringNotContainsString('super_secret_key', $dump);
        $this->assertStringNotContainsString('super_secret_key', print_r($bancard, true));
        $this->assertStringContainsString('pk', $dump);
    }

    /**
     * The private key parameter is hidden from stack traces.
     */
    public function testPrivateKeyParameterIsSensitive(): void
    {
        $param = new \ReflectionParameter([Bancard::class, '__construct'], 'privateKey');

        $this->assertCount(1, $param->getAttributes(\SensitiveParameter::class));
    }

    /**
     * setHttp() accepts any Guzzle ClientInterface implementation.
     */
    public function testSetHttpAcceptsClientInterface(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('POST', '/vpos/api/0.3/single_buy/confirmations', $this->arrayHasKey('json'))
            ->willReturn(new Response(200, [], '{"status":"success","confirmation":{"response_code":"00"}}'));

        $bancard = new Bancard(publicKey: 'pk', privateKey: 'sk');
        $bancard->setHttp($client);

        $this->assertTrue($bancard->singleBuyGetConfirmation(['shop_process_id' => '123'])->isApproved());
    }

    /**
     * Send one request through a client built with the given Guzzle options and return the request options used.
     *
     * @param array<string, mixed> $guzzle Guzzle options passed to the Bancard constructor
     * @return array<string, mixed> Request options received by the handler
     */
    private function captureRequestOptions(array $guzzle): array
    {
        $captured = [];
        $mock = new MockHandler([
            function (RequestInterface $request, array $options) use (&$captured): Response {
                $captured = $options;

                return new Response(200, [], '{"status":"success","confirmation":{}}');
            },
        ]);

        $bancard = new Bancard(
            publicKey: 'pk',
            privateKey: 'sk',
            guzzle: $guzzle + ['handler' => HandlerStack::create($mock)],
        );
        $bancard->singleBuyGetConfirmation(['shop_process_id' => '123']);

        /** @var array<string, mixed> $captured */
        return $captured;
    }

    public function testConstructorSetsConfig(): void
    {
        $bancard = new Bancard(
            publicKey: 'pk_123',
            privateKey: 'sk_456',
        );

        $this->assertSame('pk_123', $bancard->publicKey);
        $this->assertSame('sk_456', $bancard->privateKey);
        $this->assertFalse($bancard->staging);
    }

    public function testConstructorSetsStaging(): void
    {
        $bancard = new Bancard(
            publicKey: 'pk',
            privateKey: 'sk',
            staging: true,
        );

        $this->assertTrue($bancard->staging);
    }

    public function testStagingDefaultsToFalse(): void
    {
        $bancard = new Bancard(
            publicKey: 'pk',
            privateKey: 'sk',
        );

        $this->assertFalse($bancard->staging);
    }

    public function testBaseUriReturnsProductionUrl(): void
    {
        $bancard = new Bancard(
            publicKey: 'pk',
            privateKey: 'sk',
            staging: false,
        );

        $this->assertSame('https://vpos.infonet.com.py/', $bancard->baseUri());
    }

    public function testBaseUriReturnsStagingUrl(): void
    {
        $bancard = new Bancard(
            publicKey: 'pk',
            privateKey: 'sk',
            staging: true,
        );

        $this->assertSame('https://vpos.infonet.com.py:8888/', $bancard->baseUri());
    }

    public function testSingleBuyConvenienceMethod(): void
    {
        $bancard = $this->makeBancard();
        $this->mockResponse(200, $this->fixtureArray('single_buy_success'));

        $response = $bancard->singleBuy([
            'shop_process_id' => '123',
            'amount' => '10000.00',
            'currency' => 'PYG',
            'return_url' => 'https://example.com/return',
            'cancel_url' => 'https://example.com/cancel',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('abc123', $response->getProcessId());
        $this->assertRequestSent('POST', '/vpos/api/0.3/single_buy');
    }

    public function testSingleBuyZimpleSetsZimpleFlag(): void
    {
        $bancard = $this->makeBancard();
        $this->mockResponse(200, $this->fixtureArray('single_buy_success'));

        $response = $bancard->singleBuyZimple([
            'shop_process_id' => '123',
            'amount' => '10000.00',
            'currency' => 'PYG',
            'return_url' => 'https://example.com/return',
            'cancel_url' => 'https://example.com/cancel',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertRequestSent('POST', '/vpos/api/0.3/single_buy');

        $body = $this->getRequestBody();
        $this->assertSame('S', $body['operation']['zimple']);
    }

    public function testChargeConvenienceMethod(): void
    {
        $bancard = $this->makeBancard();
        $this->mockResponse(200, $this->fixtureArray('charge_success'));

        $response = $bancard->charge([
            'shop_process_id' => '123',
            'amount' => '10000.00',
            'currency' => 'PYG',
            'alias_token' => 'alias123',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->isApproved());
        $this->assertRequestSent('POST', '/vpos/api/0.3/charge');
    }
}
