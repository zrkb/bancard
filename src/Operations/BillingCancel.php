<?php

declare(strict_types=1);

namespace Bancard\Operations;

use Bancard\Response\BillingCancelResponse;
use Bancard\Util\Token;

/**
 * @extends Operation<BillingCancelResponse>
 */
class BillingCancel extends Operation
{
    protected string $endpoint = '/vpos/api/0.3/billing/cancel';

    /** @var class-string<BillingCancelResponse> */
    protected string $responseClass = BillingCancelResponse::class;

    /**
     * Generate the operation token.
     *
     * Token: md5(private_key + shop_process_id + "billing_cancel")
     *
     * @return string MD5 hash
     */
    public function token(): string
    {
        return Token::make(
            $this->client->privateKey,
            (string) $this->payload('shop_process_id'),
            'billing_cancel',
        );
    }

    /**
     * @return list<string>
     */
    protected function rules(): array
    {
        return ['shop_process_id'];
    }
}
