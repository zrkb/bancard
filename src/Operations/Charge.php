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
}
