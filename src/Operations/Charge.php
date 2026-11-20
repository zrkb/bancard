<?php

declare(strict_types=1);

namespace Bancard\Operations;

use Bancard\Response\ChargeResponse;
use Bancard\Util\Token;

/**
 * @extends Operation<ChargeResponse>
 */
class Charge extends Operation
{
    protected string $endpoint = '/vpos/api/0.3/charge';

    /** @var class-string<ChargeResponse> */
    protected string $responseClass = ChargeResponse::class;

    /**
     * Generate the operation token.
     *
     * Token: md5(private_key + shop_process_id + "charge" + amount + currency + alias_token)
     *
     * @return string MD5 hash
     */
    public function token(): string
    {
        return Token::make(
            $this->client->privateKey,
            (string) $this->payload('shop_process_id'),
            'charge',
            (string) $this->payload('amount'),
            (string) $this->payload('currency'),
            (string) $this->payload('alias_token'),
        );
    }

    /**
     * @return list<string>
     */
    protected function rules(): array
    {
        return ['shop_process_id', 'amount', 'currency', 'alias_token'];
    }

    /**
     * Get default values for fields missing from the payload.
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'number_of_payments' => 1,
        ];
    }

    /**
     * Get integer fields and their maximum digits, per the vPOS spec.
     *
     * @return array<string, int>
     */
    protected function integerFields(): array
    {
        return [
            'shop_process_id' => 15,
        ];
    }

    /**
     * Get string fields and their maximum length, per the vPOS spec.
     *
     * @return array<string, int>
     */
    protected function maxLengths(): array
    {
        return [
            'currency' => 3,
            'alias_token' => 255,
            'additional_data' => 255,
            'return_url' => 255,
            'preauthorization' => 1,
        ];
    }
}
