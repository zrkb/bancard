<?php

namespace Bancard\Tests\Operations;

use Bancard\Bancard;
use Bancard\Operations\Operation;
use Bancard\Tests\TestCase;
use Bancard\Util\Token;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;

class OperationTest extends TestCase
{
    public function testPayloadReturnsValue(): void
    {
        $op = new class(['key' => 'value']) extends Operation {
            protected string $endpoint = '/test';
            public function token(): string { return 'test_token'; }
        };
        $this->assertSame('value', $op->payload('key'));
    }

    public function testPayloadThrowsOnMissingKey(): void
    {
        $op = new class([]) extends Operation {
            protected string $endpoint = '/test';
            public function token(): string { return 'test_token'; }
        };
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid key "missing" in payload.');
        $op->payload('missing');
    }

    public function testDataReturnsCorrectStructure(): void
    {
        $op = new class(['shop_process_id' => '123']) extends Operation {
            protected string $endpoint = '/test';
            public function token(): string { return 'generated_token'; }
        };

        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertArrayHasKey('operation', $data);
        $this->assertSame('generated_token', $data['operation']['token']);
        $this->assertSame('123', $data['operation']['shop_process_id']);
    }

    public function testDataFiltersFalsyValues(): void
    {
        $op = new class([
            'keep' => 'value',
            'remove_null' => null,
            'remove_false' => false,
            'remove_empty' => '',
            'remove_zero' => 0,
        ]) extends Operation {
            protected string $endpoint = '/test';
            public function token(): string { return 'token'; }
        };

        $data = $op->data();

        $this->assertSame('value', $data['operation']['keep']);
        $this->assertArrayNotHasKey('remove_null', $data['operation']);
        $this->assertArrayNotHasKey('remove_false', $data['operation']);
        $this->assertArrayNotHasKey('remove_empty', $data['operation']);
        $this->assertArrayNotHasKey('remove_zero', $data['operation']);
    }

    public function testDataKeepsStringZeroPointZero(): void
    {
        $op = new class(['amount' => '0.00']) extends Operation {
            protected string $endpoint = '/test';
            public function token(): string { return 'token'; }
        };

        $data = $op->data();
        $this->assertSame('0.00', $data['operation']['amount']);
    }

    public function testPayloadTokenOverridesGeneratedToken(): void
    {
        $op = new class(['token' => 'custom_token']) extends Operation {
            protected string $endpoint = '/test';
            public function token(): string { return 'generated_token'; }
        };

        $data = $op->data();
        $this->assertSame('custom_token', $data['operation']['token']);
    }

    public function testDefaultMethodIsPost(): void
    {
        $op = new class([]) extends Operation {
            protected string $endpoint = '/test';
            public function token(): string { return 'token'; }
        };

        $this->assertSame('POST', $this->getProtectedProperty($op, 'method'));
    }

    public function testDataUsesPublicKeyFromBancard(): void
    {
        Bancard::setPublicKey('custom_public_key');

        $op = new class([]) extends Operation {
            protected string $endpoint = '/test';
            public function token(): string { return 'token'; }
        };

        $data = $op->data();
        $this->assertSame('custom_public_key', $data['public_key']);
    }

    public function testExecuteSendsCorrectRequest(): void
    {
        $mock = new MockHandler([
            new Response(200, [], (string) json_encode(['status' => 'success'])),
        ]);
        /** @var array<int, array<string, mixed>> $history */
        $history = [];
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $guzzle = new GuzzleClient([
            'handler' => $stack,
            'base_uri' => 'https://vpos.infonet.com.py/',
        ]);

        $bancard = new Bancard();
        $bancard->setHttp($guzzle);

        $op = new TestableOperation(['id' => '42', 'name' => 'test']);
        $result = $op->executeWith($bancard);

        $this->assertEquals('success', $result->status);
        $this->assertCount(1, $history);

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/test/42/resource', $request->getUri()->getPath());

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('test_public_key', $body['public_key']);
        $this->assertArrayHasKey('token', $body['operation']);
        $this->assertSame('42', $body['operation']['id']);
        $this->assertSame('test', $body['operation']['name']);
    }

    public function testExecuteInterpolatesEndpointPlaceholders(): void
    {
        $mock = new MockHandler([
            new Response(200, [], (string) json_encode(['ok' => true])),
        ]);
        /** @var array<int, array<string, mixed>> $history */
        $history = [];
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $guzzle = new GuzzleClient([
            'handler' => $stack,
            'base_uri' => 'https://vpos.infonet.com.py/',
        ]);

        $bancard = new Bancard();
        $bancard->setHttp($guzzle);

        $op = new TestableOperation(['id' => '99', 'name' => 'test']);
        $op->executeWith($bancard);

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $history[0]['request'];
        $this->assertSame('/test/99/resource', $request->getUri()->getPath());
    }
}

/**
 * Concrete Operation subclass for integration testing.
 * Provides executeWith() to inject a Bancard client instance.
 */
class TestableOperation extends Operation
{
    protected string $endpoint = '/test/{id}/resource';

    public function token(): string
    {
        return Token::make(Bancard::privateKey(), $this->payload('id'));
    }

    /**
     * @return mixed
     */
    public function executeWith(Bancard $client)
    {
        /** @var string $endpoint */
        $endpoint = preg_replace_callback(
            '/{(\w+)}/',
            function (array $m): string { return (string) $this->payload($m[1]); },
            $this->endpoint
        );

        return $client->request($this->method, $endpoint, $this->data());
    }
}
