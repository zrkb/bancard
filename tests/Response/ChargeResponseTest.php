<?php

declare(strict_types=1);

namespace Bancard\Tests\Response;

use Bancard\Response\ChargeResponse;
use Bancard\Tests\TestCase;

class ChargeResponseTest extends TestCase
{
    public function testIsApproved(): void
    {
        $response = new ChargeResponse((object) [
            'status' => 'success',
            'confirmation' => (object) [
                'shop_process_id' => '123',
                'response_code' => '00',
            ],
        ]);

        $this->assertTrue($response->isApproved());
    }

    public function testIsNotApproved(): void
    {
        $response = new ChargeResponse((object) [
            'status' => 'success',
            'confirmation' => (object) [
                'response_code' => '51',
            ],
        ]);

        $this->assertFalse($response->isApproved());
    }

    public function testGetProcessId(): void
    {
        $response = new ChargeResponse((object) [
            'status' => 'success',
            'confirmation' => (object) [
                'shop_process_id' => '123',
                'response_code' => '00',
            ],
        ]);

        $this->assertSame('123', $response->getProcessId());
    }

    public function testGetResponseCode(): void
    {
        $response = new ChargeResponse((object) [
            'status' => 'success',
            'confirmation' => (object) ['response_code' => '00'],
        ]);

        $this->assertSame('00', $response->getResponseCode());
    }

    public function testIs3dsRedirect(): void
    {
        $response = new ChargeResponse((object) [
            'status' => 'success',
            'redirect_agent' => true,
        ]);

        $this->assertTrue($response->is3dsRedirect());
    }

    public function testIsNot3dsRedirect(): void
    {
        $response = new ChargeResponse((object) [
            'status' => 'success',
        ]);

        $this->assertFalse($response->is3dsRedirect());
    }
}
