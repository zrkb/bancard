<?php

declare(strict_types=1);

namespace Bancard\Tests;

use Bancard\Bancard;

class BancardTest extends TestCase
{
    public function testConstructorSetsConfig(): void
    {
        $bancard = new Bancard(
            publicKey: 'pk_123',
            privateKey: 'sk_456',
        );

        $this->assertSame('pk_123', $bancard->publicKey);
        $this->assertSame('sk_456', $bancard->privateKey);
        $this->assertFalse($bancard->staging);
    }

    public function testConstructorSetsStaging(): void
    {
        $bancard = new Bancard(
            publicKey: 'pk',
            privateKey: 'sk',
            staging: true,
        );

        $this->assertTrue($bancard->staging);
    }

    public function testStagingDefaultsToFalse(): void
    {
        $bancard = new Bancard(
            publicKey: 'pk',
            privateKey: 'sk',
        );

        $this->assertFalse($bancard->staging);
    }

    public function testBaseUriReturnsProductionUrl(): void
    {
        $bancard = new Bancard(
            publicKey: 'pk',
            privateKey: 'sk',
            staging: false,
        );

        $this->assertSame('https://vpos.infonet.com.py/', $bancard->baseUri());
    }

    public function testBaseUriReturnsStagingUrl(): void
    {
        $bancard = new Bancard(
            publicKey: 'pk',
            privateKey: 'sk',
            staging: true,
        );

        $this->assertSame('https://vpos.infonet.com.py:8888/', $bancard->baseUri());
    }

    public function testSingleBuyConvenienceMethod(): void
    {
        $bancard = $this->makeBancard();
        $this->mockResponse(200, $this->fixtureArray('single_buy_success'));

        $response = $bancard->singleBuy([
            'shop_process_id' => '123',
            'amount' => '10000.00',
            'currency' => 'PYG',
            'return_url' => 'https://example.com/return',
            'cancel_url' => 'https://example.com/cancel',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('abc123', $response->getProcessId());
        $this->assertRequestSent('POST', '/vpos/api/0.3/single_buy');
    }

    public function testSingleBuyZimpleSetsZimpleFlag(): void
    {
        $bancard = $this->makeBancard();
        $this->mockResponse(200, $this->fixtureArray('single_buy_success'));

        $response = $bancard->singleBuyZimple([
            'shop_process_id' => '123',
            'amount' => '10000.00',
            'currency' => 'PYG',
            'return_url' => 'https://example.com/return',
            'cancel_url' => 'https://example.com/cancel',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertRequestSent('POST', '/vpos/api/0.3/single_buy');

        $body = $this->getRequestBody();
        $this->assertSame('S', $body['operation']['zimple']);
    }

    public function testChargeConvenienceMethod(): void
    {
        $bancard = $this->makeBancard();
        $this->mockResponse(200, $this->fixtureArray('charge_success'));

        $response = $bancard->charge([
            'shop_process_id' => '123',
            'amount' => '10000.00',
            'currency' => 'PYG',
            'alias_token' => 'alias123',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->isApproved());
        $this->assertRequestSent('POST', '/vpos/api/0.3/charge');
    }
}
