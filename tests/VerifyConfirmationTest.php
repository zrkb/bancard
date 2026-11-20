<?php

declare(strict_types=1);

namespace Bancard\Tests;

use Bancard\Bancard;
use PHPUnit\Framework\Attributes\DataProvider;

class VerifyConfirmationTest extends TestCase
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
     * Build a callback operation as Bancard sends it to the confirmation URL.
     *
     * @param array<string, mixed> $overrides Fields to replace
     * @return array<string, mixed>
     */
    private function operation(array $overrides = []): array
    {
        return $overrides + [
            'token' => md5('test_private_key' . '123' . 'confirm' . '10100.00' . 'PYG'),
            'shop_process_id' => '123',
            'response' => 'S',
            'response_details' => 'Procesado Satisfactoriamente',
            'amount' => '10100.00',
            'currency' => 'PYG',
            'response_code' => '00',
        ];
    }

    /**
     * A correctly signed confirmation is accepted.
     */
    public function testValidTokenIsAccepted(): void
    {
        $this->assertTrue($this->bancard->verifyConfirmation($this->operation()));
    }

    /**
     * Numeric shop_process_id from JSON gives the same token.
     */
    public function testNumericShopProcessIdIsAccepted(): void
    {
        $this->assertTrue($this->bancard->verifyConfirmation($this->operation(['shop_process_id' => 123])));
    }

    /**
     * Verification makes no HTTP request.
     */
    public function testNoHttpRequestIsMade(): void
    {
        $this->bancard->verifyConfirmation($this->operation());

        $this->assertCount(0, $this->requestHistory);
    }

    /**
     * Tampered or malformed confirmations are rejected without throwing.
     *
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidOperations(): array
    {
        return [
            'tampered amount' => [['amount' => '1.00']],
            'tampered currency' => [['currency' => 'USD']],
            'tampered shop_process_id' => [['shop_process_id' => '124']],
            'wrong token' => [['token' => md5('nope')]],
            'empty token' => [['token' => '']],
            'non-string token' => [['token' => 123]],
            'null token' => [['token' => null]],
            'null amount' => [['amount' => null]],
            'array shop_process_id' => [['shop_process_id' => ['123']]],
            'invalid amount format' => [['amount' => '10,100.00']],
        ];
    }

    /**
     * Tampered or malformed confirmations return false.
     *
     * @param array<string, mixed> $overrides Fields to replace
     */
    #[DataProvider('invalidOperations')]
    public function testInvalidConfirmationIsRejected(array $overrides): void
    {
        $this->assertFalse($this->bancard->verifyConfirmation($this->operation($overrides)));
    }

    /**
     * Missing fields return false.
     */
    public function testMissingFieldsAreRejected(): void
    {
        $this->assertFalse($this->bancard->verifyConfirmation([]));
        $this->assertFalse($this->bancard->verifyConfirmation(['token' => md5('x')]));
    }

    /**
     * A token signed with another private key is rejected.
     */
    public function testTokenFromOtherPrivateKeyIsRejected(): void
    {
        $other = $this->makeBancard(privateKey: 'other_private_key');

        $this->assertFalse($other->verifyConfirmation($this->operation()));
    }
}
