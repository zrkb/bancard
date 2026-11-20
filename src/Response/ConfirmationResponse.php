<?php

declare(strict_types=1);

namespace Bancard\Response;

class ConfirmationResponse extends Response
{
    /**
     * Get the confirmation data.
     *
     * @return \stdClass|null
     */
    public function getConfirmation(): ?\stdClass
    {
        return self::objectValue($this->data->confirmation ?? null);
    }

    /**
     * Check whether the payment was approved (response code "00").
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->getResponseCode() === '00';
    }

    /**
     * Get the confirmation response code.
     *
     * @return string|null
     */
    public function getResponseCode(): ?string
    {
        return self::stringValue($this->getConfirmation()->response_code ?? null);
    }
}
