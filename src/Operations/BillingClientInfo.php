<?php

namespace Bancard\Operations;

use Bancard\Bancard;
use Bancard\Util\Token;

class BillingClientInfo extends Operation
{
    protected string $endpoint = '/vpos/api/0.3/billing/client_info';

    /**
     * Make a new token.
     *
     * @return string
     */
    public function token(): string
    {
        return Token::make(
            Bancard::privateKey(),
            'billing_client_info',
        );
    }
}
