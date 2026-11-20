<?php

declare(strict_types=1);

namespace Bancard\Operations;

use Bancard\Response\RollbackResponse;
use Bancard\Util\Token;

/**
 * @extends Operation<RollbackResponse>
 */
class SingleBuyRollback extends Operation
{
    protected string $endpoint = '/vpos/api/0.3/single_buy/rollback';

    /** @var class-string<RollbackResponse> */
    protected string $responseClass = RollbackResponse::class;

    /**
     * Generate the operation token.
     *
     * Token: md5(private_key + shop_process_id + "rollback" + "0.00")
     *
     * @return string MD5 hash
     */
    public function token(): string
    {
        return Token::make(
            $this->client->privateKey,
            (string) $this->payload('shop_process_id'),
            'rollback',
            '0.00',
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
