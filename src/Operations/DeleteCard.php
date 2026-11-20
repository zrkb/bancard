<?php

declare(strict_types=1);

namespace Bancard\Operations;

use Bancard\Response\DeleteCardResponse;
use Bancard\Util\Token;

/**
 * @extends Operation<DeleteCardResponse>
 */
class DeleteCard extends Operation
{
    protected string $endpoint = '/vpos/api/0.3/users/{user_id}/cards';

    protected string $method = 'DELETE';

    /** @var class-string<DeleteCardResponse> */
    protected string $responseClass = DeleteCardResponse::class;

    /**
     * Generate the operation token.
     *
     * Token: md5(private_key + "delete_card" + user_id + alias_token)
     *
     * @return string MD5 hash
     */
    public function token(): string
    {
        return Token::make(
            $this->client->privateKey,
            'delete_card',
            (string) $this->payload('user_id'),
            (string) $this->payload('alias_token'),
        );
    }

    /**
     * @return list<string>
     */
    protected function rules(): array
    {
        return ['user_id', 'alias_token'];
    }

    /**
     * Get integer fields and their maximum digits, per the vPOS spec.
     *
     * @return array<string, int>
     */
    protected function integerFields(): array
    {
        return [
            'user_id' => 19,
        ];
    }

    /**
     * Get string fields and their maximum length, per the vPOS spec.
     *
     * @return array<string, int>
     */
    protected function maxLengths(): array
    {
        return [
            'alias_token' => 255,
        ];
    }
}
