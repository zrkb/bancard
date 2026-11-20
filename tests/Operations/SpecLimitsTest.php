<?php

declare(strict_types=1);

namespace Bancard\Tests\Operations;

use Bancard\Bancard;
use Bancard\Exception\ValidationException;
use Bancard\Operations\BillingCancel;
use Bancard\Operations\BillingClientInfo;
use Bancard\Operations\CardsNew;
use Bancard\Operations\Charge;
use Bancard\Operations\DeleteCard;
use Bancard\Operations\Operation;
use Bancard\Operations\PreauthorizationConfirm;
use Bancard\Operations\SingleBuy;
use Bancard\Operations\SingleBuyGetConfirmation;
use Bancard\Operations\SingleBuyRollback;
use Bancard\Operations\UsersCards;
use Bancard\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SpecLimitsTest extends TestCase
{
    private const SINGLE_BUY = ['shop_process_id' => '123', 'amount' => '10000.00', 'currency' => 'PYG'];

    private const CHARGE = self::SINGLE_BUY + ['alias_token' => 'alias123', 'description' => 'Order 123'];

    private const CARDS_NEW = [
        'card_id' => '1',
        'user_id' => '789',
        'user_cell_phone' => '0981123456',
        'user_mail' => 'user@example.com',
    ];

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
     * Payloads that break a spec limit, and the fields expected in the error.
     *
     * @return array<string, array{class-string<Operation<covariant \Bancard\Response\Response>>, array<string, mixed>, list<string>}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'description over 20 chars' => [SingleBuy::class, self::SINGLE_BUY + ['description' => str_repeat('a', 21)], ['description']],
            'description invalid UTF-8' => [SingleBuy::class, self::SINGLE_BUY + ['description' => "\xB1\x31"], ['description']],
            'currency over 3 chars' => [SingleBuy::class, ['currency' => 'PYGX'] + self::SINGLE_BUY, ['currency']],
            'return_url over 255 chars' => [SingleBuy::class, self::SINGLE_BUY + ['return_url' => 'https://x.test/' . str_repeat('a', 241)], ['return_url']],
            'shop_process_id 16 digits' => [SingleBuy::class, ['shop_process_id' => '1234567890123456'] + self::SINGLE_BUY, ['shop_process_id']],
            'shop_process_id not numeric' => [SingleBuy::class, ['shop_process_id' => 'order-1'] + self::SINGLE_BUY, ['shop_process_id']],
            'shop_process_id negative' => [SingleBuy::class, ['shop_process_id' => -1] + self::SINGLE_BUY, ['shop_process_id']],
            'several fields at once' => [
                SingleBuy::class,
                ['shop_process_id' => 'x'] + self::SINGLE_BUY + ['description' => str_repeat('a', 21)],
                ['shop_process_id', 'description'],
            ],
            'charge alias_token over 255' => [Charge::class, ['alias_token' => str_repeat('a', 256)] + self::CHARGE, ['alias_token']],
            'charge shop_process_id not numeric' => [Charge::class, ['shop_process_id' => 'x'] + self::CHARGE, ['shop_process_id']],
            'cards_new card_id not numeric' => [CardsNew::class, ['card_id' => 'abc'] + self::CARDS_NEW, ['card_id']],
            'cards_new user_id 20 digits' => [CardsNew::class, ['user_id' => str_repeat('9', 20)] + self::CARDS_NEW, ['user_id']],
            'cards_new user_mail over 255' => [CardsNew::class, ['user_mail' => str_repeat('a', 250) . '@x.com'] + self::CARDS_NEW, ['user_mail']],
            'users_cards user_id not numeric' => [UsersCards::class, ['user_id' => 'u-1'], ['user_id']],
            'delete alias_token over 255' => [DeleteCard::class, ['user_id' => '1', 'alias_token' => str_repeat('a', 256)], ['alias_token']],
            'get_confirmation shop_process_id' => [SingleBuyGetConfirmation::class, ['shop_process_id' => 'x'], ['shop_process_id']],
            'preauthorization shop_process_id' => [PreauthorizationConfirm::class, ['shop_process_id' => 'x'], ['shop_process_id']],
            'client_ruc over 32 chars' => [BillingClientInfo::class, ['client_ruc' => str_repeat('1', 33)], ['client_ruc']],
        ];
    }

    /**
     * Fields over the spec's limits throw ValidationException before any request.
     *
     * @param class-string<Operation<covariant \Bancard\Response\Response>> $class Operation class
     * @param array<string, mixed> $payload Payload
     * @param list<string> $fields Expected invalid fields
     */
    #[DataProvider('invalidPayloads')]
    public function testFieldsOverSpecLimitsAreRejected(string $class, array $payload, array $fields): void
    {
        try {
            (new $class($this->bancard, $payload))->execute();
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame($fields, $e->getErrors());
            $this->assertStringStartsWith('Invalid fields: ' . $fields[0] . ' (', $e->getMessage());
            $this->assertCount(0, $this->requestHistory);
        }
    }

    /**
     * Values at the spec's limits are accepted.
     */
    public function testValuesAtSpecLimitsAreAccepted(): void
    {
        $this->mockResponse(200, $this->fixtureArray('single_buy_success'));

        (new SingleBuy($this->bancard, [
            'shop_process_id' => 123456789012345,
            'amount' => '1234567890123.99',
            'currency' => 'PYG',
            'description' => str_repeat('ñ', 20),
        ]))->execute();

        $body = $this->getRequestBody();
        $this->assertSame(str_repeat('ñ', 20), $body['operation']['description']);
        $this->assertSame('1234567890123.99', $body['operation']['amount']);
    }

    /**
     * Numbers in string fields are measured as strings; arrays are rejected.
     */
    public function testStringFieldsMeasureNumbersAndRejectArrays(): void
    {
        $this->mockResponse(200, $this->fixtureArray('single_buy_success'));
        (new SingleBuy($this->bancard, self::SINGLE_BUY + ['description' => 12345]))->execute();
        $this->assertCount(1, $this->requestHistory);

        try {
            (new SingleBuy($this->bancard, self::SINGLE_BUY + ['description' => ['x']]))->execute();
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame(['description'], $e->getErrors());
            $this->assertCount(1, $this->requestHistory);
        }
    }

    /**
     * Amounts above Decimal (15,2) are rejected.
     */
    public function testAmountOverThirteenIntegerDigitsIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid amount for fields: amount');

        (new SingleBuy($this->bancard, ['amount' => '12345678901234.00'] + self::SINGLE_BUY))->execute();
    }

    /**
     * Rollback and billing cancel type shop_process_id as a plain string in the spec.
     */
    public function testRollbackAndBillingCancelAcceptStringShopProcessId(): void
    {
        $this->mockResponse(200, $this->fixtureArray('rollback_success'));
        $this->mockResponse(200, $this->fixtureArray('billing_cancel_success'));

        (new SingleBuyRollback($this->bancard, ['shop_process_id' => 'order-1']))->execute();
        (new BillingCancel($this->bancard, ['shop_process_id' => 'order-1']))->execute();

        $this->assertCount(2, $this->requestHistory);
    }

    /**
     * client_ruc is required for billing client info.
     */
    public function testBillingClientInfoRequiresClientRuc(): void
    {
        try {
            (new BillingClientInfo($this->bancard, []))->execute();
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame(['client_ruc'], $e->getErrors());
        }
    }

    /**
     * number_of_payments defaults to 1, as the spec requires for debit cards.
     */
    public function testChargeDefaultsNumberOfPaymentsToOne(): void
    {
        $data = (new Charge($this->bancard, self::CHARGE))->data();

        $this->assertSame(1, $data['operation']['number_of_payments']);
    }

    /**
     * An explicit number_of_payments is kept, and null opts out of sending it.
     */
    public function testChargeKeepsExplicitNumberOfPayments(): void
    {
        $this->assertSame(3, (new Charge($this->bancard, self::CHARGE + ['number_of_payments' => 3]))->data()['operation']['number_of_payments']);
        $this->assertArrayNotHasKey(
            'number_of_payments',
            (new Charge($this->bancard, self::CHARGE + ['number_of_payments' => null]))->data()['operation']
        );
    }

    /**
     * chargeCard() sends the default number_of_payments too.
     */
    public function testChargeCardSendsDefaultNumberOfPayments(): void
    {
        $this->mockResponse(200, $this->fixtureArray('users_cards_success'));
        $this->mockResponse(200, $this->fixtureArray('charge_success'));

        $this->bancard->chargeCard(['user_id' => '789', 'card_id' => 1, 'description' => 'Order 123'] + self::SINGLE_BUY);

        /** @var \GuzzleHttp\Psr7\Request $charge */
        $charge = $this->requestHistory[1]['request'];
        /** @var array{operation: array<string, mixed>} $body */
        $body = json_decode((string) $charge->getBody(), true);

        $this->assertSame(1, $body['operation']['number_of_payments']);
    }
}
