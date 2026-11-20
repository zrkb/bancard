<?php

declare(strict_types=1);

namespace Bancard;

use Bancard\Callback\ConfirmationCallback;
use Bancard\Exception\ApiException;
use Bancard\Exception\BancardException;
use Bancard\Exception\CardNotFoundException;
use Bancard\Exception\InvalidConfirmationException;
use Bancard\Exception\NetworkException;
use Bancard\Exception\ValidationException;
use Bancard\Operations\BillingCancel;
use Bancard\Operations\BillingClientInfo;
use Bancard\Operations\CardsNew;
use Bancard\Operations\Charge;
use Bancard\Operations\DeleteCard;
use Bancard\Operations\PreauthorizationConfirm;
use Bancard\Operations\SingleBuy;
use Bancard\Operations\SingleBuyConfirm;
use Bancard\Operations\SingleBuyGetConfirmation;
use Bancard\Operations\SingleBuyRollback;
use Bancard\Operations\UsersCards;
use Bancard\Response\BillingCancelResponse;
use Bancard\Response\BillingClientInfoResponse;
use Bancard\Response\CardsNewResponse;
use Bancard\Response\ChargeResponse;
use Bancard\Response\ConfirmationResponse;
use Bancard\Response\ConfirmTokenResponse;
use Bancard\Response\DeleteCardResponse;
use Bancard\Response\PreauthorizationConfirmResponse;
use Bancard\Response\RollbackResponse;
use Bancard\Response\SingleBuyResponse;
use Bancard\Response\UsersCardsResponse;
use Bancard\Util\Currency;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class Bancard
{
    private const PRODUCTION_BASE_URL = 'https://vpos.infonet.com.py/';
    private const STAGING_BASE_URL = 'https://vpos.infonet.com.py:8888/';

    /** Default total request timeout in seconds (overridable via the $guzzle option "timeout"). */
    public const DEFAULT_TIMEOUT = 30.0;

    /** Default connection timeout in seconds (overridable via the $guzzle option "connect_timeout"). */
    public const DEFAULT_CONNECT_TIMEOUT = 10.0;

    /** bancard-checkout.js version returned by checkoutScriptUrl() (vPOS spec 1.22). */
    public const CHECKOUT_JS_VERSION = '4.0.0';

    private ClientInterface $http;

    /**
     * Create a Bancard client.
     *
     * @param string $publicKey Commerce public key
     * @param string $privateKey Commerce private key (hidden from var_dump/print_r and stack traces)
     * @param bool $staging Use the staging environment instead of production
     * @param array<string, mixed> $guzzle Extra Guzzle client options; "base_uri" is always overridden
     */
    public function __construct(
        public readonly string $publicKey,
        #[\SensitiveParameter]
        public readonly string $privateKey,
        public readonly bool $staging = false,
        array $guzzle = [],
    ) {
        $guzzle['base_uri'] = $this->baseUri();
        $guzzle += [
            'timeout' => self::DEFAULT_TIMEOUT,
            'connect_timeout' => self::DEFAULT_CONNECT_TIMEOUT,
        ];

        $this->http = new GuzzleClient($guzzle);
    }

    /**
     * Get the API base URI for the configured environment.
     *
     * @return string Production or staging base URI
     */
    public function baseUri(): string
    {
        return $this->staging ? self::STAGING_BASE_URL : self::PRODUCTION_BASE_URL;
    }

    /**
     * Get the URL of bancard-checkout.js for the configured environment.
     *
     * Load it with a script tag, then mount the iframe with the process_id:
     * Bancard.Checkout.createForm() (single buy), Bancard.Zimple.createForm(),
     * Bancard.Cards.createForm() (cards_new) or Bancard.Charge3DS.createForm().
     *
     * @return string Script URL
     */
    public function checkoutScriptUrl(): string
    {
        return $this->baseUri() . 'checkout/javascript/dist/bancard-checkout-' . self::CHECKOUT_JS_VERSION . '.js';
    }

    /**
     * Replace the underlying Guzzle client (e.g. for testing).
     *
     * The client must have its own "base_uri" and timeouts configured.
     *
     * @param ClientInterface $http Guzzle client to use for requests
     */
    public function setHttp(ClientInterface $http): void
    {
        $this->http = $http;
    }

    /**
     * Send a JSON request to Bancard and decode the response.
     *
     * HTTP error statuses do not throw; their JSON body is returned so the
     * caller can inspect the Bancard error (operations raise ApiException).
     *
     * @param string $method HTTP method
     * @param string $url Endpoint path relative to the base URI
     * @param array<string, mixed> $params JSON request body
     * @return \stdClass Decoded response body
     *
     * @throws BancardException When the response body is not a JSON object
     * @throws NetworkException On transport errors (timeout, DNS, TLS); the previous exception is Guzzle's
     */
    public function request(string $method, string $url, array $params): \stdClass
    {
        try {
            $response = $this->http->request($method, $url, [
                'json' => $params,
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            throw new NetworkException("Request to Bancard failed: {$e->getMessage()}", 0, $e);
        }

        $status = $response->getStatusCode();

        try {
            $data = json_decode((string) $response->getBody(), false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new BancardException("Invalid JSON response from Bancard (HTTP {$status}).", $status, $e);
        }

        if (!$data instanceof \stdClass) {
            throw new BancardException("Unexpected response from Bancard (HTTP {$status}): expected a JSON object.", $status);
        }

        return $data;
    }

    /**
     * Debug representation with the private key redacted.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'publicKey' => $this->publicKey,
            'privateKey' => '[redacted]',
            'staging' => $this->staging,
        ];
    }

    /**
     * Start a single buy payment.
     *
     * @param array{
     *   shop_process_id: int|string,
     *   amount: int|float|string,
     *   currency: Currency|string,
     *   return_url?: string,
     *   cancel_url?: string,
     *   description?: string,
     *   additional_data?: string,
     *   iva_amount?: int|float|string,
     *   preauthorization?: string,
     *   extra_response_attributes?: list<string>,
     *   billing?: array<string, mixed>,
     * } $payload
     * @return SingleBuyResponse
     *
     * @throws ValidationException
     * @throws ApiException
     * @throws BancardException
     */
    public function singleBuy(array $payload): SingleBuyResponse
    {
        return (new SingleBuy($this, $payload))->execute();
    }

    /**
     * Generate the confirm token (no HTTP request is made).
     *
     * @param array{
     *   shop_process_id: int|string,
     *   amount: int|float|string,
     *   currency: Currency|string,
     * } $payload
     * @return ConfirmTokenResponse
     *
     * @throws ValidationException
     */
    public function singleBuyConfirm(array $payload): ConfirmTokenResponse
    {
        return (new SingleBuyConfirm($this, $payload))->execute();
    }

    /**
     * Verify the token of a payment confirmation Bancard sends to the confirmation URL.
     *
     * Pass the "operation" object of the callback body, e.g.
     * json_decode($body, true)['operation']. The token is compared in
     * constant time. Malformed input returns false instead of throwing.
     *
     * @param array<string, mixed> $operation Callback operation with token, shop_process_id, amount and currency
     * @return bool True when the token matches md5(private_key + shop_process_id + "confirm" + amount + currency)
     */
    public function verifyConfirmation(array $operation): bool
    {
        $token = $operation['token'] ?? null;

        if (!is_string($token) || $token === '') {
            return false;
        }

        $fields = [];

        foreach (['shop_process_id', 'amount', 'currency'] as $field) {
            $value = $operation[$field] ?? null;

            if (!is_string($value) && !is_int($value) && !is_float($value)) {
                return false;
            }

            $fields[$field] = $value;
        }

        try {
            $expected = (new SingleBuyConfirm($this, $fields))->execute()->getToken();
        } catch (ValidationException) {
            return false;
        }

        return hash_equals($expected, $token);
    }

    /**
     * Parse and verify the payment confirmation Bancard POSTs to the confirmation URL.
     *
     * On success, answer HTTP 200 with ConfirmationCallback::ACKNOWLEDGEMENT
     * as JSON within 30 seconds.
     *
     * @param string $body Raw request body, e.g. file_get_contents('php://input')
     * @return ConfirmationCallback Verified confirmation
     *
     * @throws InvalidConfirmationException When the body is not {"operation": {...}} or the token does not match
     */
    public function parseConfirmation(string $body): ConfirmationCallback
    {
        try {
            $data = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidConfirmationException('Invalid confirmation body: not valid JSON.', 0, $e);
        }

        $operation = $data instanceof \stdClass ? ($data->operation ?? null) : null;

        if (!$operation instanceof \stdClass) {
            throw new InvalidConfirmationException('Invalid confirmation body: expected a JSON object with an "operation" object.');
        }

        if (!$this->verifyConfirmation(get_object_vars($operation))) {
            throw new InvalidConfirmationException('Invalid confirmation token.');
        }

        return new ConfirmationCallback($operation);
    }

    /**
     * Query the confirmation status of a single buy.
     *
     * @param array{
     *   shop_process_id: int|string,
     * } $payload
     * @return ConfirmationResponse
     *
     * @throws ValidationException
     * @throws ApiException
     * @throws BancardException
     */
    public function singleBuyGetConfirmation(array $payload): ConfirmationResponse
    {
        return (new SingleBuyGetConfirmation($this, $payload))->execute();
    }

    /**
     * Roll back a single buy.
     *
     * @param array{
     *   shop_process_id: int|string,
     * } $payload
     * @return RollbackResponse
     *
     * @throws ValidationException
     * @throws ApiException
     * @throws BancardException
     */
    public function singleBuyRollback(array $payload): RollbackResponse
    {
        return (new SingleBuyRollback($this, $payload))->execute();
    }

    /**
     * Start registering a new card for a user.
     *
     * @param array{
     *   card_id: int|string,
     *   user_id: int|string,
     *   user_cell_phone: string,
     *   user_mail: string,
     *   return_url?: string,
     *   cancel_url?: string,
     *   billing?: array<string, mixed>,
     * } $payload
     * @return CardsNewResponse
     *
     * @throws ValidationException
     * @throws ApiException
     * @throws BancardException
     */
    public function cardsNew(array $payload): CardsNewResponse
    {
        return (new CardsNew($this, $payload))->execute();
    }

    /**
     * List the registered cards of a user.
     *
     * @param array{
     *   user_id: int|string,
     * } $payload
     * @return UsersCardsResponse
     *
     * @throws ValidationException
     * @throws ApiException
     * @throws BancardException
     */
    public function usersCards(array $payload): UsersCardsResponse
    {
        return (new UsersCards($this, $payload))->execute();
    }

    /**
     * Charge a registered card using its alias token.
     *
     * @param array{
     *   shop_process_id: int|string,
     *   amount: int|float|string,
     *   currency: Currency|string,
     *   alias_token: string,
     *   number_of_payments?: int,
     *   return_url?: string,
     *   description?: string,
     *   additional_data?: string,
     *   iva_amount?: int|float|string,
     *   preauthorization?: string,
     *   extra_response_attributes?: list<string>,
     *   billing?: array<string, mixed>,
     * } $payload
     * @return ChargeResponse
     *
     * @throws ValidationException
     * @throws ApiException
     * @throws BancardException
     */
    public function charge(array $payload): ChargeResponse
    {
        return (new Charge($this, $payload))->execute();
    }

    /**
     * Charge a registered card by card_id, fetching a fresh alias token first.
     *
     * Bancard alias tokens are single-use and expire within minutes, so store
     * user_id + card_id and use this for repeat payments (e.g. subscriptions).
     * Sends two requests: users_cards, then charge. Missing fields are
     * checked before any request.
     *
     * @param array{
     *   user_id: int|string,
     *   card_id: int|string,
     *   shop_process_id: int|string,
     *   amount: int|float|string,
     *   currency: Currency|string,
     *   number_of_payments?: int,
     *   return_url?: string,
     *   description?: string,
     *   additional_data?: string,
     *   iva_amount?: int|float|string,
     *   preauthorization?: string,
     *   extra_response_attributes?: list<string>,
     *   billing?: array<string, mixed>,
     * } $payload
     * @return ChargeResponse
     *
     * @throws ValidationException When required fields are missing or the amount is invalid
     * @throws CardNotFoundException When the user has no card with that card_id
     * @throws ApiException
     * @throws BancardException
     */
    public function chargeCard(array $payload): ChargeResponse
    {
        $missing = array_values(array_filter(
            ['user_id', 'card_id', 'shop_process_id', 'amount', 'currency'],
            fn (string $field): bool => !isset($payload[$field])
        ));

        if ($missing !== []) {
            throw new ValidationException($missing);
        }

        $card = $this->usersCards(['user_id' => $payload['user_id']])->findCard($payload['card_id']);
        $aliasToken = $card->alias_token ?? null;

        if (!is_string($aliasToken) || $aliasToken === '') {
            throw new CardNotFoundException((string) $payload['user_id'], (string) $payload['card_id']);
        }

        $charge = $payload;
        unset($charge['user_id'], $charge['card_id']);
        $charge['alias_token'] = $aliasToken;

        return (new Charge($this, $charge))->execute();
    }

    /**
     * Delete a registered card.
     *
     * @param array{
     *   user_id: int|string,
     *   alias_token: string,
     * } $payload
     * @return DeleteCardResponse
     *
     * @throws ValidationException
     * @throws ApiException
     * @throws BancardException
     */
    public function deleteCard(array $payload): DeleteCardResponse
    {
        return (new DeleteCard($this, $payload))->execute();
    }

    /**
     * Confirm a preauthorized payment.
     *
     * @param array{
     *   shop_process_id: int|string,
     * } $payload
     * @return PreauthorizationConfirmResponse
     *
     * @throws ValidationException
     * @throws ApiException
     * @throws BancardException
     */
    public function preauthorizationConfirm(array $payload): PreauthorizationConfirmResponse
    {
        return (new PreauthorizationConfirm($this, $payload))->execute();
    }

    /**
     * Get the electronic invoice client data (name, email) registered for a RUC.
     *
     * @param array{
     *   client_ruc: string,
     * } $payload
     * @return BillingClientInfoResponse
     *
     * @throws ValidationException
     * @throws ApiException
     * @throws BancardException
     */
    public function billingClientInfo(array $payload): BillingClientInfoResponse
    {
        return (new BillingClientInfo($this, $payload))->execute();
    }

    /**
     * Cancel a billing operation.
     *
     * @param array{
     *   shop_process_id: int|string,
     * } $payload
     * @return BillingCancelResponse
     *
     * @throws ValidationException
     * @throws ApiException
     * @throws BancardException
     */
    public function billingCancel(array $payload): BillingCancelResponse
    {
        return (new BillingCancel($this, $payload))->execute();
    }

    /**
     * Start a single buy payment with Zimple.
     *
     * @param array{
     *   shop_process_id: int|string,
     *   amount: int|float|string,
     *   currency: Currency|string,
     *   return_url?: string,
     *   cancel_url?: string,
     *   description?: string,
     *   additional_data?: string,
     *   iva_amount?: int|float|string,
     *   preauthorization?: string,
     *   extra_response_attributes?: list<string>,
     *   billing?: array<string, mixed>,
     * } $payload
     * @return SingleBuyResponse
     *
     * @throws ValidationException
     * @throws ApiException
     * @throws BancardException
     */
    public function singleBuyZimple(array $payload): SingleBuyResponse
    {
        $payload['zimple'] = 'S';

        return (new SingleBuy($this, $payload))->execute();
    }
}
