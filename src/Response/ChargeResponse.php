<?php

declare(strict_types=1);

namespace Bancard\Response;

class ChargeResponse extends Response
{
    /**
     * Get the shop process ID from the confirmation.
     *
     * @return string|null
     */
    public function getProcessId(): ?string
    {
        return self::stringValue($this->getConfirmation()->shop_process_id ?? null);
    }

    /**
     * Check whether the charge was approved (response code "00").
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

    /**
     * Check whether 3DS authentication is required.
     *
     * @return bool
     */
    public function is3dsRedirect(): bool
    {
        return isset($this->data->redirect_agent) && $this->data->redirect_agent === true;
    }

    /**
     * Get the confirmation data.
     *
     * @return \stdClass|null
     */
    private function getConfirmation(): ?\stdClass
    {
        return self::objectValue($this->data->confirmation ?? null);
    }
}
