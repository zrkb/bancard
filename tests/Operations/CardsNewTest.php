<?php

declare(strict_types=1);

namespace Bancard\Tests\Operations;

use Bancard\Bancard;
use Bancard\Exception\ValidationException;
use Bancard\Operations\CardsNew;
use Bancard\Response\CardsNewResponse;
use Bancard\Tests\TestCase;

class CardsNewTest extends TestCase
{
    private Bancard $bancard;

    /** @var array<string, string> */
    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bancard = $this->makeBancard();
        $this->payload = [
            'card_id' => '456',
            'user_id' => '789',
            'user_cell_phone' => '0981123456',
            'user_mail' => 'user@example.com',
            'return_url' => 'https://example.com/return',
        ];
    }

    /**
     * user_cell_phone and user_mail are required by the spec.
     */
    public function testValidationRequiresUserContactFields(): void
    {
        $op = new CardsNew($this->bancard, ['card_id' => '456', 'user_id' => '789']);

        try {
            $op->execute();
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame(['user_cell_phone', 'user_mail'], $e->getErrors());
            $this->assertCount(0, $this->requestHistory);
        }
    }

    /**
     * Contact fields are sent but not part of the token.
     */
    public function testDataIncludesUserContactFields(): void
    {
        $data = (new CardsNew($this->bancard, $this->payload))->data();

        $this->assertSame('0981123456', $data['operation']['user_cell_phone']);
        $this->assertSame('user@example.com', $data['operation']['user_mail']);
    }

    public function testToken(): void
    {
        $op = new CardsNew($this->bancard, $this->payload);
        $expected = md5('test_private_key' . '456' . '789' . 'request_new_card');
        $this->assertSame($expected, $op->token());
    }

    public function testData(): void
    {
        $op = new CardsNew($this->bancard, $this->payload);
        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertSame('456', $data['operation']['card_id']);
        $this->assertSame('789', $data['operation']['user_id']);
        $this->assertArrayHasKey('token', $data['operation']);
    }

    public function testEndpoint(): void
    {
        $op = new CardsNew($this->bancard, $this->payload);
        $this->assertSame('/vpos/api/0.3/cards/new', $this->getProtectedProperty($op, 'endpoint'));
    }

    public function testExecuteReturnsCardsNewResponse(): void
    {
        $this->mockResponse(200, $this->fixtureArray('cards_new_success'));

        $op = new CardsNew($this->bancard, $this->payload);
        $response = $op->execute();

        $this->assertInstanceOf(CardsNewResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('card_proc_123', $response->getProcessId());
        $this->assertRequestSent('POST', '/vpos/api/0.3/cards/new');
    }
}
