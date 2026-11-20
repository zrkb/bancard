<?php

declare(strict_types=1);

namespace Bancard\Response;

class Response
{
    use JsonValues;

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
     * Get the raw decoded response body.
     *
     * @return \stdClass
     */
    public function raw(): \stdClass
    {
        return $this->data;
    }
}
