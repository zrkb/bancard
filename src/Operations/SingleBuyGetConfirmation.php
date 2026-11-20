<?php

declare(strict_types=1);

namespace Bancard\Operations;

use Bancard\Response\ConfirmationResponse;
use Bancard\Util\Token;

/**
 * @extends Operation<ConfirmationResponse>
 */
class SingleBuyGetConfirmation extends Operation
{
    protected string $endpoint = '/vpos/api/0.3/single_buy/confirmations';

    /** @var class-string<ConfirmationResponse> */
    protected string $responseClass = ConfirmationResponse::class;

    /**
     * Generate the operation token.
     *
     * Token: md5(private_key + shop_process_id + "get_confirmation")
     *
     * @return string MD5 hash
     */
    public function token(): string
    {
        return Token::make(
            $this->client->privateKey,
            (string) $this->payload('shop_process_id'),
            'get_confirmation',
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
