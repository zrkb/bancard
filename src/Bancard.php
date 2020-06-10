<?php

namespace Bancard;

class Bancard
{
    /**
     * @var GuzzleHttp\Client
     */
    protected $http;

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
    public static function getPrivateKey()
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
    public static function getPublicKey()
    {
        return self::$publicKey;
    }

    protected function token()
    {
        return md5(
            self::$privateKey . implode( '', func_get_args())
        );
    }
}
