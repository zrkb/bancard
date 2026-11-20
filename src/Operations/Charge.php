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
     * Get required fields.
     *
     * description is required by Bancard (staging answers "The parameter
     * description is missing.") although the spec's field table omits it.
     *
     * @return list<string>
     */
    protected function rules(): array
    {
        return ['shop_process_id', 'amount', 'currency', 'alias_token', 'description'];
    }

    /**
     * Get default values for fields missing from the payload.
     *
     * - number_of_payments: the spec requires 1 for debit cards.
     * - additional_data: Bancard rejects charges without it, though the spec marks it optional.
     * - extra_response_attributes: the spec says to always send confirmation.process_id,
     *   which carries the 3DS process ID when authentication is needed.
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'number_of_payments' => 1,
            'additional_data' => '',
            'extra_response_attributes' => ['confirmation.process_id'],
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
            'description' => 20,
            'alias_token' => 255,
            'additional_data' => 255,
            'return_url' => 255,
            'preauthorization' => 1,
        ];
    }
}
