<?php

declare(strict_types=1);

namespace Bancard\Tests\Response;

use Bancard\Response\ConfirmationResponse;
use Bancard\Tests\TestCase;

class ConfirmationResponseTest extends TestCase
{
    /**
     * Is approved when response code is "00".
     */
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

    /**
     * Is not approved when response code is not "00".
     */
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

    /**
     * Is not approved when no confirmation.
     */
    public function testIsNotApprovedWhenNoConfirmation(): void
    {
        $response = new ConfirmationResponse((object) ['status' => 'success']);
        $this->assertFalse($response->isApproved());
    }

    /**
     * ConfirmationResponse: get confirmation.
     */
    public function testGetConfirmation(): void
    {
        $confirmation = (object) ['response_code' => '00'];
        $response = new ConfirmationResponse((object) [
            'status' => 'success',
            'confirmation' => $confirmation,
        ]);

        $this->assertSame($confirmation, $response->getConfirmation());
    }

    /**
     * Get confirmation returns null when missing.
     */
    public function testGetConfirmationReturnsNullWhenMissing(): void
    {
        $response = new ConfirmationResponse((object) ['status' => 'success']);
        $this->assertNull($response->getConfirmation());
    }

    /**
     * ConfirmationResponse: get response code.
     */
    public function testGetResponseCode(): void
    {
        $response = new ConfirmationResponse((object) [
            'status' => 'success',
            'confirmation' => (object) ['response_code' => '00'],
        ]);

        $this->assertSame('00', $response->getResponseCode());
    }

    /**
     * Get response code returns null when no confirmation.
     */
    public function testGetResponseCodeReturnsNullWhenNoConfirmation(): void
    {
        $response = new ConfirmationResponse((object) ['status' => 'success']);
        $this->assertNull($response->getResponseCode());
    }
}
