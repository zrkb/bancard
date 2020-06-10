<?php

namespace Bancard;

use GuzzleHttp\Client;
use Bancard\Http\ClientInterface;

class Bancard
{
    /**
     * @var Http\ClientInterface
     */
    protected static $httpClient;

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
     * @param string $http
     * @return Http\ClientInterface
     */
    public static function setHttpClient(ClientInterface $httpClient)
    {
        self::$httpClient = $httpClient;
    }

    /**
     * @return Client\ClientInterface
     */
    public static function httpClient()
    {
        if (!self::$httpClient) {
            self::$httpClient = new Http\Client;
        }

        return self::$httpClient;
    }

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
}
