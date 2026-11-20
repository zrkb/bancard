<?php

declare(strict_types=1);

namespace Bancard\Response;

class Response
{
    /**
     * Wrap a decoded Bancard response.
     *
     * @param \stdClass $data Decoded response body
     */
    public function __construct(
        protected readonly \stdClass $data,
    ) {
    }

    /**
     * Check whether the response status is "success".
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->getStatus() === 'success';
    }

    /**
     * Get the response status.
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return self::stringValue($this->data->status ?? null);
    }

    /**
     * Get the description of the first message.
     *
     * @return string|null
     */
    public function getMessage(): ?string
    {
        if (!isset($this->data->messages) || !is_array($this->data->messages)) {
            return null;
        }

        $first = $this->data->messages[0] ?? null;

        if ($first === null) {
            return null;
        }

        if (is_object($first) && isset($first->dsc)) {
            return self::stringValue($first->dsc);
        }

        if (is_string($first)) {
            return $first;
        }

        return null;
    }

    /**
     * Get the key of the first message.
     *
     * @return string|null
     */
    public function getErrorKey(): ?string
    {
        if (!isset($this->data->messages) || !is_array($this->data->messages)) {
            return null;
        }

        $first = $this->data->messages[0] ?? null;

        if (is_object($first) && isset($first->key)) {
            return self::stringValue($first->key);
        }

        return null;
    }

    /**
     * Convert a decoded value to string when it is a string or number.
     *
     * Bancard may send IDs and codes as JSON numbers; this avoids TypeErrors
     * in getters declared as ?string.
     *
     * @param mixed $value Decoded response value
     * @return string|null String value, or null when missing or not a string/number
     */
    protected static function stringValue(mixed $value): ?string
    {
        return is_string($value) || is_int($value) || is_float($value) ? (string) $value : null;
    }

    /**
     * Return the value when it is a decoded JSON object.
     *
     * @param mixed $value Decoded response value
     * @return \stdClass|null The object, or null when missing or not an object
     */
    protected static function objectValue(mixed $value): ?\stdClass
    {
        return $value instanceof \stdClass ? $value : null;
    }

    /**
     * Get the raw decoded response body.
     *
     * @return \stdClass
     */
    public function raw(): \stdClass
    {
        return $this->data;
    }
}
