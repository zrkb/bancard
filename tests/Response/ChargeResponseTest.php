<?php

declare(strict_types=1);

namespace Bancard\Tests\Response;

use Bancard\Response\ChargeResponse;
use Bancard\Tests\TestCase;

class ChargeResponseTest extends TestCase
{
    /**
     * ChargeResponse: is approved.
     */
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

    /**
     * ChargeResponse: is not approved.
     */
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

    /**
     * ChargeResponse: get process id.
     */
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

    /**
     * ChargeResponse: get response code.
     */
    public function testGetResponseCode(): void
    {
        $response = new ChargeResponse((object) [
            'status' => 'success',
            'confirmation' => (object) ['response_code' => '00'],
        ]);

        $this->assertSame('00', $response->getResponseCode());
    }

    /**
     * ChargeResponse: is 3DS redirect.
     */
    public function testIs3dsRedirect(): void
    {
        $response = new ChargeResponse((object) [
            'status' => 'success',
            'redirect_agent' => true,
        ]);

        $this->assertTrue($response->is3dsRedirect());
    }

    /**
     * Is not 3DS redirect.
     */
    public function testIsNot3dsRedirect(): void
    {
        $response = new ChargeResponse((object) [
            'status' => 'success',
        ]);

        $this->assertFalse($response->is3dsRedirect());
    }
}
