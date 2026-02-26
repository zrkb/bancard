<?php

declare(strict_types=1);

namespace Bancard;

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
use GuzzleHttp\Client as GuzzleClient;

class Bancard
{
    private const PRODUCTION_BASE_URL = 'https://vpos.infonet.com.py/';
    private const STAGING_BASE_URL = 'https://vpos.infonet.com.py:8888/';

    private GuzzleClient $http;

    /**
     * @param array<string, mixed> $guzzle
     */
    public function __construct(
        public readonly string $publicKey,
        public readonly string $privateKey,
        public readonly bool $staging = false,
        array $guzzle = [],
    ) {
        $guzzle['base_uri'] = $this->baseUri();

        $this->http = new GuzzleClient($guzzle);
    }

    public function baseUri(): string
    {
        return $this->staging ? self::STAGING_BASE_URL : self::PRODUCTION_BASE_URL;
    }

    public function setHttp(GuzzleClient $http): void
    {
        $this->http = $http;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function request(string $method, string $url, array $params): \stdClass
    {
        $response = $this->http->request($method, $url, ['json' => $params]);

        /** @var \stdClass */
        return json_decode((string) $response->getBody());
    }

    /**
     * @param array{
     *   shop_process_id: int|string,
     *   amount: string,
     *   currency: string,
     *   return_url?: string,
     *   cancel_url?: string,
     *   description?: string,
     *   additional_data?: string,
     *   iva_amount?: string,
     *   preauthorization?: string,
     *   extra_response_attributes?: list<string>,
     *   billing?: array<string, mixed>,
     * } $payload
     */
    public function singleBuy(array $payload): SingleBuyResponse
    {
        return (new SingleBuy($this, $payload))->execute();
    }

    /**
     * @param array{
     *   shop_process_id: int|string,
     *   amount: string,
     *   currency: string,
     * } $payload
     */
    public function singleBuyConfirm(array $payload): ConfirmTokenResponse
    {
        return (new SingleBuyConfirm($this, $payload))->execute();
    }

    /**
     * @param array{
     *   shop_process_id: int|string,
     * } $payload
     */
    public function singleBuyGetConfirmation(array $payload): ConfirmationResponse
    {
        return (new SingleBuyGetConfirmation($this, $payload))->execute();
    }

    /**
     * @param array{
     *   shop_process_id: int|string,
     * } $payload
     */
    public function singleBuyRollback(array $payload): RollbackResponse
    {
        return (new SingleBuyRollback($this, $payload))->execute();
    }

    /**
     * @param array{
     *   card_id: int|string,
     *   user_id: int|string,
     *   return_url?: string,
     *   cancel_url?: string,
     *   billing?: array<string, mixed>,
     * } $payload
     */
    public function cardsNew(array $payload): CardsNewResponse
    {
        return (new CardsNew($this, $payload))->execute();
    }

    /**
     * @param array{
     *   user_id: int|string,
     * } $payload
     */
    public function usersCards(array $payload): UsersCardsResponse
    {
        return (new UsersCards($this, $payload))->execute();
    }

    /**
     * @param array{
     *   shop_process_id: int|string,
     *   amount: string,
     *   currency: string,
     *   alias_token: string,
     *   description?: string,
     *   additional_data?: string,
     *   iva_amount?: string,
     *   preauthorization?: string,
     *   extra_response_attributes?: list<string>,
     *   billing?: array<string, mixed>,
     * } $payload
     */
    public function charge(array $payload): ChargeResponse
    {
        return (new Charge($this, $payload))->execute();
    }

    /**
     * @param array{
     *   user_id: int|string,
     *   alias_token: string,
     * } $payload
     */
    public function deleteCard(array $payload): DeleteCardResponse
    {
        return (new DeleteCard($this, $payload))->execute();
    }

    /**
     * @param array{
     *   shop_process_id: int|string,
     * } $payload
     */
    public function preauthorizationConfirm(array $payload): PreauthorizationConfirmResponse
    {
        return (new PreauthorizationConfirm($this, $payload))->execute();
    }

    /**
     * @param array{
     *   client_ruc?: string,
     *   client_name?: string,
     *   client_email?: string,
     * } $payload
     */
    public function billingClientInfo(array $payload): BillingClientInfoResponse
    {
        return (new BillingClientInfo($this, $payload))->execute();
    }

    /**
     * @param array{
     *   shop_process_id: int|string,
     * } $payload
     */
    public function billingCancel(array $payload): BillingCancelResponse
    {
        return (new BillingCancel($this, $payload))->execute();
    }

    /**
     * @param array{
     *   shop_process_id: int|string,
     *   amount: string,
     *   currency: string,
     *   return_url?: string,
     *   cancel_url?: string,
     *   description?: string,
     *   additional_data?: string,
     *   iva_amount?: string,
     *   preauthorization?: string,
     *   extra_response_attributes?: list<string>,
     *   billing?: array<string, mixed>,
     * } $payload
     */
    public function singleBuyZimple(array $payload): SingleBuyResponse
    {
        $payload['zimple'] = 'S';

        return (new SingleBuy($this, $payload))->execute();
    }
}
