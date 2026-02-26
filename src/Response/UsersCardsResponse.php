<?php

declare(strict_types=1);

namespace Bancard\Response;

class UsersCardsResponse extends Response
{
    /**
     * @return list<\stdClass>
     */
    public function getCards(): array
    {
        if (!isset($this->data->cards) || !is_array($this->data->cards)) {
            return [];
        }

        return array_values($this->data->cards);
    }
}
