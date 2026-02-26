<?php

declare(strict_types=1);

namespace Bancard\Tests\Response;

use Bancard\Response\BillingClientInfoResponse;
use Bancard\Tests\TestCase;

class BillingClientInfoResponseTest extends TestCase
{
    public function testGetClient(): void
    {
        $client = (object) ['name' => 'John Doe', 'document_number' => '123'];
        $response = new BillingClientInfoResponse((object) [
            'status' => 'success',
            'client' => $client,
        ]);

        $this->assertSame($client, $response->getClient());
    }

    public function testGetClientReturnsNull(): void
    {
        $response = new BillingClientInfoResponse((object) ['status' => 'success']);
        $this->assertNull($response->getClient());
    }
}
