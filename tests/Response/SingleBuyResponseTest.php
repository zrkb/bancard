<?php

declare(strict_types=1);

namespace Bancard\Tests\Response;

use Bancard\Response\SingleBuyResponse;
use Bancard\Tests\TestCase;

class SingleBuyResponseTest extends TestCase
{
    public function testGetProcessId(): void
    {
        $response = new SingleBuyResponse((object) [
            'status' => 'success',
            'process_id' => 'abc123',
        ]);

        $this->assertSame('abc123', $response->getProcessId());
    }

    public function testGetProcessIdReturnsNullWhenMissing(): void
    {
        $response = new SingleBuyResponse((object) ['status' => 'success']);
        $this->assertNull($response->getProcessId());
    }
}
