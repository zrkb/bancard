<?php

declare(strict_types=1);

namespace Bancard\Response;

/**
 * Getters for a transaction confirmation (SingleBuyConfirmation element).
 *
 * Shared by the get_confirmation response, the preauthorization confirm
 * response and the confirmation callback Bancard POSTs to the commerce.
 * The using class must use JsonValues.
 */
trait ConfirmationDetails
{
    /**
     * Get the decoded confirmation object the getters read from.
     *
     * @return \stdClass|null
     */
    abstract protected function confirmationData(): ?\stdClass;

    /**
     * Check whether the transaction was approved (response code "00").
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->getResponseCode() === '00';
    }

    /**
     * Get the transaction response code ("00" approved, "05", "12", "15", "51"...).
     *
     * @return string|null
     */
    public function getResponseCode(): ?string
    {
        return $this->confirmationString('response_code');
    }

    /**
     * Get the transaction response description.
     *
     * @return string|null
     */
    public function getResponseDescription(): ?string
    {
        return $this->confirmationString('response_description');
    }

    /**
     * Get the extended transaction response description.
     *
     * @return string|null
     */
    public function getExtendedResponseDescription(): ?string
    {
        return $this->confirmationString('extended_response_description');
    }

    /**
     * Get the process description.
     *
     * @return string|null
     */
    public function getResponseDetails(): ?string
    {
        return $this->confirmationString('response_details');
    }

    /**
     * Get the commerce's shop process ID.
     *
     * @return string|null
     */
    public function getShopProcessId(): ?string
    {
        return $this->confirmationString('shop_process_id');
    }

    /**
     * Get the amount as sent by Bancard (e.g. "10100.00").
     *
     * @return string|null
     */
    public function getAmount(): ?string
    {
        return $this->confirmationString('amount');
    }

    /**
     * Get the IVA amount (digital services law commerces only).
     *
     * @return string|null
     */
    public function getIvaAmount(): ?string
    {
        return $this->confirmationString('iva_amount');
    }

    /**
     * Get the currency code.
     *
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->confirmationString('currency');
    }

    /**
     * Get the authorization code (approved transactions only).
     *
     * @return string|null
     */
    public function getAuthorizationNumber(): ?string
    {
        return $this->confirmationString('authorization_number');
    }

    /**
     * Get the authorization identifier.
     *
     * @return string|null
     */
    public function getTicketNumber(): ?string
    {
        return $this->confirmationString('ticket_number');
    }

    /**
     * Get the IVA ticket number.
     *
     * @return string|null
     */
    public function getIvaTicketNumber(): ?string
    {
        return $this->confirmationString('iva_ticket_number');
    }

    /**
     * Get the security information element.
     *
     * @return \stdClass|null
     */
    public function getSecurityInformation(): ?\stdClass
    {
        return self::objectValue($this->confirmationData()->security_information ?? null);
    }

    /**
     * Get the card source: "L" (local) or "I" (international).
     *
     * @return string|null
     */
    public function getCardSource(): ?string
    {
        return self::stringValue($this->getSecurityInformation()->card_source ?? null);
    }

    /**
     * Get the card's country of origin.
     *
     * @return string|null
     */
    public function getCardCountry(): ?string
    {
        return self::stringValue($this->getSecurityInformation()->card_country ?? null);
    }

    /**
     * Get the IP of the customer who entered the payment data.
     *
     * @return string|null
     */
    public function getCustomerIp(): ?string
    {
        return self::stringValue($this->getSecurityInformation()->customer_ip ?? null);
    }

    /**
     * Get the real-time risk index: 1-3 low, 4-6 medium, 7-9 high, 0 not available.
     *
     * Only local credit cards are scored; international, debit and other
     * processors' cards report 0.
     *
     * @return int|null
     */
    public function getRiskIndex(): ?int
    {
        $value = self::stringValue($this->getSecurityInformation()->risk_index ?? null);

        return $value !== null && ctype_digit($value) ? (int) $value : null;
    }

    /**
     * Get the extra attributes element.
     *
     * Present when requested, e.g. get_confirmation with
     * extra_response_attributes ["confirmation.extra_attributes"].
     *
     * @return \stdClass|null
     */
    public function getExtraAttributes(): ?\stdClass
    {
        return self::objectValue($this->confirmationData()->extra_attributes ?? null);
    }

    /**
     * Get the payment card type: "credit", "debit" (Bancard debit card) or "account_debit" (PIX, some QR).
     *
     * @return string|null
     */
    public function getPaymentCardType(): ?string
    {
        return $this->extraAttribute('payment_card_type');
    }

    /**
     * Get the payment card brand (e.g. "MC", "VS").
     *
     * @return string|null
     */
    public function getPaymentCardBrand(): ?string
    {
        return $this->extraAttribute('payment_card_brand');
    }

    /**
     * Get the masked payment card number (e.g. "541863******0014").
     *
     * @return string|null
     */
    public function getPaymentMaskedCardNumber(): ?string
    {
        return $this->extraAttribute('payment_masked_card_number');
    }

    /**
     * Get the flow the payment came from (e.g. "VPOS-FORM", "VPOS-TOKEN").
     *
     * @return string|null
     */
    public function getPaymentOrigin(): ?string
    {
        return $this->extraAttribute('payment_origin');
    }

    /**
     * Read an extra attribute from extra_attributes, or from the confirmation itself.
     *
     * The fallback covers single_buy's extra_response_attributes (["payment_card_type"]),
     * whose response placement the spec does not show.
     *
     * @param string $field Field name
     * @return string|null
     */
    private function extraAttribute(string $field): ?string
    {
        return self::stringValue($this->getExtraAttributes()->{$field} ?? $this->confirmationData()->{$field} ?? null);
    }

    /**
     * Read a string field of the confirmation.
     *
     * @param string $field Field name
     * @return string|null
     */
    private function confirmationString(string $field): ?string
    {
        return self::stringValue($this->confirmationData()->{$field} ?? null);
    }
}
