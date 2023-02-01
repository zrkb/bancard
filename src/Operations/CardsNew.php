<?php

namespace Bancard\Operations;

use Bancard\Bancard;
use Bancard\Util\Token;

class CardsNew extends Operation
{
    protected string $endpoint = '/vpos/api/0.3/cards/new';

    /**
     * Make a new token.
     *
     * @return string
     */
    public function token(): string
    {
        return Token::make(
            Bancard::privateKey(),
            $this->payload('card_id'),
            $this->payload('user_id'),
            'request_new_card',
        );
    }
}
