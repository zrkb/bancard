<?php

declare(strict_types=1);

namespace Bancard\Tests\Operations;

use Bancard\Bancard;
use Bancard\Operations\UsersCards;
use Bancard\Response\UsersCardsResponse;
use Bancard\Tests\TestCase;

class UsersCardsTest extends TestCase
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
        ];
    }

    /**
     * UsersCards token matches the spec formula.
     */
    public function testToken(): void
    {
        $op = new UsersCards($this->bancard, $this->payload);
        $expected = md5('test_private_key' . '789' . 'request_user_cards');
        $this->assertSame($expected, $op->token());
    }

    /**
     * UsersCards request body contains the public key, payload and token.
     */
    public function testData(): void
    {
        $op = new UsersCards($this->bancard, $this->payload);
        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertSame('789', $data['operation']['user_id']);
        $this->assertArrayHasKey('token', $data['operation']);
    }

    /**
     * UsersCards uses the spec endpoint.
     */
    public function testEndpoint(): void
    {
        $op = new UsersCards($this->bancard, $this->payload);
        $this->assertSame(
            '/vpos/api/0.3/users/{user_id}/cards',
            $this->getProtectedProperty($op, 'endpoint')
        );
    }

    /**
     * Execute returns users cards response.
     */
    public function testExecuteReturnsUsersCardsResponse(): void
    {
        $this->mockResponse(200, $this->fixtureArray('users_cards_success'));

        $op = new UsersCards($this->bancard, $this->payload);
        $response = $op->execute();

        $this->assertInstanceOf(UsersCardsResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertCount(2, $response->getCards());
        $this->assertRequestSent('POST', '/vpos/api/0.3/users/789/cards');
    }

    /**
     * Execute returns empty cards.
     */
    public function testExecuteReturnsEmptyCards(): void
    {
        $this->mockResponse(200, $this->fixtureArray('users_cards_empty'));

        $op = new UsersCards($this->bancard, $this->payload);
        $response = $op->execute();

        $this->assertSame([], $response->getCards());
    }
}
