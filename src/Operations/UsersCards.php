<?php

declare(strict_types=1);

namespace Bancard\Operations;

use Bancard\Response\UsersCardsResponse;
use Bancard\Util\Token;

/**
 * @extends Operation<UsersCardsResponse>
 */
class UsersCards extends Operation
{
    protected string $endpoint = '/vpos/api/0.3/users/{user_id}/cards';

    /** @var class-string<UsersCardsResponse> */
    protected string $responseClass = UsersCardsResponse::class;

    public function token(): string
    {
        return Token::make(
            $this->client->privateKey,
            (string) $this->payload('user_id'),
            'request_user_cards',
        );
    }

    /**
     * @return list<string>
     */
    protected function rules(): array
    {
        return ['user_id'];
    }
}
