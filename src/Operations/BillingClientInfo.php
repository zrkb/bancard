<?php

declare(strict_types=1);

namespace Bancard\Operations;

use Bancard\Response\BillingClientInfoResponse;
use Bancard\Util\Token;

/**
 * @extends Operation<BillingClientInfoResponse>
 */
class BillingClientInfo extends Operation
{
    protected string $endpoint = '/vpos/api/0.3/billing/client_info';

    /** @var class-string<BillingClientInfoResponse> */
    protected string $responseClass = BillingClientInfoResponse::class;

    public function token(): string
    {
        return Token::make(
            $this->client->privateKey,
            'billing_client_info',
        );
    }
}
