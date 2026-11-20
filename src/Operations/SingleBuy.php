<?php

declare(strict_types=1);

namespace Bancard\Operations;

use Bancard\Response\SingleBuyResponse;
use Bancard\Util\Token;

/**
 * @extends Operation<SingleBuyResponse>
 */
class SingleBuy extends Operation
{
    protected string $endpoint = '/vpos/api/0.3/single_buy';

    /** @var class-string<SingleBuyResponse> */
    protected string $responseClass = SingleBuyResponse::class;

    /**
     * Generate the operation token.
     *
     * Token: md5(private_key + shop_process_id + amount + currency)
     *
     * @return string MD5 hash
     */
    public function token(): string
    {
        return Token::make(
            $this->client->privateKey,
            (string) $this->payload('shop_process_id'),
            (string) $this->payload('amount'),
            (string) $this->payload('currency'),
        );
    }

    /**
     * @return list<string>
     */
    protected function rules(): array
    {
        return ['shop_process_id', 'amount', 'currency'];
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
            'additional_data' => 255,
            'return_url' => 255,
            'cancel_url' => 255,
            'preauthorization' => 1,
        ];
    }
}
