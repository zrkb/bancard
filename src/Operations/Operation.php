<?php

declare(strict_types=1);

namespace Bancard\Operations;

use Bancard\Bancard;
use Bancard\Exception\ApiException;
use Bancard\Exception\ValidationException;
use Bancard\Response\Response;
use Bancard\Util\Amount;
use InvalidArgumentException;

/**
 * @template TResponse of Response
 */
abstract class Operation
{
    /**
     * Payload fields holding amounts that must use Bancard's "0.00" format.
     */
    private const AMOUNT_FIELDS = ['amount', 'iva_amount'];

    protected string $endpoint;

    protected string $method = 'POST';

    /** @var class-string<TResponse> */
    protected string $responseClass = Response::class;

    /** @var array<string, mixed> */
    protected readonly array $payload;

    /**
     * Create the operation and normalize its payload.
     *
     * Backed enums (e.g. Currency::PYG) are converted to their value and valid
     * amounts are formatted with two decimals. Invalid amounts are kept as-is
     * so validate() can report them.
     *
     * @param Bancard $client Configured Bancard client
     * @param array<string, mixed> $payload Operation fields
     */
    final public function __construct(
        protected readonly Bancard $client,
        array $payload,
    ) {
        $this->payload = self::normalizePayload($payload);
    }

    /**
     * Validate the payload, send the request and wrap the result.
     *
     * @return TResponse
     *
     * @throws ValidationException When required fields are missing or amounts are invalid
     * @throws ApiException When Bancard responds with a non-success status
     * @throws \Bancard\Exception\BancardException When the response body is not a JSON object
     */
    public function execute(): Response
    {
        $this->validate();

        $raw = $this->client->request($this->method, $this->resolveEndpoint(), $this->data());

        $response = new ($this->responseClass)($raw);

        if (!$response->isSuccessful()) {
            throw new ApiException($response);
        }

        return $response;
    }

    /**
     * Build the request body sent to Bancard.
     *
     * Null values are removed; a "token" key in the payload overrides the generated token.
     *
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

    /**
     * Get a payload value by key.
     *
     * @param string $key Payload field name
     * @return mixed The (normalized) payload value
     *
     * @throws InvalidArgumentException When the key is missing or null
     */
    public function payload(string $key): mixed
    {
        if (!isset($this->payload[$key])) {
            throw new InvalidArgumentException("Invalid key \"{$key}\" in payload.");
        }

        return $this->payload[$key];
    }

    /**
     * Generate the MD5 token for this operation.
     *
     * @return string MD5 hash
     */
    abstract public function token(): string;

    /**
     * Get the required payload fields.
     *
     * @return list<string>
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * Ensure required fields are present and amount fields are well formed.
     *
     * Null counts as missing, since data() drops null values from the request.
     *
     * @throws ValidationException When a required field is missing or an amount is invalid
     */
    protected function validate(): void
    {
        $missing = [];

        foreach ($this->rules() as $field) {
            if (!isset($this->payload[$field])) {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            throw new ValidationException($missing);
        }

        $invalid = [];

        foreach (self::AMOUNT_FIELDS as $field) {
            if (isset($this->payload[$field]) && Amount::normalize($this->payload[$field]) === null) {
                $invalid[] = $field;
            }
        }

        if ($invalid !== []) {
            throw new ValidationException(
                $invalid,
                'Invalid amount for fields: ' . implode(', ', $invalid)
                    . '. Expected a non-negative number with at most two decimals, e.g. "10000.00".'
            );
        }
    }

    /**
     * Replace {placeholders} in the endpoint with URL-encoded payload values.
     *
     * Dots are encoded too, so values like ".." cannot change the request path.
     *
     * @return string Endpoint path with placeholders resolved
     *
     * @throws InvalidArgumentException When a placeholder has no payload value
     */
    protected function resolveEndpoint(): string
    {
        return (string) preg_replace_callback(
            '/{(\w+)}/',
            fn (array $m): string => strtr(rawurlencode((string) $this->payload($m[1])), ['.' => '%2E']),
            $this->endpoint
        );
    }

    /**
     * Convert backed enums to their values and format valid amounts.
     *
     * @param array<string, mixed> $payload Raw payload
     * @return array<string, mixed> Normalized payload
     */
    private static function normalizePayload(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if ($value instanceof \BackedEnum) {
                $payload[$key] = $value->value;
            }
        }

        foreach (self::AMOUNT_FIELDS as $field) {
            if (isset($payload[$field])) {
                $payload[$field] = Amount::normalize($payload[$field]) ?? $payload[$field];
            }
        }

        return $payload;
    }
}
