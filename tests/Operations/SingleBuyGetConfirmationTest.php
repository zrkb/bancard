<?php

namespace Bancard\Tests\Operations;

use Bancard\Operations\SingleBuyGetConfirmation;
use Bancard\Tests\TestCase;

class SingleBuyGetConfirmationTest extends TestCase
{
    /** @var array<string, string> */
    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->payload = [
            'shop_process_id' => '123',
        ];
    }

    public function testToken(): void
    {
        $op = new SingleBuyGetConfirmation($this->payload);
        $expected = md5('test_private_key' . '123' . 'get_confirmation');
        $this->assertSame($expected, $op->token());
    }

    public function testData(): void
    {
        $op = new SingleBuyGetConfirmation($this->payload);
        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertSame('123', $data['operation']['shop_process_id']);
        $this->assertArrayHasKey('token', $data['operation']);
    }

    public function testEndpoint(): void
    {
        $op = new SingleBuyGetConfirmation($this->payload);
        $this->assertSame(
            '/vpos/api/0.3/single_buy/confirmations',
            $this->getProtectedProperty($op, 'endpoint')
        );
    }
}
