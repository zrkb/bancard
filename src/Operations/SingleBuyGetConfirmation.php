<?php

namespace Bancard\Operations;

use Bancard\Bancard;
use Bancard\Util\Token;

class SingleBuyGetConfirmation extends Operation
{
    protected string $endpoint = '/vpos/api/0.3/single_buy/confirmations';

    /**
     * Make a new token.
     *
     * @return string
     */
    public function token(): string
    {
        return Token::make(
            Bancard::privateKey(),
            $this->payload('shop_process_id'),
            'get_confirmation',
        );
    }
}
