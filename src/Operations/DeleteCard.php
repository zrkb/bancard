<?php

namespace Bancard\Operations;

use Bancard\Bancard;
use Bancard\Util\Token;

class DeleteCard extends Operation
{
    protected string $endpoint = '/vpos/api/0.3/users/{user_id}/cards';

    protected string $method = 'DELETE';

    /**
     * Make a new token.
     *
     * @return string
     */
    public function token(): string
    {
        return Token::make(
            Bancard::privateKey(),
            'delete_card',
            $this->payload('user_id'),
            $this->payload('alias_token'),
        );
    }
}
