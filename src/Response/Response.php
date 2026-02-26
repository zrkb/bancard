<?php

declare(strict_types=1);

namespace Bancard\Response;

class Response
{
    public function __construct(
        protected readonly \stdClass $data,
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->getStatus() === 'success';
    }

    public function getStatus(): ?string
    {
        return $this->data->status ?? null;
    }

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
            return $first->dsc;
        }

        if (is_string($first)) {
            return $first;
        }

        return null;
    }

    public function getErrorKey(): ?string
    {
        if (!isset($this->data->messages) || !is_array($this->data->messages)) {
            return null;
        }

        $first = $this->data->messages[0] ?? null;

        if (is_object($first) && isset($first->key)) {
            return $first->key;
        }

        return null;
    }

    public function raw(): \stdClass
    {
        return $this->data;
    }
}
