<?php

namespace Bancard\Operations;

use Bancard\Bancard;
use Bancard\Util\Token;

class SingleBuyConfirm extends Operation
{
    /**
     * We are overriding this method because this class is merely used to
     * get the token for the SingleBuyConfirm Operation.
     *
     * @return mixed
     */
    public function execute()
    {
        return false;
    }

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
            'confirm',
            $this->payload('amount'),
            $this->payload('currency'),
        );
    }
}
