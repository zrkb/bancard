<?php

declare(strict_types=1);

namespace Bancard\Tests;

use Bancard\Bancard;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionProperty;

class TestCase extends BaseTestCase
{
    protected MockHandler $mockHandler;

    /** @var array<int, array<string, mixed>> */
    protected array $requestHistory = [];

    protected function makeBancard(
        string $publicKey = 'test_public_key',
        string $privateKey = 'test_private_key',
        bool $staging = false,
    ): Bancard {
        $bancard = new Bancard(
            publicKey: $publicKey,
            privateKey: $privateKey,
            staging: $staging,
        );

        $this->mockHandler = new MockHandler();
        $this->requestHistory = [];

        $stack = HandlerStack::create($this->mockHandler);
        $stack->push(Middleware::history($this->requestHistory)); // @phpstan-ignore assign.propertyType

        $guzzle = new GuzzleClient([
            'handler' => $stack,
            'base_uri' => $bancard->baseUri(),
        ]);

        $bancard->setHttp($guzzle);

        return $bancard;
    }

    /**
     * @param array<string, mixed> $body
     */
    protected function mockResponse(int $status = 200, array $body = []): void
    {
        $this->mockHandler->append(
            new Response($status, [], (string) json_encode($body))
        );
    }

    protected function assertRequestSent(string $method, string $path): void
    {
        $this->assertNotEmpty($this->requestHistory, 'No requests were sent.');

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $this->requestHistory[0]['request'];

        $this->assertSame($method, $request->getMethod());
        $this->assertSame($path, $request->getUri()->getPath());
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRequestBody(): array
    {
        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $this->requestHistory[0]['request'];

        /** @var array<string, mixed> */
        return json_decode((string) $request->getBody(), true);
    }

    protected function fixture(string $name): string
    {
        return (string) file_get_contents(__DIR__ . '/fixtures/' . $name . '.json');
    }

    /**
     * @return array<string, mixed>
     */
    protected function fixtureArray(string $name): array
    {
        /** @var array<string, mixed> */
        return json_decode($this->fixture($name), true);
    }

    protected function getProtectedProperty(object $object, string $property): mixed
    {
        $reflection = new ReflectionProperty($object, $property);

        return $reflection->getValue($object);
    }
}
