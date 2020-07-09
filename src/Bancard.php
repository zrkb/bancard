<?php

namespace Bancard;

use Bancard\Http\Client;
use Bancard\Operations\SingleBuy;
use Bancard\Operations\SingleBuyConfirmation;
use Bancard\Operations\SingleBuyRollback;

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
    public static function privateKey()
    {
        return self::$privateKey;
    }

    /**
     * Sets the Public Key to be used for requests.
     *
     * @param string $publicKey
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
    public static function publicKey()
    {
        return self::$publicKey;
    }

    /**
     * Sets the staging mode.
     *
     * @param bool $staging
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
    public static function staging()
    {
        return self::$staging;
    }

    /**
     * Base uri for client.
     *
     * @return string
     */
    public function baseUri()
    {
        return static::staging() ?
                $this->stagingBaseUrl :
                $this->productionBaseUrl;
    }

    public function singleBuy($payload)
    {
        return SingleBuy::make($payload);
    }

    public function singleBuyConfirmation($payload)
    {
        return SingleBuyConfirmation::make($payload);
    }

    public function singleBuyRollback($payload)
    {
        return SingleBuyRollback::make($payload);
    }
}
