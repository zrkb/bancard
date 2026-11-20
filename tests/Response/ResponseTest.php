<?php

declare(strict_types=1);

namespace Bancard\Tests\Response;

use Bancard\Response\Response;
use Bancard\Tests\TestCase;

class ResponseTest extends TestCase
{
    /**
     * Response: is successful.
     */
    public function testIsSuccessful(): void
    {
        $response = new Response((object) ['status' => 'success']);
        $this->assertTrue($response->isSuccessful());
    }

    /**
     * Response: is not successful.
     */
    public function testIsNotSuccessful(): void
    {
        $response = new Response((object) ['status' => 'error']);
        $this->assertFalse($response->isSuccessful());
    }

    /**
     * Response: get status.
     */
    public function testGetStatus(): void
    {
        $response = new Response((object) ['status' => 'success']);
        $this->assertSame('success', $response->getStatus());
    }

    /**
     * Get status returns null when missing.
     */
    public function testGetStatusReturnsNullWhenMissing(): void
    {
        $response = new Response((object) []);
        $this->assertNull($response->getStatus());
    }

    /**
     * Get message from object messages.
     */
    public function testGetMessageFromObjectMessages(): void
    {
        $response = new Response((object) [
            'messages' => [
                (object) ['key' => 'SomeError', 'level' => 'error', 'dsc' => 'Something went wrong.'],
            ],
        ]);

        $this->assertSame('Something went wrong.', $response->getMessage());
    }

    /**
     * Get message from string messages.
     */
    public function testGetMessageFromStringMessages(): void
    {
        $response = new Response((object) [
            'messages' => ['A simple string message'],
        ]);

        $this->assertSame('A simple string message', $response->getMessage());
    }

    /**
     * Get message returns null when empty.
     */
    public function testGetMessageReturnsNullWhenEmpty(): void
    {
        $response = new Response((object) ['messages' => []]);
        $this->assertNull($response->getMessage());
    }

    /**
     * Get message returns null when missing.
     */
    public function testGetMessageReturnsNullWhenMissing(): void
    {
        $response = new Response((object) []);
        $this->assertNull($response->getMessage());
    }

    /**
     * Response: get error key.
     */
    public function testGetErrorKey(): void
    {
        $response = new Response((object) [
            'messages' => [
                (object) ['key' => 'InvalidOperationError', 'dsc' => 'desc'],
            ],
        ]);

        $this->assertSame('InvalidOperationError', $response->getErrorKey());
    }

    /**
     * Get error key returns null when missing.
     */
    public function testGetErrorKeyReturnsNullWhenMissing(): void
    {
        $response = new Response((object) []);
        $this->assertNull($response->getErrorKey());
    }

    /**
     * Messages without a usable description or key return null.
     */
    public function testMessagesWithoutDescriptionOrKeyReturnNull(): void
    {
        $withoutDsc = new Response((object) ['messages' => [(object) ['level' => 'error']]]);
        $this->assertNull($withoutDsc->getMessage());
        $this->assertNull($withoutDsc->getErrorKey());

        $numeric = new Response((object) ['messages' => [123]]);
        $this->assertNull($numeric->getMessage());
        $this->assertNull($numeric->getErrorKey());
    }

    /**
     * Response: raw.
     */
    public function testRaw(): void
    {
        $data = (object) ['status' => 'success', 'extra' => 'data'];
        $response = new Response($data);
        $this->assertSame($data, $response->raw());
    }
}
