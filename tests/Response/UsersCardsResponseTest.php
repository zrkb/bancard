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
}
