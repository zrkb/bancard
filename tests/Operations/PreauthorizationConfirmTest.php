<?php

namespace Bancard\Tests\Operations;

use Bancard\Operations\PreauthorizationConfirm;
use Bancard\Tests\TestCase;

class PreauthorizationConfirmTest extends TestCase
{
    /** @var array<string, string> */
    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->payload = [
            'shop_process_id' => '123',
        ];
    }

    public function testToken(): void
    {
        $op = new PreauthorizationConfirm($this->payload);
        $expected = md5('test_private_key' . '123' . 'pre-authorization-confirm');
        $this->assertSame($expected, $op->token());
    }

    public function testData(): void
    {
        $op = new PreauthorizationConfirm($this->payload);
        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertSame('123', $data['operation']['shop_process_id']);
        $this->assertArrayHasKey('token', $data['operation']);
    }

    public function testEndpoint(): void
    {
        $op = new PreauthorizationConfirm($this->payload);
        $this->assertSame(
            '/vpos/api/0.3/preauthorizations/confirm',
            $this->getProtectedProperty($op, 'endpoint')
        );
    }
}
