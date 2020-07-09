<?php

namespace Bancard\Operations;

use Bancard\Bancard;
use Bancard\Util\Token;

class SingleBuyRollback extends Operation
{
    /**
     * @var string
     */
    protected $endpoint = '/vpos/api/0.3/single_buy/rollback';

    /**
     * Make a new token.
     *
     * @return string
     */
    public function token()
    {
        return Token::make(
            Bancard::privateKey(),
            $this->payload('shop_process_id'),
            'rollback',
            '0.00'
        );
    }
}
