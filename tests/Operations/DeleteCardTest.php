<?php

declare(strict_types=1);

namespace Bancard\Tests\Operations;

use Bancard\Bancard;
use Bancard\Operations\DeleteCard;
use Bancard\Response\DeleteCardResponse;
use Bancard\Tests\TestCase;

class DeleteCardTest extends TestCase
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
            'user_id' => '789',
            'alias_token' => 'alias123',
        ];
    }

    /**
     * DeleteCard token matches the spec formula.
     */
    public function testToken(): void
    {
        $op = new DeleteCard($this->bancard, $this->payload);
        $expected = md5('test_private_key' . 'delete_card' . '789' . 'alias123');
        $this->assertSame($expected, $op->token());
    }

    /**
     * DeleteCard request body contains the public key, payload and token.
     */
    public function testData(): void
    {
        $op = new DeleteCard($this->bancard, $this->payload);
        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertSame('789', $data['operation']['user_id']);
        $this->assertSame('alias123', $data['operation']['alias_token']);
        $this->assertArrayHasKey('token', $data['operation']);
    }

    /**
     * DeleteCard uses the spec endpoint.
     */
    public function testEndpoint(): void
    {
        $op = new DeleteCard($this->bancard, $this->payload);
        $this->assertSame(
            '/vpos/api/0.3/users/{user_id}/cards',
            $this->getProtectedProperty($op, 'endpoint')
        );
    }

    /**
     * DeleteCard uses the DELETE method.
     */
    public function testMethodIsDelete(): void
    {
        $op = new DeleteCard($this->bancard, $this->payload);
        $this->assertSame('DELETE', $this->getProtectedProperty($op, 'method'));
    }

    /**
     * Execute returns delete card response.
     */
    public function testExecuteReturnsDeleteCardResponse(): void
    {
        $this->mockResponse(200, $this->fixtureArray('delete_card_success'));

        $op = new DeleteCard($this->bancard, $this->payload);
        $response = $op->execute();

        $this->assertInstanceOf(DeleteCardResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertRequestSent('DELETE', '/vpos/api/0.3/users/789/cards');
    }
}
