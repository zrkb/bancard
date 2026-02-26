<?php

declare(strict_types=1);

namespace Bancard\Tests\Response;

use Bancard\Response\ConfirmationResponse;
use Bancard\Tests\TestCase;

class ConfirmationResponseTest extends TestCase
{
    public function testIsApprovedWhenResponseCodeIs00(): void
    {
        $response = new ConfirmationResponse((object) [
            'status' => 'success',
            'confirmation' => (object) [
                'response_code' => '00',
            ],
        ]);

        $this->assertTrue($response->isApproved());
    }

    public function testIsNotApprovedWhenResponseCodeIsNot00(): void
    {
        $response = new ConfirmationResponse((object) [
            'status' => 'success',
            'confirmation' => (object) [
                'response_code' => '12',
            ],
        ]);

        $this->assertFalse($response->isApproved());
    }

    public function testIsNotApprovedWhenNoConfirmation(): void
    {
        $response = new ConfirmationResponse((object) ['status' => 'success']);
        $this->assertFalse($response->isApproved());
    }

    public function testGetConfirmation(): void
    {
        $confirmation = (object) ['response_code' => '00'];
        $response = new ConfirmationResponse((object) [
            'status' => 'success',
            'confirmation' => $confirmation,
        ]);

        $this->assertSame($confirmation, $response->getConfirmation());
    }

    public function testGetConfirmationReturnsNullWhenMissing(): void
    {
        $response = new ConfirmationResponse((object) ['status' => 'success']);
        $this->assertNull($response->getConfirmation());
    }

    public function testGetResponseCode(): void
    {
        $response = new ConfirmationResponse((object) [
            'status' => 'success',
            'confirmation' => (object) ['response_code' => '00'],
        ]);

        $this->assertSame('00', $response->getResponseCode());
    }

    public function testGetResponseCodeReturnsNullWhenNoConfirmation(): void
    {
        $response = new ConfirmationResponse((object) ['status' => 'success']);
        $this->assertNull($response->getResponseCode());
    }
}
