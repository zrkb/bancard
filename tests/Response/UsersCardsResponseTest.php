<?php

declare(strict_types=1);

namespace Bancard\Tests\Response;

use Bancard\Response\UsersCardsResponse;
use Bancard\Tests\TestCase;

class UsersCardsResponseTest extends TestCase
{
    public function testGetCards(): void
    {
        $response = new UsersCardsResponse((object) [
            'status' => 'success',
            'cards' => [
                (object) ['alias_token' => 'token_abc', 'card_brand' => 'Visa'],
                (object) ['alias_token' => 'token_def', 'card_brand' => 'Mastercard'],
            ],
        ]);

        $cards = $response->getCards();
        $this->assertCount(2, $cards);
        $this->assertSame('token_abc', $cards[0]->alias_token);
        $this->assertSame('token_def', $cards[1]->alias_token);
    }

    public function testGetCardsReturnsEmptyWhenNoCards(): void
    {
        $response = new UsersCardsResponse((object) ['status' => 'success']);
        $this->assertSame([], $response->getCards());
    }

    public function testGetCardsReturnsEmptyArray(): void
    {
        $response = new UsersCardsResponse((object) [
            'status' => 'success',
            'cards' => [],
        ]);

        $this->assertSame([], $response->getCards());
    }

    /**
     * findCard() matches card_id as a string or integer.
     */
    public function testFindCardMatchesStringAndIntegerIds(): void
    {
        $response = new UsersCardsResponse((object) [
            'status' => 'success',
            'cards' => [
                (object) ['card_id' => 1, 'alias_token' => 'token_abc'],
                (object) ['card_id' => '2', 'alias_token' => 'token_def'],
            ],
        ]);

        $this->assertSame('token_abc', $response->findCard(1)?->alias_token);
        $this->assertSame('token_abc', $response->findCard('1')?->alias_token);
        $this->assertSame('token_def', $response->findCard(2)?->alias_token);
        $this->assertNull($response->findCard(3));
    }

    /**
     * findCard() returns null when cards have no card_id or the list is missing.
     */
    public function testFindCardReturnsNullWithoutMatch(): void
    {
        $withoutIds = new UsersCardsResponse((object) [
            'status' => 'success',
            'cards' => [(object) ['alias_token' => 'token_abc']],
        ]);

        $this->assertNull($withoutIds->findCard(1));
        $this->assertNull((new UsersCardsResponse((object) ['status' => 'success']))->findCard(1));
    }

    /**
     * Non-object entries in the cards list are skipped.
     */
    public function testGetCardsSkipsNonObjectEntries(): void
    {
        $response = new UsersCardsResponse((object) [
            'status' => 'success',
            'cards' => ['oops', (object) ['card_id' => 1], null],
        ]);

        $this->assertCount(1, $response->getCards());
    }
}
