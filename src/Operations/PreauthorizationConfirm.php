<?php

namespace Bancard\Operations;

use Bancard\Bancard;
use Bancard\Util\Token;

class PreauthorizationConfirm extends Operation
{
    protected string $endpoint = '/vpos/api/0.3/preauthorizations/confirm';

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
            'pre-authorization-confirm',
        );
    }
}
