<?php

namespace Bancard\Tests\Operations;

use Bancard\Operations\UsersCards;
use Bancard\Tests\TestCase;

class UsersCardsTest extends TestCase
{
    /** @var array<string, string> */
    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->payload = [
            'user_id' => '789',
        ];
    }

    public function testToken(): void
    {
        $op = new UsersCards($this->payload);
        $expected = md5('test_private_key' . '789' . 'request_user_cards');
        $this->assertSame($expected, $op->token());
    }

    public function testData(): void
    {
        $op = new UsersCards($this->payload);
        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertSame('789', $data['operation']['user_id']);
        $this->assertArrayHasKey('token', $data['operation']);
    }

    public function testEndpoint(): void
    {
        $op = new UsersCards($this->payload);
        $this->assertSame(
            '/vpos/api/0.3/users/{user_id}/cards',
            $this->getProtectedProperty($op, 'endpoint')
        );
    }
}
