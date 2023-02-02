<?php

namespace Bancard\Tests\Operations;

use Bancard\Operations\Charge;
use Bancard\Tests\TestCase;

class ChargeTest extends TestCase
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
            'alias_token' => 'alias123',
        ];
    }

    public function testToken(): void
    {
        $op = new Charge($this->payload);
        $expected = md5('test_private_key' . '123' . 'charge' . '10000.00' . 'PYG' . 'alias123');
        $this->assertSame($expected, $op->token());
    }

    public function testData(): void
    {
        $op = new Charge($this->payload);
        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertSame('123', $data['operation']['shop_process_id']);
        $this->assertSame('10000.00', $data['operation']['amount']);
        $this->assertSame('PYG', $data['operation']['currency']);
        $this->assertSame('alias123', $data['operation']['alias_token']);
        $this->assertArrayHasKey('token', $data['operation']);
    }

    public function testEndpoint(): void
    {
        $op = new Charge($this->payload);
        $this->assertSame('/vpos/api/0.3/charge', $this->getProtectedProperty($op, 'endpoint'));
    }
}
