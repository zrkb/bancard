<?php

declare(strict_types=1);

namespace Bancard\Tests\Operations;

use Bancard\Bancard;
use Bancard\Operations\BillingClientInfo;
use Bancard\Response\BillingClientInfoResponse;
use Bancard\Tests\TestCase;

class BillingClientInfoTest extends TestCase
{
    private Bancard $bancard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bancard = $this->makeBancard();
    }

    public function testToken(): void
    {
        $op = new BillingClientInfo($this->bancard, ['some_field' => 'value']);
        $expected = md5('test_private_key' . 'billing_client_info');
        $this->assertSame($expected, $op->token());
    }

    public function testTokenDoesNotDependOnPayload(): void
    {
        $op1 = new BillingClientInfo($this->bancard, ['field_a' => 'aaa']);
        $op2 = new BillingClientInfo($this->bancard, ['field_b' => 'bbb']);
        $this->assertSame($op1->token(), $op2->token());
    }

    public function testData(): void
    {
        $op = new BillingClientInfo($this->bancard, ['some_field' => 'value']);
        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertSame('value', $data['operation']['some_field']);
        $this->assertArrayHasKey('token', $data['operation']);
    }

    public function testEndpoint(): void
    {
        $op = new BillingClientInfo($this->bancard, ['some_field' => 'value']);
        $this->assertSame(
            '/vpos/api/0.3/billing/client_info',
            $this->getProtectedProperty($op, 'endpoint')
        );
    }

    public function testExecuteReturnsBillingClientInfoResponse(): void
    {
        $this->mockResponse(200, $this->fixtureArray('billing_client_info_success'));

        $op = new BillingClientInfo($this->bancard, ['some_field' => 'value']);
        $response = $op->execute();

        $this->assertInstanceOf(BillingClientInfoResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertNotNull($response->getClient());
        $this->assertSame('John Doe', $response->getClient()->name);
        $this->assertRequestSent('POST', '/vpos/api/0.3/billing/client_info');
    }
}
