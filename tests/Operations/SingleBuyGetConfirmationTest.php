<?php

declare(strict_types=1);

namespace Bancard\Tests\Operations;

use Bancard\Bancard;
use Bancard\Operations\SingleBuyGetConfirmation;
use Bancard\Response\ConfirmationResponse;
use Bancard\Tests\TestCase;

class SingleBuyGetConfirmationTest extends TestCase
{
    private Bancard $bancard;

    /** @var array<string, string> */
    private array $payload;

    /**
     * Create a Bancard client with a mocked HTTP handler and a default payload.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->bancard = $this->makeBancard();
        $this->payload = [
            'shop_process_id' => '123',
        ];
    }

    /**
     * SingleBuyGetConfirmation token matches the spec formula.
     */
    public function testToken(): void
    {
        $op = new SingleBuyGetConfirmation($this->bancard, $this->payload);
        $expected = md5('test_private_key' . '123' . 'get_confirmation');
        $this->assertSame($expected, $op->token());
    }

    /**
     * SingleBuyGetConfirmation request body contains the public key, payload and token.
     */
    public function testData(): void
    {
        $op = new SingleBuyGetConfirmation($this->bancard, $this->payload);
        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertSame('123', $data['operation']['shop_process_id']);
        $this->assertArrayHasKey('token', $data['operation']);
    }

    /**
     * SingleBuyGetConfirmation uses the spec endpoint.
     */
    public function testEndpoint(): void
    {
        $op = new SingleBuyGetConfirmation($this->bancard, $this->payload);
        $this->assertSame(
            '/vpos/api/0.3/single_buy/confirmations',
            $this->getProtectedProperty($op, 'endpoint')
        );
    }

    /**
     * Execute returns confirmation response.
     */
    public function testExecuteReturnsConfirmationResponse(): void
    {
        $this->mockResponse(200, $this->fixtureArray('confirmation_success'));

        $op = new SingleBuyGetConfirmation($this->bancard, $this->payload);
        $response = $op->execute();

        $this->assertInstanceOf(ConfirmationResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->isApproved());
        $this->assertSame('00', $response->getResponseCode());
        $this->assertRequestSent('POST', '/vpos/api/0.3/single_buy/confirmations');
    }

    /**
     * SingleBuyGetConfirmation: pending confirmation.
     */
    public function testPendingConfirmation(): void
    {
        $this->mockResponse(200, $this->fixtureArray('confirmation_pending'));

        $op = new SingleBuyGetConfirmation($this->bancard, $this->payload);
        $response = $op->execute();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isApproved());
        $this->assertSame('12', $response->getResponseCode());
    }
}
