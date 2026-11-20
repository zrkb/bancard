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

    /**
     * Generate the operation token.
     *
     * Token: md5(private_key + card_id + user_id + "request_new_card")
     *
     * @return string MD5 hash
     */
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
     * Get the required payload fields (all four are mandatory per the vPOS spec).
     *
     * @return list<string>
     */
    protected function rules(): array
    {
        return ['card_id', 'user_id', 'user_cell_phone', 'user_mail'];
    }

    /**
     * Get integer fields and their maximum digits, per the vPOS spec.
     *
     * @return array<string, int>
     */
    protected function integerFields(): array
    {
        return [
            'card_id' => 19,
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
            'user_cell_phone' => 255,
            'user_mail' => 255,
            'return_url' => 255,
        ];
    }
}
