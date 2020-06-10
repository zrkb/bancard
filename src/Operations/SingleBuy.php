<?php

namespace Bancard\Operations;

use Bancard\Bancard;
use Bancard\Util\Token;

class SingleBuy extends Operation
{
    /**
     * @var string
     */
    protected $endpoint = '/vpos/api/0.3/single_buy';

    /**
     * Make a new token.
     *
     * @return string
     */
    protected function token()
    {
        return Token::make(
            Bancard::privateKey(),
            $this->payload('shop_process_id'),
            $this->payload('amount'),
            $this->payload('currency'),
        );
    }
}
