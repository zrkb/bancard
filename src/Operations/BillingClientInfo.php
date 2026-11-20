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

    /**
     * Generate the operation token.
     *
     * Token: md5(private_key + "billing_client_info")
     *
     * @return string MD5 hash
     */
    public function token(): string
    {
        return Token::make(
            $this->client->privateKey,
            'billing_client_info',
        );
    }

    /**
     * Get string fields and their maximum length, per the vPOS spec.
     *
     * @return array<string, int>
     */
    protected function maxLengths(): array
    {
        return [
            'client_ruc' => 32,
        ];
    }

    /**
     * Get the required payload fields.
     *
     * @return list<string>
     */
    protected function rules(): array
    {
        return ['client_ruc'];
    }
}
