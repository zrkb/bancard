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
}
