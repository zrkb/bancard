<?php

declare(strict_types=1);

namespace Bancard\Response;

/**
 * Charge response: {"status": "success", "confirmation": {...}} (verified on staging).
 */
class ChargeResponse extends Response
{
    use ConfirmationDetails;

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
     * Check whether the card needs 3DS authentication to complete the charge.
     *
     * True when confirmation.process_id is set; charge() requests it by default
     * through extra_response_attributes ["confirmation.process_id"].
     *
     * @return bool
     */
    public function requires3ds(): bool
    {
        return $this->get3dsProcessId() !== null;
    }

    /**
     * Get the process ID to mount Bancard.Charge3DS.createForm() with.
     *
     * Read from confirmation.process_id (the shape staging uses), falling back
     * to operation.process_id (the shape of the spec's 3DS example).
     *
     * @return string|null Process ID, or null when no 3DS authentication is needed
     */
    public function get3dsProcessId(): ?string
    {
        $operation = self::objectValue($this->data->operation ?? null);
        $processId = self::stringValue($this->getConfirmation()->process_id ?? $operation->process_id ?? null);

        return $processId === '' ? null : $processId;
    }

    /**
     * Get the shop process ID from the confirmation.
     *
     * @deprecated 1.7 Use getShopProcessId(). This is the shop_process_id, not the 3DS process ID (get3dsProcessId()).
     *
     * @return string|null
     */
    public function getProcessId(): ?string
    {
        return $this->getShopProcessId();
    }

    /**
     * Check whether 3DS authentication is required.
     *
     * @deprecated 1.7 Use requires3ds(). Also true for the legacy redirect_agent flag, which the spec does not define.
     *
     * @return bool
     */
    public function is3dsRedirect(): bool
    {
        return $this->requires3ds() || (isset($this->data->redirect_agent) && $this->data->redirect_agent === true);
    }

    /**
     * Get the confirmation object the ConfirmationDetails getters read from.
     *
     * @return \stdClass|null
     */
    protected function confirmationData(): ?\stdClass
    {
        return $this->getConfirmation();
    }
}
