<?php

declare(strict_types=1);

namespace Bancard\Tests\Operations;

use Bancard\Bancard;
use Bancard\Exception\ValidationException;
use Bancard\Operations\Charge;
use Bancard\Operations\Operation;
use Bancard\Response\Response;
use Bancard\Operations\SingleBuy;
use Bancard\Operations\SingleBuyConfirm;
use Bancard\Tests\TestCase;
use Bancard\Util\Currency;
use PHPUnit\Framework\Attributes\DataProvider;

class PayloadNormalizationTest extends TestCase
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
     * Unformatted amounts and the formatted value that must be sent and signed.
     *
     * @return array<string, array{int|float|string, string}>
     */
    public static function unformattedAmounts(): array
    {
        return [
            'int' => [10000, '10000.00'],
            'float' => [10000.5, '10000.50'],
            'integer string' => ['10000', '10000.00'],
            'one decimal string' => ['10000.5', '10000.50'],
        ];
    }

    /**
     * Amounts that must be rejected before any request is sent.
     *
     * @return array<string, array{int|float|string}>
     */
    public static function invalidAmounts(): array
    {
        return [
            'thousands separator' => ['10,000'],
            'three decimals' => ['1.005'],
            'negative' => [-1],
            'non numeric' => ['abc'],
        ];
    }

    /**
     * Unformatted amounts are formatted in both the request body and the token.
     */
    #[DataProvider('unformattedAmounts')]
    public function testAmountIsFormattedInBodyAndToken(int|float|string $amount, string $expected): void
    {
        $this->mockResponse(200, $this->fixtureArray('single_buy_success'));

        $this->bancard->singleBuy([
            'shop_process_id' => '123',
            'amount' => $amount,
            'currency' => 'PYG',
        ]);

        $body = $this->getRequestBody();
        $this->assertSame($expected, $body['operation']['amount']);
        $this->assertSame(md5('test_private_key' . '123' . $expected . 'PYG'), $body['operation']['token']);
    }

    /**
     * The confirm token uses the formatted amount.
     */
    public function testConfirmTokenUsesFormattedAmount(): void
    {
        $op = new SingleBuyConfirm($this->bancard, [
            'shop_process_id' => '123',
            'amount' => 10000,
            'currency' => 'PYG',
        ]);

        $this->assertSame(md5('test_private_key' . '123' . 'confirm' . '10000.00' . 'PYG'), $op->token());
    }

    /**
     * Invalid amounts throw ValidationException and send no request.
     */
    #[DataProvider('invalidAmounts')]
    public function testInvalidAmountThrowsValidationException(int|float|string $amount): void
    {
        $op = new SingleBuy($this->bancard, [
            'shop_process_id' => '123',
            'amount' => $amount,
            'currency' => 'PYG',
        ]);

        try {
            $op->execute();
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame(['amount'], $e->getErrors());
            $this->assertStringContainsString('Invalid amount for fields: amount', $e->getMessage());
            $this->assertCount(0, $this->requestHistory);
        }
    }

    /**
     * iva_amount is formatted like amount.
     */
    public function testIvaAmountIsFormatted(): void
    {
        $op = new SingleBuy($this->bancard, [
            'shop_process_id' => '123',
            'amount' => '11000.00',
            'currency' => 'PYG',
            'iva_amount' => 1000,
        ]);

        $this->assertSame('1000.00', $op->data()['operation']['iva_amount']);
    }

    /**
     * An invalid iva_amount is rejected.
     */
    public function testInvalidIvaAmountThrowsValidationException(): void
    {
        $op = new SingleBuy($this->bancard, [
            'shop_process_id' => '123',
            'amount' => '11000.00',
            'currency' => 'PYG',
            'iva_amount' => '1.000,00',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid amount for fields: iva_amount');

        $op->execute();
    }

    /**
     * The Currency enum can be passed directly.
     */
    public function testCurrencyEnumIsAccepted(): void
    {
        $this->mockResponse(200, $this->fixtureArray('charge_success'));

        $this->bancard->charge([
            'shop_process_id' => '123',
            'amount' => '10000.00',
            'currency' => Currency::USD,
            'alias_token' => 'alias123',
        ]);

        $body = $this->getRequestBody();
        $this->assertSame('USD', $body['operation']['currency']);
        $this->assertSame(
            md5('test_private_key' . '123' . 'charge' . '10000.00' . 'USD' . 'alias123'),
            $body['operation']['token']
        );
    }

    /**
     * The enum gives the same token as its string value.
     */
    public function testCurrencyEnumTokenMatchesStringToken(): void
    {
        $payload = ['shop_process_id' => '1', 'amount' => '1.00', 'alias_token' => 'a'];

        $this->assertSame(
            (new Charge($this->bancard, $payload + ['currency' => 'PYG']))->token(),
            (new Charge($this->bancard, $payload + ['currency' => Currency::PYG]))->token()
        );
    }

    /**
     * Path parameters and their encoded request paths.
     *
     * @return array<string, array{string, string}>
     */
    public static function pathParameters(): array
    {
        return [
            'plain id' => ['789', '/test/789/resource'],
            'traversal with slashes' => ['../../single_buy/rollback?x=', '/test/%2E%2E%2F%2E%2E%2Fsingle_buy%2Frollback%3Fx%3D/resource'],
            'dot dot' => ['..', '/test/%2E%2E/resource'],
            'single dot' => ['.', '/test/%2E/resource'],
            'hash and space' => ['a b#c', '/test/a%20b%23c/resource'],
        ];
    }

    /**
     * Path parameters are URL-encoded so they cannot change the target endpoint.
     *
     * Uses an operation without integer rules, since users_cards/delete reject
     * non-integer user IDs before encoding is reached.
     */
    #[DataProvider('pathParameters')]
    public function testPathParametersAreEncoded(string $id, string $expectedPath): void
    {
        $this->mockResponse(200, ['status' => 'success']);

        $op = new class ($this->bancard, ['id' => $id]) extends Operation {
            protected string $endpoint = '/test/{id}/resource';

            /** @var class-string<Response> */
            protected string $responseClass = Response::class;

            /**
             * Generate a fixed token.
             *
             * @return string
             */
            public function token(): string
            {
                return 'token';
            }
        };

        $op->execute();

        $this->assertRequestSent('POST', $expectedPath);
    }

    /**
     * Non-integer user IDs are rejected before any request on endpoints with a {user_id} path.
     */
    public function testNonIntegerUserIdIsRejectedBeforeRequest(): void
    {
        foreach ([
            fn () => $this->bancard->usersCards(['user_id' => '../../single_buy/rollback?x=']),
            fn () => $this->bancard->deleteCard(['user_id' => '..', 'alias_token' => 'alias123']),
        ] as $call) {
            try {
                $call();
                $this->fail('Expected ValidationException was not thrown.');
            } catch (ValidationException $e) {
                $this->assertSame(['user_id'], $e->getErrors());
            }
        }

        $this->assertCount(0, $this->requestHistory);
    }
}
