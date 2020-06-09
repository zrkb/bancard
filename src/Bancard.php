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

    // md5(private_key + shop_process_id + amount + currency)
    public function singleBuy()
    {
        $v_shop_process_id = '12';
        $v_amount = '100000.00';
        $v_description = 'Product description';

        $v_currency = 'PYG';
        $v_return_url = 'https://autocines.test/return';
        $v_cancel_url = 'https://autocines.test/cancel';

        $v_token = $this->token(
            $v_shop_process_id,
            $v_amount,
            $v_currency
        );

        $data = [
            'public_key' => self::$publicKey,
            'operation'  => [
                'token' => "$v_token",
                'shop_process_id' => $v_shop_process_id,
                'amount' => "$v_amount",
                'description' => "$v_description",
                'currency' => "$v_currency",
                // 'additional_data' => "",
                'return_url' => "$v_return_url",
                'cancel_url' => "$v_cancel_url",
            ],
        ];

        $json = json_encode($data);

        $ch = curl_init('https://vpos.infonet.com.py:8888/vpos/api/0.3/single_buy');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        dd("Respuesta de vPos: $result\n");
    }
}
