<?php

declare(strict_types=1);

namespace Bancard\Tests\Response;

use Bancard\Response\Response;
use Bancard\Tests\TestCase;

class ResponseTest extends TestCase
{
    public function testIsSuccessful(): void
    {
        $response = new Response((object) ['status' => 'success']);
        $this->assertTrue($response->isSuccessful());
    }

    public function testIsNotSuccessful(): void
    {
        $response = new Response((object) ['status' => 'error']);
        $this->assertFalse($response->isSuccessful());
    }

    public function testGetStatus(): void
    {
        $response = new Response((object) ['status' => 'success']);
        $this->assertSame('success', $response->getStatus());
    }

    public function testGetStatusReturnsNullWhenMissing(): void
    {
        $response = new Response((object) []);
        $this->assertNull($response->getStatus());
    }

    public function testGetMessageFromObjectMessages(): void
    {
        $response = new Response((object) [
            'messages' => [
                (object) ['key' => 'SomeError', 'level' => 'error', 'dsc' => 'Something went wrong.'],
            ],
        ]);

        $this->assertSame('Something went wrong.', $response->getMessage());
    }

    public function testGetMessageFromStringMessages(): void
    {
        $response = new Response((object) [
            'messages' => ['A simple string message'],
        ]);

        $this->assertSame('A simple string message', $response->getMessage());
    }

    public function testGetMessageReturnsNullWhenEmpty(): void
    {
        $response = new Response((object) ['messages' => []]);
        $this->assertNull($response->getMessage());
    }

    public function testGetMessageReturnsNullWhenMissing(): void
    {
        $response = new Response((object) []);
        $this->assertNull($response->getMessage());
    }

    public function testGetErrorKey(): void
    {
        $response = new Response((object) [
            'messages' => [
                (object) ['key' => 'InvalidOperationError', 'dsc' => 'desc'],
            ],
        ]);

        $this->assertSame('InvalidOperationError', $response->getErrorKey());
    }

    public function testGetErrorKeyReturnsNullWhenMissing(): void
    {
        $response = new Response((object) []);
        $this->assertNull($response->getErrorKey());
    }

    public function testRaw(): void
    {
        $data = (object) ['status' => 'success', 'extra' => 'data'];
        $response = new Response($data);
        $this->assertSame($data, $response->raw());
    }
}
