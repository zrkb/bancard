<?php

declare(strict_types=1);

namespace Bancard\Tests\Operations;

use Bancard\Bancard;
use Bancard\Exception\ValidationException;
use Bancard\Operations\Operation;
use Bancard\Response\Response;
use Bancard\Tests\TestCase;
use Bancard\Util\Token;
use InvalidArgumentException;

class OperationTest extends TestCase
{
    private Bancard $bancard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bancard = $this->makeBancard();
    }

    public function testPayloadReturnsValue(): void
    {
        $op = new TestableOperation($this->bancard, ['id' => 'value', 'name' => 'test']);
        $this->assertSame('value', $op->payload('id'));
    }

    public function testPayloadThrowsOnMissingKey(): void
    {
        $op = new TestableOperation($this->bancard, ['id' => '1', 'name' => 'test']);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid key "missing" in payload.');
        $op->payload('missing');
    }

    public function testDataReturnsCorrectStructure(): void
    {
        $op = new TestableOperation($this->bancard, ['id' => '123', 'name' => 'test']);

        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertArrayHasKey('operation', $data);
        $this->assertArrayHasKey('token', $data['operation']);
        $this->assertSame('123', $data['operation']['id']);
        $this->assertSame('test', $data['operation']['name']);
    }

    public function testDataOnlyFiltersNull(): void
    {
        $op = new class($this->bancard, [
            'keep' => 'value',
            'keep_false' => false,
            'keep_empty' => '',
            'keep_zero' => 0,
            'remove_null' => null,
        ]) extends Operation {
            protected string $endpoint = '/test';

            /** @var class-string<Response> */
            protected string $responseClass = Response::class;

            public function token(): string
            {
                return 'token';
            }
        };

        $data = $op->data();

        $this->assertSame('value', $data['operation']['keep']);
        $this->assertFalse($data['operation']['keep_false']);
        $this->assertSame('', $data['operation']['keep_empty']);
        $this->assertSame(0, $data['operation']['keep_zero']);
        $this->assertArrayNotHasKey('remove_null', $data['operation']);
    }

    public function testDataKeepsStringZeroPointZero(): void
    {
        $op = new class($this->bancard, ['amount' => '0.00']) extends Operation {
            protected string $endpoint = '/test';

            /** @var class-string<Response> */
            protected string $responseClass = Response::class;

            public function token(): string
            {
                return 'token';
            }
        };

        $data = $op->data();
        $this->assertSame('0.00', $data['operation']['amount']);
    }

    public function testPayloadTokenOverridesGeneratedToken(): void
    {
        $op = new class($this->bancard, ['token' => 'custom_token']) extends Operation {
            protected string $endpoint = '/test';

            /** @var class-string<Response> */
            protected string $responseClass = Response::class;

            public function token(): string
            {
                return 'generated_token';
            }
        };

        $data = $op->data();
        $this->assertSame('custom_token', $data['operation']['token']);
    }

    public function testDefaultMethodIsPost(): void
    {
        $op = new TestableOperation($this->bancard, ['id' => '1', 'name' => 'test']);
        $this->assertSame('POST', $this->getProtectedProperty($op, 'method'));
    }

    public function testDataUsesPublicKeyFromClient(): void
    {
        $bancard = $this->makeBancard(publicKey: 'custom_public_key');
        $op = new TestableOperation($bancard, ['id' => '1', 'name' => 'test']);

        $data = $op->data();
        $this->assertSame('custom_public_key', $data['public_key']);
    }

    public function testExecuteSendsCorrectRequest(): void
    {
        $this->mockResponse(200, ['status' => 'success']);

        $op = new TestableOperation($this->bancard, ['id' => '42', 'name' => 'test']);
        $result = $op->execute();

        $this->assertTrue($result->isSuccessful());
        $this->assertCount(1, $this->requestHistory);

        $this->assertRequestSent('POST', '/test/42/resource');

        $body = $this->getRequestBody();
        $this->assertSame('test_public_key', $body['public_key']);
        $this->assertArrayHasKey('token', $body['operation']);
        $this->assertSame('42', $body['operation']['id']);
        $this->assertSame('test', $body['operation']['name']);
    }

    public function testExecuteInterpolatesEndpointPlaceholders(): void
    {
        $this->mockResponse(200, ['ok' => true]);

        $op = new TestableOperation($this->bancard, ['id' => '99', 'name' => 'test']);
        $op->execute();

        $this->assertRequestSent('POST', '/test/99/resource');
    }

    public function testValidationThrowsOnMissingRequiredFields(): void
    {
        $op = new ValidatedOperation($this->bancard, ['unrelated' => 'value']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing required fields: required_field');

        $this->mockResponse(200, ['status' => 'success']);
        $op->execute();
    }

    public function testValidationPassesWithRequiredFields(): void
    {
        $this->mockResponse(200, ['status' => 'success']);

        $op = new ValidatedOperation($this->bancard, ['required_field' => 'value']);
        $result = $op->execute();

        $this->assertTrue($result->isSuccessful());
    }
}

/**
 * @extends Operation<Response>
 */
class TestableOperation extends Operation
{
    protected string $endpoint = '/test/{id}/resource';

    /** @var class-string<Response> */
    protected string $responseClass = Response::class;

    public function token(): string
    {
        return Token::make('test_private_key', (string) $this->payload('id'));
    }
}

/**
 * @extends Operation<Response>
 */
class ValidatedOperation extends Operation
{
    protected string $endpoint = '/test';

    /** @var class-string<Response> */
    protected string $responseClass = Response::class;

    public function token(): string
    {
        return 'token';
    }

    /**
     * @return list<string>
     */
    protected function rules(): array
    {
        return ['required_field'];
    }
}
