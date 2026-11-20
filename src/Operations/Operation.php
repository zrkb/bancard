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

    /**
     * Maximum integer digits of an amount: the spec's Decimal (15,2) leaves 13.
     */
    private const AMOUNT_MAX_INTEGER_DIGITS = 13;

    protected string $endpoint;

    protected string $method = 'POST';

    /** @var class-string<TResponse> */
    protected string $responseClass = Response::class;

    /** @var array<string, mixed> */
    protected readonly array $payload;

    /**
     * Create the operation and normalize its payload.
     *
     * Missing fields get their defaults(), backed enums (e.g. Currency::PYG) are
     * converted to their value and valid amounts are formatted with two
     * decimals. Invalid amounts are kept as-is so validate() can report them.
     *
     * @param Bancard $client Configured Bancard client
     * @param array<string, mixed> $payload Operation fields
     */
    final public function __construct(
        protected readonly Bancard $client,
        array $payload,
    ) {
        $this->payload = self::normalizePayload($payload + $this->defaults());
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
     * Get default values for fields missing from the payload.
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [];
    }

    /**
     * Get integer fields and their maximum number of digits, per the vPOS spec ("Entero (n)").
     *
     * @return array<string, int>
     */
    protected function integerFields(): array
    {
        return [];
    }

    /**
     * Get string fields and their maximum length in characters, per the vPOS spec ("String (n)").
     *
     * @return array<string, int>
     */
    protected function maxLengths(): array
    {
        return [];
    }

    /**
     * Ensure required fields are present and fields match the spec's types and limits.
     *
     * Null counts as missing, since data() drops null values from the request,
     * and so does an empty string, which Bancard also reports as missing.
     *
     * @throws ValidationException When a required field is missing or a field is invalid
     */
    protected function validate(): void
    {
        $missing = [];

        foreach ($this->rules() as $field) {
            if (!isset($this->payload[$field]) || $this->payload[$field] === '') {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            throw new ValidationException($missing);
        }

        $invalid = [];

        foreach (self::AMOUNT_FIELDS as $field) {
            if (isset($this->payload[$field]) && !self::isValidAmount($this->payload[$field])) {
                $invalid[] = $field;
            }
        }

        if ($invalid !== []) {
            throw new ValidationException(
                $invalid,
                'Invalid amount for fields: ' . implode(', ', $invalid)
                    . '. Expected a non-negative number with at most two decimals and '
                    . self::AMOUNT_MAX_INTEGER_DIGITS . ' integer digits, e.g. "10000.00".'
            );
        }

        $errors = [];

        foreach ($this->integerFields() as $field => $digits) {
            if (isset($this->payload[$field]) && !self::isValidInteger($this->payload[$field], $digits)) {
                $errors[$field] = "integer with at most {$digits} digits";
            }
        }

        foreach ($this->maxLengths() as $field => $length) {
            if (isset($this->payload[$field]) && !self::isValidString($this->payload[$field], $length)) {
                $errors[$field] ??= "string with at most {$length} characters";
            }
        }

        if ($errors !== []) {
            $details = array_map(
                fn (string $field, string $rule): string => "{$field} ({$rule})",
                array_keys($errors),
                $errors
            );

            throw new ValidationException(array_keys($errors), 'Invalid fields: ' . implode(', ', $details) . '.');
        }
    }

    /**
     * Check that a value is an amount within the spec's Decimal (15,2).
     *
     * @param mixed $value Payload value
     * @return bool
     */
    private static function isValidAmount(mixed $value): bool
    {
        $amount = Amount::normalize($value);

        return $amount !== null && strlen((string) strstr($amount, '.', true)) <= self::AMOUNT_MAX_INTEGER_DIGITS;
    }

    /**
     * Check that a value is a non-negative integer (int or digit string) with at most $digits digits.
     *
     * @param mixed $value Payload value
     * @param int $digits Maximum number of digits
     * @return bool
     */
    private static function isValidInteger(mixed $value, int $digits): bool
    {
        if (is_int($value)) {
            return $value >= 0 && strlen((string) $value) <= $digits;
        }

        return is_string($value) && preg_match('/^\d{1,' . $digits . '}$/', $value) === 1;
    }

    /**
     * Check that a value is a valid UTF-8 string (or number) with at most $length characters.
     *
     * @param mixed $value Payload value
     * @param int $length Maximum length in characters
     * @return bool
     */
    private static function isValidString(mixed $value, int $length): bool
    {
        if (is_int($value) || is_float($value)) {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            return false;
        }

        $characters = preg_match_all('/./su', $value);

        return $characters !== false && $characters <= $length;
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
