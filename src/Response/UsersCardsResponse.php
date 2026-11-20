<?php

declare(strict_types=1);

namespace Bancard\Response;

class UsersCardsResponse extends Response
{
    /**
     * Get the registered cards of the user.
     *
     * @return list<\stdClass>
     */
    public function getCards(): array
    {
        if (!isset($this->data->cards) || !is_array($this->data->cards)) {
            return [];
        }

        return array_values(array_filter($this->data->cards, fn (mixed $card): bool => $card instanceof \stdClass));
    }

    /**
     * Find a registered card by its card_id.
     *
     * IDs are compared as strings, so 1 and "1" match.
     *
     * @param int|string $cardId Card ID given when the card was registered (cards_new)
     * @return \stdClass|null The card, or null when the user has no card with that ID
     */
    public function findCard(int|string $cardId): ?\stdClass
    {
        foreach ($this->getCards() as $card) {
            if (self::stringValue($card->card_id ?? null) === (string) $cardId) {
                return $card;
            }
        }

        return null;
    }
}
