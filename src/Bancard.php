<?php

namespace Bancard;

use Bancard\Operations\Operation;
use Bancard\Operations\SingleBuy;
use Bancard\Operations\SingleBuyConfirm;
use Bancard\Operations\SingleBuyGetConfirmation;
use Bancard\Operations\SingleBuyRollback;
use Zero\Http\Client;

class Bancard extends Client
{
    /**
     * Production base URL
     *
     * @var string
     */
    protected $productionBaseUrl = 'https://vpos.infonet.com.py/';

    /**
     * Staging base URL
     *
     * @var string
     */
    protected $stagingBaseUrl = 'https://vpos.infonet.com.py:8888/';

    /**
     * Private Key
     *
     * @var string
     */
    protected static $privateKey;

    /**
     * Public Key
     *
     * @var string
     */
    protected static $publicKey;

    /**
     * Public Key
     *
     * @var bool
     */
    protected static $staging = false;

    /**
     * Sets the Private Key to be used for requests.
     *
     * @param string $privateKey
     * 
     * @return void
     */
    public static function setPrivateKey($privateKey)
    {
        self::$privateKey = $privateKey;
    }

    /**
     * The Private Key used for requests.
     *
     * @return string
     */
    public static function privateKey(): string
    {
        return self::$privateKey;
    }

    /**
     * Sets the Public Key to be used for requests.
     *
     * @param string $publicKey
     * 
     * @return void
     */
    public static function setPublicKey($publicKey)
    {
        self::$publicKey = $publicKey;
    }

    /**
     * The Public Key used for requests.
     *
     * @return string
     */
    public static function publicKey(): string
    {
        return self::$publicKey;
    }

    /**
     * Sets the staging mode.
     *
     * @param bool $staging
     * 
     * @return void
     */
    public static function setStaging($staging)
    {
        self::$staging = $staging;
    }

    /**
     * The staging mode.
     *
     * @return bool
     */
    public static function staging(): bool
    {
        return self::$staging;
    }

    /**
     * Base uri for client.
     *
     * @return string
     */
    public function baseUri(): string
    {
        return static::staging() ?
                $this->stagingBaseUrl :
                $this->productionBaseUrl;
    }

    public function singleBuy(array $payload): Operation
    {
        return SingleBuy::make($payload);
    }

    public function singleBuyConfirm(array $payload): Operation
    {
        return SingleBuyConfirm::make($payload);
    }

    public function singleBuyGetConfirmation(array $payload): Operation
    {
        return SingleBuyGetConfirmation::make($payload);
    }

    public function singleBuyRollback(array $payload): Operation
    {
        return SingleBuyRollback::make($payload);
    }
}
