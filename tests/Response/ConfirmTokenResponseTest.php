<?php

declare(strict_types=1);

namespace Bancard\Tests\Response;

use Bancard\Response\ConfirmTokenResponse;
use Bancard\Tests\TestCase;

class ConfirmTokenResponseTest extends TestCase
{
    public function testGetToken(): void
    {
        $response = new ConfirmTokenResponse('abc123token');
        $this->assertSame('abc123token', $response->getToken());
    }

    public function testIsSuccessful(): void
    {
        $response = new ConfirmTokenResponse('token');
        $this->assertTrue($response->isSuccessful());
    }

    public function testRawContainsToken(): void
    {
        $response = new ConfirmTokenResponse('my_token');
        $this->assertSame('my_token', $response->raw()->token);
    }
}
