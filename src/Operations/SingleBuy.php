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
}
