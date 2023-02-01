<?php

namespace Bancard\Operations;

use Bancard\Bancard;
use Bancard\Util\Token;

class UsersCards extends Operation
{
    protected string $endpoint = '/vpos/api/0.3/users/{user_id}/cards';

    /**
     * Make a new token.
     *
     * @return string
     */
    public function token(): string
    {
        return Token::make(
            Bancard::privateKey(),
            $this->payload('user_id'),
            'request_user_cards',
        );
    }
}
