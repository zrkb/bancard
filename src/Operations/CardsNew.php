<?php

declare(strict_types=1);

namespace Bancard\Operations;

use Bancard\Response\CardsNewResponse;
use Bancard\Util\Token;

/**
 * @extends Operation<CardsNewResponse>
 */
class CardsNew extends Operation
{
    protected string $endpoint = '/vpos/api/0.3/cards/new';

    /** @var class-string<CardsNewResponse> */
    protected string $responseClass = CardsNewResponse::class;

    public function token(): string
    {
        return Token::make(
            $this->client->privateKey,
            (string) $this->payload('card_id'),
            (string) $this->payload('user_id'),
            'request_new_card',
        );
    }

    /**
     * @return list<string>
     */
    protected function rules(): array
    {
        return ['card_id', 'user_id'];
    }
}
