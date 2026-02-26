<?php

declare(strict_types=1);

namespace Bancard\Tests\Operations;

use Bancard\Bancard;
use Bancard\Exception\ValidationException;
use Bancard\Operations\SingleBuy;
use Bancard\Response\SingleBuyResponse;
use Bancard\Tests\TestCase;

class SingleBuyTest extends TestCase
{
    private Bancard $bancard;

    /** @var array<string, string> */
    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bancard = $this->makeBancard();
        $this->payload = [
            'shop_process_id' => '123',
            'amount' => '10000.00',
            'currency' => 'PYG',
            'return_url' => 'https://example.com/return',
            'cancel_url' => 'https://example.com/cancel',
        ];
    }

    public function testToken(): void
    {
        $op = new SingleBuy($this->bancard, $this->payload);
        $expected = md5('test_private_key' . '123' . '10000.00' . 'PYG');
        $this->assertSame($expected, $op->token());
    }

    public function testData(): void
    {
        $op = new SingleBuy($this->bancard, $this->payload);
        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertSame('123', $data['operation']['shop_process_id']);
        $this->assertSame('10000.00', $data['operation']['amount']);
        $this->assertSame('PYG', $data['operation']['currency']);
        $this->assertArrayHasKey('token', $data['operation']);
    }

    public function testEndpoint(): void
    {
        $op = new SingleBuy($this->bancard, $this->payload);
        $this->assertSame('/vpos/api/0.3/single_buy', $this->getProtectedProperty($op, 'endpoint'));
    }

    public function testMethodIsPost(): void
    {
        $op = new SingleBuy($this->bancard, $this->payload);
        $this->assertSame('POST', $this->getProtectedProperty($op, 'method'));
    }

    public function testExecuteReturnsResponse(): void
    {
        $this->mockResponse(200, $this->fixtureArray('single_buy_success'));

        $op = new SingleBuy($this->bancard, $this->payload);
        $response = $op->execute();

        $this->assertInstanceOf(SingleBuyResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('abc123', $response->getProcessId());
        $this->assertRequestSent('POST', '/vpos/api/0.3/single_buy');
    }

    public function testExecuteHandlesErrorResponse(): void
    {
        $this->mockResponse(200, $this->fixtureArray('single_buy_error'));

        $op = new SingleBuy($this->bancard, $this->payload);
        $response = $op->execute();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('Invalid operation.', $response->getMessage());
        $this->assertSame('InvalidOperationError', $response->getErrorKey());
    }

    public function testValidationThrowsOnMissingFields(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing required fields: shop_process_id, amount, currency');

        $op = new SingleBuy($this->bancard, []);
        $this->mockResponse(200, ['status' => 'success']);
        $op->execute();
    }
}
