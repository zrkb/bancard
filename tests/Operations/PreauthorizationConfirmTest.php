<?php

declare(strict_types=1);

namespace Bancard\Tests\Operations;

use Bancard\Bancard;
use Bancard\Operations\PreauthorizationConfirm;
use Bancard\Response\PreauthorizationConfirmResponse;
use Bancard\Tests\TestCase;

class PreauthorizationConfirmTest extends TestCase
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
        $op = new PreauthorizationConfirm($this->bancard, $this->payload);
        $expected = md5('test_private_key' . '123' . 'pre-authorization-confirm');
        $this->assertSame($expected, $op->token());
    }

    public function testData(): void
    {
        $op = new PreauthorizationConfirm($this->bancard, $this->payload);
        $data = $op->data();

        $this->assertSame('test_public_key', $data['public_key']);
        $this->assertSame('123', $data['operation']['shop_process_id']);
        $this->assertArrayHasKey('token', $data['operation']);
    }

    public function testEndpoint(): void
    {
        $op = new PreauthorizationConfirm($this->bancard, $this->payload);
        $this->assertSame(
            '/vpos/api/0.3/preauthorizations/confirm',
            $this->getProtectedProperty($op, 'endpoint')
        );
    }

    public function testExecuteReturnsPreauthorizationConfirmResponse(): void
    {
        $this->mockResponse(200, $this->fixtureArray('preauthorization_confirm_success'));

        $op = new PreauthorizationConfirm($this->bancard, $this->payload);
        $response = $op->execute();

        $this->assertInstanceOf(PreauthorizationConfirmResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->isApproved());
        $this->assertRequestSent('POST', '/vpos/api/0.3/preauthorizations/confirm');
    }
}
