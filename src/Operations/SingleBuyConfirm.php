<?php

declare(strict_types=1);

namespace Bancard\Operations;

use Bancard\Response\ConfirmTokenResponse;
use Bancard\Response\Response;
use Bancard\Util\Token;

/**
 * @extends Operation<ConfirmTokenResponse>
 */
class SingleBuyConfirm extends Operation
{
    /** @var class-string<ConfirmTokenResponse> */
    protected string $responseClass = ConfirmTokenResponse::class;

    /**
     * @return ConfirmTokenResponse
     */
    public function execute(): Response
    {
        $this->validate();

        return new ConfirmTokenResponse($this->token());
    }

    /**
     * Generate the operation token.
     *
     * Token: md5(private_key + shop_process_id + "confirm" + amount + currency)
     *
     * @return string MD5 hash
     */
    public function token(): string
    {
        return Token::make(
            $this->client->privateKey,
            (string) $this->payload('shop_process_id'),
            'confirm',
            (string) $this->payload('amount'),
            (string) $this->payload('currency'),
        );
    }

    /**
     * @return list<string>
     */
    protected function rules(): array
    {
        return ['shop_process_id', 'amount', 'currency'];
    }
}
