<?php

namespace Bancard\Tests\Operations;

use Bancard\Operations\BillingClientInfo;
use Bancard\Tests\TestCase;

class BillingClientInfoTest extends TestCase
{
    public function testToken(): void
    {
        $op = new BillingClientInfo(['some_field' => 'value']);
        $expected = md5('test_private_key' . 'billing_client_info');
        $this->assertSame($expected, $op->token());
    }

    public function testTokenDoesNotDependOnPayload(): void
    {
        $op1 = new BillingClientInfo(['field_a' => 'aaa']);
        $op2 = new BillingClientInfo(['field_b' => 'bbb']);
        $this->assertSame($op1->token(), $op2->token());
    }

    public function testData(): void
    {
        $op = new BillingClientInfo(['some_field' => 'value']);
        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertSame('value', $data['operation']['some_field']);
        $this->assertArrayHasKey('token', $data['operation']);
    }

    public function testEndpoint(): void
    {
        $op = new BillingClientInfo(['some_field' => 'value']);
        $this->assertSame(
            '/vpos/api/0.3/billing/client_info',
            $this->getProtectedProperty($op, 'endpoint')
        );
    }
}
