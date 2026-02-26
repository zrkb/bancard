<?php

declare(strict_types=1);

namespace Bancard\Tests\Operations;

use Bancard\Bancard;
use Bancard\Operations\BillingCancel;
use Bancard\Response\BillingCancelResponse;
use Bancard\Tests\TestCase;

class BillingCancelTest extends TestCase
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
        ];
    }

    public function testToken(): void
    {
        $op = new BillingCancel($this->bancard, $this->payload);
        $expected = md5('test_private_key' . '123' . 'billing_cancel');
        $this->assertSame($expected, $op->token());
    }

    public function testData(): void
    {
        $op = new BillingCancel($this->bancard, $this->payload);
        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertSame('123', $data['operation']['shop_process_id']);
        $this->assertArrayHasKey('token', $data['operation']);
    }

    public function testEndpoint(): void
    {
        $op = new BillingCancel($this->bancard, $this->payload);
        $this->assertSame(
            '/vpos/api/0.3/billing/cancel',
            $this->getProtectedProperty($op, 'endpoint')
        );
    }

    public function testExecuteReturnsBillingCancelResponse(): void
    {
        $this->mockResponse(200, $this->fixtureArray('billing_cancel_success'));

        $op = new BillingCancel($this->bancard, $this->payload);
        $response = $op->execute();

        $this->assertInstanceOf(BillingCancelResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertRequestSent('POST', '/vpos/api/0.3/billing/cancel');
    }
}
