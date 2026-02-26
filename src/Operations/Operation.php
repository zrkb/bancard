<?php

declare(strict_types=1);

namespace Bancard\Operations;

use Bancard\Bancard;
use Bancard\Exception\ValidationException;
use Bancard\Response\Response;
use InvalidArgumentException;

/**
 * @template TResponse of Response
 */
abstract class Operation
{
    protected string $endpoint;

    protected string $method = 'POST';

    /** @var class-string<TResponse> */
    protected string $responseClass = Response::class;

    /**
     * @param array<string, mixed> $payload
     */
    final public function __construct(
        protected readonly Bancard $client,
        protected readonly array $payload,
    ) {
    }

    /**
     * @return TResponse
     */
    public function execute(): Response
    {
        $this->validate();

        /** @var string $endpoint */
        $endpoint = preg_replace_callback(
            '/{(\w+)}/',
            fn (array $m): string => (string) $this->payload($m[1]),
            $this->endpoint
        );

        $raw = $this->client->request($this->method, $endpoint, $this->data());

        return new ($this->responseClass)($raw);
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $operationData = array_filter(
            $this->payload + ['token' => $this->token()],
            fn (mixed $v): bool => $v !== null
        );

        return [
            'public_key' => $this->client->publicKey,
            'operation' => $operationData,
        ];
    }

    public function payload(string $key): mixed
    {
        if (!isset($this->payload[$key])) {
            throw new InvalidArgumentException("Invalid key \"{$key}\" in payload.");
        }

        return $this->payload[$key];
    }

    abstract public function token(): string;

    /**
     * @return list<string>
     */
    protected function rules(): array
    {
        return [];
    }

    protected function validate(): void
    {
        $missing = [];

        foreach ($this->rules() as $field) {
            if (!array_key_exists($field, $this->payload)) {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            throw new ValidationException($missing);
        }
    }
}
