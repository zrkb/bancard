<?php

namespace Bancard\Tests\Operations;

use Bancard\Operations\SingleBuyConfirm;
use Bancard\Tests\TestCase;

class SingleBuyConfirmTest extends TestCase
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
        ];
    }

    public function testToken(): void
    {
        $op = new SingleBuyConfirm($this->payload);
        $expected = md5('test_private_key' . '123' . 'confirm' . '10000.00' . 'PYG');
        $this->assertSame($expected, $op->token());
    }

    public function testExecuteReturnsFalse(): void
    {
        $op = new SingleBuyConfirm($this->payload);
        $this->assertFalse($op->execute());
    }

    public function testData(): void
    {
        $op = new SingleBuyConfirm($this->payload);
        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertSame('123', $data['operation']['shop_process_id']);
        $this->assertSame('10000.00', $data['operation']['amount']);
        $this->assertSame('PYG', $data['operation']['currency']);
        $this->assertArrayHasKey('token', $data['operation']);
    }
}
