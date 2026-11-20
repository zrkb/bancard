<?php

declare(strict_types=1);

namespace Bancard\Tests\Response;

use Bancard\Response\BillingClientInfoResponse;
use Bancard\Response\CardsNewResponse;
use Bancard\Response\ChargeResponse;
use Bancard\Response\ConfirmationResponse;
use Bancard\Response\PreauthorizationConfirmResponse;
use Bancard\Response\Response;
use Bancard\Response\SingleBuyResponse;
use Bancard\Tests\TestCase;

class ValueCoercionTest extends TestCase
{
    /**
     * Numeric IDs and codes are returned as strings instead of raising TypeError.
     */
    public function testNumericValuesAreReturnedAsStrings(): void
    {
        $confirmation = (object) ['shop_process_id' => 123, 'response_code' => 51];

        $charge = new ChargeResponse((object) ['status' => 'success', 'confirmation' => $confirmation]);
        $this->assertSame('123', $charge->getProcessId());
        $this->assertSame('51', $charge->getResponseCode());
        $this->assertFalse($charge->isApproved());

        $status = new ConfirmationResponse((object) ['status' => 'success', 'confirmation' => $confirmation]);
        $this->assertSame('51', $status->getResponseCode());
        $this->assertFalse($status->isApproved());

        $this->assertSame('42', (new SingleBuyResponse((object) ['process_id' => 42]))->getProcessId());
        $this->assertSame('42', (new CardsNewResponse((object) ['process_id' => 42]))->getProcessId());
    }

    /**
     * Numeric message keys and descriptions are returned as strings.
     */
    public function testNumericMessageValuesAreReturnedAsStrings(): void
    {
        $response = new Response((object) [
            'status' => 'error',
            'messages' => [(object) ['key' => 500, 'dsc' => 404]],
        ]);

        $this->assertSame('500', $response->getErrorKey());
        $this->assertSame('404', $response->getMessage());
    }

    /**
     * Non-scalar values where a string is expected return null.
     */
    public function testNonScalarValuesReturnNull(): void
    {
        $response = new ChargeResponse((object) [
            'status' => ['success'],
            'confirmation' => (object) ['shop_process_id' => ['x'], 'response_code' => true],
        ]);

        $this->assertNull($response->getStatus());
        $this->assertFalse($response->isSuccessful());
        $this->assertNull($response->getProcessId());
        $this->assertNull($response->getResponseCode());
        $this->assertFalse($response->isApproved());
    }

    /**
     * Non-object confirmation/client values return null instead of raising TypeError.
     */
    public function testNonObjectConfirmationReturnsNull(): void
    {
        $data = (object) ['status' => 'success', 'confirmation' => 'oops', 'client' => [1, 2]];

        $this->assertNull((new ConfirmationResponse($data))->getConfirmation());
        $this->assertFalse((new ConfirmationResponse($data))->isApproved());
        $this->assertNull((new PreauthorizationConfirmResponse($data))->getConfirmation());
        $this->assertFalse((new PreauthorizationConfirmResponse($data))->isApproved());
        $this->assertNull((new ChargeResponse($data))->getProcessId());
        $this->assertNull((new BillingClientInfoResponse($data))->getClient());
    }
}
