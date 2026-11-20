<?php

declare(strict_types=1);

namespace Bancard\Tests;

use Bancard\Bancard;
use Bancard\Callback\ConfirmationCallback;
use Bancard\Exception\BancardException;
use Bancard\Exception\InvalidConfirmationException;
use PHPUnit\Framework\Attributes\DataProvider;

class ConfirmationCallbackTest extends TestCase
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
     * Build a callback body like the spec 1.22 example, signed with the test private key.
     *
     * @param array<string, mixed> $overrides Operation fields to replace
     * @param bool $sign Recompute the token from the final fields
     * @return string JSON body
     */
    private function body(array $overrides = [], bool $sign = true): string
    {
        $operation = $overrides + [
            'shop_process_id' => '12313',
            'response' => 'S',
            'response_details' => 'respuesta S',
            'extended_response_description' => 'respuesta extendida',
            'currency' => 'PYG',
            'amount' => '10100.00',
            'authorization_number' => '123456',
            'ticket_number' => '123456789123456',
            'iva_amount' => '1100.0',
            'iva_ticket_number' => '2117960079',
            'response_code' => '00',
            'response_description' => 'Transacción aprobada.',
            'security_information' => [
                'customer_ip' => '123.123.123.123',
                'card_source' => 'I',
                'card_country' => 'Croacia',
                'version' => '0.3',
                'risk_index' => '0',
            ],
            'billing_response' => [
                'status' => 'success',
                'description' => 'Factura generada correctamente',
                'data' => ['invoice_number' => '001-001-0002563'],
            ],
        ];

        if ($sign) {
            $operation['token'] = md5(
                'test_private_key' . $operation['shop_process_id'] . 'confirm' . $operation['amount'] . $operation['currency']
            );
        }

        return (string) json_encode(['operation' => $operation]);
    }

    /**
     * A signed spec example is parsed and exposes every field.
     */
    public function testParsesSignedConfirmation(): void
    {
        $confirmation = $this->bancard->parseConfirmation($this->body());

        $this->assertTrue($confirmation->isApproved());
        $this->assertSame('00', $confirmation->getResponseCode());
        $this->assertSame('Transacción aprobada.', $confirmation->getResponseDescription());
        $this->assertSame('respuesta extendida', $confirmation->getExtendedResponseDescription());
        $this->assertSame('respuesta S', $confirmation->getResponseDetails());
        $this->assertSame('12313', $confirmation->getShopProcessId());
        $this->assertSame('10100.00', $confirmation->getAmount());
        $this->assertSame('1100.0', $confirmation->getIvaAmount());
        $this->assertSame('PYG', $confirmation->getCurrency());
        $this->assertSame('123456', $confirmation->getAuthorizationNumber());
        $this->assertSame('123456789123456', $confirmation->getTicketNumber());
        $this->assertSame('2117960079', $confirmation->getIvaTicketNumber());
        $this->assertSame('I', $confirmation->getCardSource());
        $this->assertSame('Croacia', $confirmation->getCardCountry());
        $this->assertSame('123.123.123.123', $confirmation->getCustomerIp());
        $this->assertSame(0, $confirmation->getRiskIndex());
        $this->assertSame('0.3', $confirmation->getSecurityInformation()?->version);
        $this->assertSame('success', $confirmation->getBillingResponse()?->status);
        $this->assertSame('001-001-0002563', $confirmation->getInvoiceNumber());
        $this->assertSame('S', $confirmation->raw()->response);
        $this->assertCount(0, $this->requestHistory);
    }

    /**
     * A declined payment is parsed but not approved.
     */
    public function testDeclinedConfirmation(): void
    {
        $confirmation = $this->bancard->parseConfirmation($this->body([
            'response' => 'N',
            'response_code' => '51',
            'response_description' => 'Fondos insuficientes',
            'authorization_number' => null,
        ]));

        $this->assertFalse($confirmation->isApproved());
        $this->assertSame('51', $confirmation->getResponseCode());
        $this->assertNull($confirmation->getAuthorizationNumber());
    }

    /**
     * Numbers are read as strings; missing elements give null.
     */
    public function testNumericAndMissingFields(): void
    {
        $confirmation = $this->bancard->parseConfirmation($this->body([
            'shop_process_id' => 12313,
            'ticket_number' => 123456789123456,
            'security_information' => ['risk_index' => 7],
            'billing_response' => null,
        ]));

        $this->assertSame('12313', $confirmation->getShopProcessId());
        $this->assertSame('123456789123456', $confirmation->getTicketNumber());
        $this->assertSame(7, $confirmation->getRiskIndex());
        $this->assertNull($confirmation->getCardSource());
        $this->assertNull($confirmation->getBillingResponse());
        $this->assertNull($confirmation->getInvoiceNumber());
    }

    /**
     * A non-numeric risk index gives null.
     */
    public function testInvalidRiskIndexIsNull(): void
    {
        $confirmation = $this->bancard->parseConfirmation($this->body(['security_information' => ['risk_index' => 'x']]));

        $this->assertNull($confirmation->getRiskIndex());
    }

    /**
     * A body altered after signing is rejected.
     */
    public function testTamperedConfirmationIsRejected(): void
    {
        $body = str_replace('"amount":"10100.00"', '"amount":"1.00"', $this->body());

        $this->expectException(InvalidConfirmationException::class);
        $this->expectExceptionMessage('Invalid confirmation token.');

        $this->bancard->parseConfirmation($body);
    }

    /**
     * An unsigned body is rejected.
     */
    public function testMissingTokenIsRejected(): void
    {
        $this->expectException(InvalidConfirmationException::class);
        $this->expectExceptionMessage('Invalid confirmation token.');

        $this->bancard->parseConfirmation($this->body(sign: false));
    }

    /**
     * Invalid JSON is rejected and keeps the JSON error as previous.
     */
    public function testInvalidJsonIsRejected(): void
    {
        try {
            $this->bancard->parseConfirmation('{"operation":');
            $this->fail('Expected InvalidConfirmationException was not thrown.');
        } catch (InvalidConfirmationException $e) {
            $this->assertSame('Invalid confirmation body: not valid JSON.', $e->getMessage());
            $this->assertInstanceOf(\JsonException::class, $e->getPrevious());
        }
    }

    /**
     * Bodies without an operation object.
     *
     * @return array<string, array{string}>
     */
    public static function bodiesWithoutOperation(): array
    {
        return [
            'array' => ['[]'],
            'string' => ['"x"'],
            'empty object' => ['{}'],
            'operation array' => ['{"operation":[]}'],
            'operation string' => ['{"operation":"x"}'],
        ];
    }

    /**
     * Bodies without an operation object are rejected.
     *
     * @param string $body Request body
     */
    #[DataProvider('bodiesWithoutOperation')]
    public function testBodyWithoutOperationIsRejected(string $body): void
    {
        $this->expectException(InvalidConfirmationException::class);
        $this->expectExceptionMessage('expected a JSON object with an "operation" object');

        $this->bancard->parseConfirmation($body);
    }

    /**
     * The acknowledgement matches the spec's expected answer.
     */
    public function testAcknowledgementMatchesSpec(): void
    {
        $this->assertSame('{"status":"success"}', json_encode(ConfirmationCallback::ACKNOWLEDGEMENT));
    }

    /**
     * A generic BancardException catch also covers invalid confirmations.
     */
    public function testInvalidConfirmationIsCaughtAsBancardException(): void
    {
        $this->expectException(BancardException::class);

        $this->bancard->parseConfirmation('{}');
    }
}
