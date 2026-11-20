<?php

declare(strict_types=1);

namespace Bancard\Tests\Exception;

use Bancard\Exception\ApiException;
use Bancard\Response\Response;
use PHPUnit\Framework\TestCase;

class ApiExceptionTest extends TestCase
{
    /**
     * ApiException: message from response.
     */
    public function testMessageFromResponse(): void
    {
        $response = new Response((object) [
            'status' => 'error',
            'messages' => [(object) ['key' => 'SomeError', 'level' => 'error', 'dsc' => 'Something failed.']],
        ]);

        $exception = new ApiException($response);

        $this->assertSame('Something failed.', $exception->getMessage());
    }

    /**
     * ApiException: fallback message.
     */
    public function testFallbackMessage(): void
    {
        $response = new Response((object) ['status' => 'error']);
        $exception = new ApiException($response);

        $this->assertSame('API request failed', $exception->getMessage());
    }

    /**
     * ApiException: get response.
     */
    public function testGetResponse(): void
    {
        $response = new Response((object) ['status' => 'error']);
        $exception = new ApiException($response);

        $this->assertSame($response, $exception->getResponse());
    }

    /**
     * ApiException: get error key.
     */
    public function testGetErrorKey(): void
    {
        $response = new Response((object) [
            'status' => 'error',
            'messages' => [(object) ['key' => 'InvalidOperationError', 'level' => 'error', 'dsc' => 'Invalid.']],
        ]);

        $exception = new ApiException($response);

        $this->assertSame('InvalidOperationError', $exception->getErrorKey());
    }

    /**
     * ApiException: get status.
     */
    public function testGetStatus(): void
    {
        $response = new Response((object) ['status' => 'error']);
        $exception = new ApiException($response);

        $this->assertSame('error', $exception->getStatus());
    }

    /**
     * ApiException: null delegates.
     */
    public function testNullDelegates(): void
    {
        $response = new Response((object) []);
        $exception = new ApiException($response);

        $this->assertNull($exception->getErrorKey());
        $this->assertNull($exception->getStatus());
    }
}
