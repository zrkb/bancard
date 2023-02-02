<?php

namespace Bancard\Tests\Operations;

use Bancard\Operations\SingleBuyRollback;
use Bancard\Tests\TestCase;

class SingleBuyRollbackTest extends TestCase
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
        $op = new SingleBuyRollback($this->payload);
        $expected = md5('test_private_key' . '123' . 'rollback' . '0.00');
        $this->assertSame($expected, $op->token());
    }

    public function testTokenAlwaysIncludesHardcodedZero(): void
    {
        $op1 = new SingleBuyRollback(['shop_process_id' => '456']);
        $op2 = new SingleBuyRollback(['shop_process_id' => '456', 'amount' => '99999.00']);

        // Token should be identical regardless of amount in payload
        $this->assertSame($op1->token(), $op2->token());
        $this->assertSame(md5('test_private_key' . '456' . 'rollback' . '0.00'), $op1->token());
    }

    public function testData(): void
    {
        $op = new SingleBuyRollback($this->payload);
        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertSame('123', $data['operation']['shop_process_id']);
        $this->assertArrayHasKey('token', $data['operation']);
    }

    public function testEndpoint(): void
    {
        $op = new SingleBuyRollback($this->payload);
        $this->assertSame(
            '/vpos/api/0.3/single_buy/rollback',
            $this->getProtectedProperty($op, 'endpoint')
        );
    }
}
