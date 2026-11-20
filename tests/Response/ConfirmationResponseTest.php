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

    /**
     * Confirmation details are read from the spec's get_confirmation example.
     */
    public function testConfirmationDetails(): void
    {
        $response = new ConfirmationResponse((object) [
            'status' => 'success',
            'confirmation' => (object) [
                'shop_process_id' => '12313',
                'response_code' => '00',
                'response_description' => 'Transacción aprobada.',
                'authorization_number' => '123456',
                'ticket_number' => '123456789123456',
                'amount' => '10100.00',
                'currency' => 'PYG',
                'security_information' => (object) ['card_source' => 'L', 'risk_index' => '4'],
            ],
        ]);

        $this->assertSame('12313', $response->getShopProcessId());
        $this->assertSame('Transacción aprobada.', $response->getResponseDescription());
        $this->assertSame('123456', $response->getAuthorizationNumber());
        $this->assertSame('123456789123456', $response->getTicketNumber());
        $this->assertSame('10100.00', $response->getAmount());
        $this->assertSame('PYG', $response->getCurrency());
        $this->assertSame('L', $response->getCardSource());
        $this->assertSame(4, $response->getRiskIndex());
    }

    /**
     * Confirmation details are null without a confirmation.
     */
    public function testConfirmationDetailsAreNullWithoutConfirmation(): void
    {
        $response = new ConfirmationResponse((object) ['status' => 'success']);

        $this->assertNull($response->getTicketNumber());
        $this->assertNull($response->getSecurityInformation());
        $this->assertNull($response->getRiskIndex());
        $this->assertNull($response->getExtraAttributes());
        $this->assertNull($response->getPaymentCardType());
    }

    /**
     * extra_attributes as staging returns it for extra_response_attributes ["confirmation.extra_attributes"].
     */
    public function testExtraAttributes(): void
    {
        $response = new ConfirmationResponse((object) [
            'status' => 'success',
            'confirmation' => (object) [
                'response_code' => '00',
                'extra_attributes' => (object) [
                    'payment_card_type' => 'credit',
                    'payment_card_brand' => 'MC',
                    'payment_masked_card_number' => '541863******0014',
                    'payment_origin' => 'VPOS-TOKEN',
                ],
            ],
        ]);

        $this->assertSame('credit', $response->getPaymentCardType());
        $this->assertSame('MC', $response->getPaymentCardBrand());
        $this->assertSame('541863******0014', $response->getPaymentMaskedCardNumber());
        $this->assertSame('VPOS-TOKEN', $response->getPaymentOrigin());
        $this->assertSame('credit', $response->getExtraAttributes()?->payment_card_type);
    }

    /**
     * Extra attributes sent directly on the confirmation are read too.
     */
    public function testExtraAttributesOnConfirmation(): void
    {
        $response = new ConfirmationResponse((object) [
            'confirmation' => (object) ['payment_card_type' => 'debit', 'payment_origin' => 'VPOS-FORM'],
        ]);

        $this->assertSame('debit', $response->getPaymentCardType());
        $this->assertSame('VPOS-FORM', $response->getPaymentOrigin());
        $this->assertNull($response->getPaymentCardBrand());
    }
}
