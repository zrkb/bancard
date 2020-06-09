<?php

namespace Bancard\Operations;

use Bancard\Bancard;
use Bancard\Util\Token;

class SingleBuy extends Operation
{
    protected $endpoint = '/vpos/api/0.3/single_buy';

    protected function token()
    {
        return Token::make(
            Bancard::getPrivateKey(),
            $this->payload('shop_process_id'),
            $this->payload('amount'),
            $this->payload('currency'),
        );
    }
}
