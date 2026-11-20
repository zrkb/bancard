<?php

declare(strict_types=1);

namespace Bancard\Callback;

use Bancard\Response\ConfirmationDetails;
use Bancard\Response\JsonValues;

/**
 * Payment confirmation Bancard POSTs to the commerce's confirmation URL.
 *
 * Obtain it from Bancard::parseConfirmation(), which verifies the token.
 * Answer HTTP 200 with ACKNOWLEDGEMENT as JSON within 30 seconds.
 */
class ConfirmationCallback
{
    use JsonValues;
    use ConfirmationDetails;

    /** Body Bancard expects in the HTTP 200 answer to the callback. */
    public const ACKNOWLEDGEMENT = ['status' => 'success'];

    /**
     * Wrap the callback's operation object.
     *
     * @param \stdClass $operation Decoded "operation" object of the callback body
     */
    public function __construct(
        private readonly \stdClass $operation,
    ) {
    }

    /**
     * Get the electronic billing result element, when the commerce uses billing.
     *
     * @return \stdClass|null
     */
    public function getBillingResponse(): ?\stdClass
    {
        return self::objectValue($this->operation->billing_response ?? null);
    }

    /**
     * Get the generated invoice number (e.g. "001-001-0002563").
     *
     * @return string|null
     */
    public function getInvoiceNumber(): ?string
    {
        $data = self::objectValue($this->getBillingResponse()->data ?? null);

        return self::stringValue($data->invoice_number ?? null);
    }

    /**
     * Get the raw decoded operation object.
     *
     * @return \stdClass
     */
    public function raw(): \stdClass
    {
        return $this->operation;
    }

    /**
     * Get the operation object the ConfirmationDetails getters read from.
     *
     * @return \stdClass
     */
    protected function confirmationData(): \stdClass
    {
        return $this->operation;
    }
}
