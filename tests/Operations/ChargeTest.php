<?php

declare(strict_types=1);

namespace Bancard\Tests\Operations;

use Bancard\Bancard;
use Bancard\Exception\ValidationException;
use Bancard\Operations\Charge;
use Bancard\Response\ChargeResponse;
use Bancard\Tests\TestCase;

class ChargeTest extends TestCase
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
            'amount' => '10000.00',
            'currency' => 'PYG',
            'alias_token' => 'alias123',
            'description' => 'Order 123',
        ];
    }

    /**
     * Charge token matches the spec formula.
     */
    public function testToken(): void
    {
        $op = new Charge($this->bancard, $this->payload);
        $expected = md5('test_private_key' . '123' . 'charge' . '10000.00' . 'PYG' . 'alias123');
        $this->assertSame($expected, $op->token());
    }

    /**
     * Charge request body contains the public key, payload and token.
     */
    public function testData(): void
    {
        $op = new Charge($this->bancard, $this->payload);
        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertSame('123', $data['operation']['shop_process_id']);
        $this->assertSame('10000.00', $data['operation']['amount']);
        $this->assertSame('PYG', $data['operation']['currency']);
        $this->assertSame('alias123', $data['operation']['alias_token']);
        $this->assertArrayHasKey('token', $data['operation']);
    }

    /**
     * Charge uses the spec endpoint.
     */
    public function testEndpoint(): void
    {
        $op = new Charge($this->bancard, $this->payload);
        $this->assertSame('/vpos/api/0.3/charge', $this->getProtectedProperty($op, 'endpoint'));
    }

    /**
     * Execute returns charge response.
     */
    public function testExecuteReturnsChargeResponse(): void
    {
        $this->mockResponse(200, $this->fixtureArray('charge_success'));

        $op = new Charge($this->bancard, $this->payload);
        $response = $op->execute();

        $this->assertInstanceOf(ChargeResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->isApproved());
        $this->assertSame('00', $response->getResponseCode());
        $this->assertSame('123', $response->getProcessId());
        $this->assertRequestSent('POST', '/vpos/api/0.3/charge');
    }

    /**
     * Execute handles 3DS redirect.
     */
    public function testExecuteHandles3dsRedirect(): void
    {
        $this->mockResponse(200, $this->fixtureArray('charge_3ds_redirect'));

        $op = new Charge($this->bancard, $this->payload);
        $response = $op->execute();

        $this->assertTrue($response->is3dsRedirect());
    }

    /**
     * The staging-verified approved response exposes the confirmation details.
     */
    public function testApprovedChargeDetails(): void
    {
        $this->mockResponse(200, $this->fixtureArray('charge_success'));

        $response = (new Charge($this->bancard, $this->payload))->execute();

        $this->assertTrue($response->isApproved());
        $this->assertFalse($response->requires3ds());
        $this->assertNull($response->get3dsProcessId());
        $this->assertFalse($response->is3dsRedirect());
        $this->assertSame('123', $response->getShopProcessId());
        $this->assertSame('932557', $response->getAuthorizationNumber());
        $this->assertSame('2119288713', $response->getTicketNumber());
        $this->assertSame('Transaccion aprobada', $response->getResponseDescription());
        $this->assertSame('L', $response->getCardSource());
        $this->assertSame(0, $response->getRiskIndex());
    }

    /**
     * A non-null confirmation.process_id means 3DS authentication is required.
     */
    public function testThreeDsRequired(): void
    {
        $this->mockResponse(200, $this->fixtureArray('charge_3ds_required'));

        $response = (new Charge($this->bancard, $this->payload))->execute();

        $this->assertTrue($response->requires3ds());
        $this->assertSame('i5fn*lx6niQel0QzWK1g', $response->get3dsProcessId());
        $this->assertTrue($response->is3dsRedirect());
        $this->assertFalse($response->isApproved());
        $this->assertNull($response->getShopProcessId());
    }

    /**
     * The spec's 3DS example shape ({"operation": {"process_id": ...}}) is also detected; empty IDs are not.
     */
    public function testThreeDsProcessIdShapes(): void
    {
        $this->assertSame('abc', (new ChargeResponse((object) ['operation' => (object) ['process_id' => 'abc']]))->get3dsProcessId());
        $this->assertFalse((new ChargeResponse((object) ['confirmation' => (object) ['process_id' => '']]))->requires3ds());
        $this->assertFalse((new ChargeResponse((object) ['status' => 'success']))->requires3ds());
    }

    /**
     * Charge sends the defaults Bancard needs: number_of_payments, additional_data and the 3DS attribute.
     */
    public function testDefaults(): void
    {
        $operation = (new Charge($this->bancard, $this->payload))->data()['operation'];

        $this->assertSame(1, $operation['number_of_payments']);
        $this->assertSame('', $operation['additional_data']);
        $this->assertSame(['confirmation.process_id'], $operation['extra_response_attributes']);
    }

    /**
     * Explicit values replace the defaults, and null omits them.
     */
    public function testDefaultsCanBeOverriddenOrOmitted(): void
    {
        $operation = (new Charge($this->bancard, [
            'additional_data' => '099VS ORO000045',
            'extra_response_attributes' => null,
        ] + $this->payload))->data()['operation'];

        $this->assertSame('099VS ORO000045', $operation['additional_data']);
        $this->assertArrayNotHasKey('extra_response_attributes', $operation);
    }

    /**
     * description is required and must not be empty, since Bancard rejects charges without it.
     */
    public function testDescriptionIsRequired(): void
    {
        foreach ([null, ''] as $description) {
            try {
                (new Charge($this->bancard, ['description' => $description] + $this->payload))->execute();
                $this->fail('Expected ValidationException was not thrown.');
            } catch (ValidationException $e) {
                $this->assertSame(['description'], $e->getErrors());
            }
        }

        $this->assertCount(0, $this->requestHistory);
    }
}
