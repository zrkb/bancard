<?php

declare(strict_types=1);

namespace Bancard\Exception;

class CardNotFoundException extends BancardException
{
    /**
     * Create an exception for a card that is not registered (or has no alias token) for the user.
     *
     * @param string $userId User ID the cards were listed for
     * @param string $cardId Card ID that was not found
     */
    public function __construct(
        private readonly string $userId,
        private readonly string $cardId,
    ) {
        parent::__construct("Card \"{$cardId}\" not found for user \"{$userId}\".");
    }

    /**
     * Get the user ID the cards were listed for.
     *
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * Get the card ID that was not found.
     *
     * @return string
     */
    public function getCardId(): string
    {
        return $this->cardId;
    }
}
