<?php

namespace Bancard\Tests\Operations;

use Bancard\Operations\SingleBuy;
use Bancard\Tests\TestCase;

class SingleBuyTest extends TestCase
{
    /** @var array<string, string> */
    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();
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
        $op = new SingleBuy($this->payload);
        $expected = md5('test_private_key' . '123' . '10000.00' . 'PYG');
        $this->assertSame($expected, $op->token());
    }

    public function testData(): void
    {
        $op = new SingleBuy($this->payload);
        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertSame('123', $data['operation']['shop_process_id']);
        $this->assertSame('10000.00', $data['operation']['amount']);
        $this->assertSame('PYG', $data['operation']['currency']);
        $this->assertArrayHasKey('token', $data['operation']);
    }

    public function testEndpoint(): void
    {
        $op = new SingleBuy($this->payload);
        $this->assertSame('/vpos/api/0.3/single_buy', $this->getProtectedProperty($op, 'endpoint'));
    }

    public function testMethodIsPost(): void
    {
        $op = new SingleBuy($this->payload);
        $this->assertSame('POST', $this->getProtectedProperty($op, 'method'));
    }
}
