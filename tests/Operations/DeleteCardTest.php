<?php

namespace Bancard\Tests\Operations;

use Bancard\Operations\DeleteCard;
use Bancard\Tests\TestCase;

class DeleteCardTest extends TestCase
{
    /** @var array<string, string> */
    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->payload = [
            'user_id' => '789',
            'alias_token' => 'alias123',
        ];
    }

    public function testToken(): void
    {
        $op = new DeleteCard($this->payload);
        $expected = md5('test_private_key' . 'delete_card' . '789' . 'alias123');
        $this->assertSame($expected, $op->token());
    }

    public function testData(): void
    {
        $op = new DeleteCard($this->payload);
        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertSame('789', $data['operation']['user_id']);
        $this->assertSame('alias123', $data['operation']['alias_token']);
        $this->assertArrayHasKey('token', $data['operation']);
    }

    public function testEndpoint(): void
    {
        $op = new DeleteCard($this->payload);
        $this->assertSame(
            '/vpos/api/0.3/users/{user_id}/cards',
            $this->getProtectedProperty($op, 'endpoint')
        );
    }

    public function testMethodIsDelete(): void
    {
        $op = new DeleteCard($this->payload);
        $this->assertSame('DELETE', $this->getProtectedProperty($op, 'method'));
    }
}
