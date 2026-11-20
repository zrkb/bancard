<?php

declare(strict_types=1);

namespace Bancard\Tests;

use Bancard\Bancard;
use Bancard\Exception\ApiException;
use Bancard\Exception\CardNotFoundException;
use Bancard\Exception\ValidationException;
use Bancard\Util\Currency;

class ChargeCardTest extends TestCase
{
    private Bancard $bancard;

    /**
     * Create a Bancard client with a mocked HTTP handler.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->bancard = $this->makeBancard();
    }

    /**
     * The alias token is looked up by card_id and used for the charge.
     */
    public function testChargesCardWithFreshAliasToken(): void
    {
        $this->mockResponse(200, $this->fixtureArray('users_cards_success'));
        $this->mockResponse(200, $this->fixtureArray('charge_success'));

        $response = $this->bancard->chargeCard([
            'user_id' => '789',
            'card_id' => '2',
            'shop_process_id' => '123',
            'amount' => 10000,
            'currency' => Currency::PYG,
            'number_of_payments' => 1,
        ]);

        $this->assertTrue($response->isApproved());
        $this->assertCount(2, $this->requestHistory);

        /** @var \GuzzleHttp\Psr7\Request $lookup */
        $lookup = $this->requestHistory[0]['request'];
        $this->assertSame('/vpos/api/0.3/users/789/cards', $lookup->getUri()->getPath());

        /** @var \GuzzleHttp\Psr7\Request $charge */
        $charge = $this->requestHistory[1]['request'];
        $this->assertSame('/vpos/api/0.3/charge', $charge->getUri()->getPath());

        /** @var array{operation: array<string, mixed>} $body */
        $body = json_decode((string) $charge->getBody(), true);
        $operation = $body['operation'];

        $this->assertSame('token_def', $operation['alias_token']);
        $this->assertSame('10000.00', $operation['amount']);
        $this->assertSame('PYG', $operation['currency']);
        $this->assertSame(1, $operation['number_of_payments']);
        $this->assertArrayNotHasKey('user_id', $operation);
        $this->assertArrayNotHasKey('card_id', $operation);
        $this->assertSame(
            md5('test_private_key' . '123' . 'charge' . '10000.00' . 'PYG' . 'token_def'),
            $operation['token']
        );
    }

    /**
     * An integer card_id matches the numeric card_id returned by Bancard.
     */
    public function testIntegerCardIdMatches(): void
    {
        $this->mockResponse(200, $this->fixtureArray('users_cards_success'));
        $this->mockResponse(200, $this->fixtureArray('charge_success'));

        $this->bancard->chargeCard([
            'user_id' => 789,
            'card_id' => 1,
            'shop_process_id' => '123',
            'amount' => '10000.00',
            'currency' => 'PYG',
        ]);

        /** @var \GuzzleHttp\Psr7\Request $charge */
        $charge = $this->requestHistory[1]['request'];
        /** @var array{operation: array<string, mixed>} $body */
        $body = json_decode((string) $charge->getBody(), true);

        $this->assertSame('token_abc', $body['operation']['alias_token']);
    }

    /**
     * An unknown card_id throws CardNotFoundException without charging.
     */
    public function testUnknownCardThrowsCardNotFoundException(): void
    {
        $this->mockResponse(200, $this->fixtureArray('users_cards_success'));

        try {
            $this->bancard->chargeCard([
                'user_id' => '789',
                'card_id' => '99',
                'shop_process_id' => '123',
                'amount' => '10000.00',
                'currency' => 'PYG',
            ]);
            $this->fail('Expected CardNotFoundException was not thrown.');
        } catch (CardNotFoundException $e) {
            $this->assertSame('789', $e->getUserId());
            $this->assertSame('99', $e->getCardId());
            $this->assertCount(1, $this->requestHistory);
        }
    }

    /**
     * A matching card without an alias token is treated as not found.
     */
    public function testCardWithoutAliasTokenThrowsCardNotFoundException(): void
    {
        $this->mockResponse(200, [
            'status' => 'success',
            'cards' => [['card_id' => 5, 'card_masked_number' => '****0000']],
        ]);

        $this->expectException(CardNotFoundException::class);

        $this->bancard->chargeCard([
            'user_id' => '789',
            'card_id' => 5,
            'shop_process_id' => '123',
            'amount' => '10000.00',
            'currency' => 'PYG',
        ]);
    }

    /**
     * Missing fields are reported before any request is sent.
     */
    public function testMissingFieldsThrowBeforeAnyRequest(): void
    {
        try {
            $this->bancard->chargeCard([]); // @phpstan-ignore argument.type
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame(['user_id', 'card_id', 'shop_process_id', 'amount', 'currency'], $e->getErrors());
            $this->assertCount(0, $this->requestHistory);
        }
    }

    /**
     * A users_cards API error propagates as ApiException and nothing is charged.
     */
    public function testLookupErrorPropagates(): void
    {
        $this->mockResponse(200, $this->fixtureArray('generic_error'));

        try {
            $this->bancard->chargeCard([
                'user_id' => '789',
                'card_id' => '1',
                'shop_process_id' => '123',
                'amount' => '10000.00',
                'currency' => 'PYG',
            ]);
            $this->fail('Expected ApiException was not thrown.');
        } catch (ApiException $e) {
            $this->assertSame('GenericError', $e->getErrorKey());
            $this->assertCount(1, $this->requestHistory);
        }
    }
}
