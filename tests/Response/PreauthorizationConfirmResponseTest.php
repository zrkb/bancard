<?php

declare(strict_types=1);

namespace Bancard\Tests\Response;

use Bancard\Response\PreauthorizationConfirmResponse;
use Bancard\Tests\TestCase;

class PreauthorizationConfirmResponseTest extends TestCase
{
    /**
     * PreauthorizationConfirmResponse: is approved.
     */
    public function testIsApproved(): void
    {
        $response = new PreauthorizationConfirmResponse((object) [
            'status' => 'success',
            'confirmation' => (object) ['response_code' => '00'],
        ]);

        $this->assertTrue($response->isApproved());
    }

    /**
     * PreauthorizationConfirmResponse: is not approved.
     */
    public function testIsNotApproved(): void
    {
        $response = new PreauthorizationConfirmResponse((object) [
            'status' => 'success',
            'confirmation' => (object) ['response_code' => '51'],
        ]);

        $this->assertFalse($response->isApproved());
    }

    /**
     * PreauthorizationConfirmResponse: get confirmation.
     */
    public function testGetConfirmation(): void
    {
        $confirmation = (object) ['response_code' => '00'];
        $response = new PreauthorizationConfirmResponse((object) [
            'status' => 'success',
            'confirmation' => $confirmation,
        ]);

        $this->assertSame($confirmation, $response->getConfirmation());
    }

    /**
     * Get confirmation returns null.
     */
    public function testGetConfirmationReturnsNull(): void
    {
        $response = new PreauthorizationConfirmResponse((object) ['status' => 'success']);
        $this->assertNull($response->getConfirmation());
    }
}
