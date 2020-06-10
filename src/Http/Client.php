<?php

namespace Bancard\Http;

use Bancard\Bancard;
use GuzzleHttp\Client as GuzzleClient;

class Client implements ClientInterface
{
    /**
     * @var GuzzleHttp\ClientInterface
     */
    private $http;

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

    public function http()
    {
        if (!$this->http) {
            $this->http = new GuzzleClient([
                'base_uri' => $this->baseUri(),
            ]);
        }

        return $this->http;
    }

    /**
     * Make an http request.
     *
     * @param string $method
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return mixed
     * @throws \Exception
     */
    public function request(string $method, string $url, array $params = [], array $headers = [])
    {
        $keyParams = strtoupper($method) == 'GET' ? 'query' : 'json';

        $response = $this->http()->request($method, $url, [
            $keyParams => $params,
            'debug' => false,
        ]);

        $statusCode = $response->getStatusCode();

        if ($statusCode >= 300) {
            throw new \Exception("Failed to retrieve data. Code:" . $statusCode);
        }

        return json_decode((string) $response->getBody());
    }

    public function get(string $url, array $params = [])
    {
        return $this->request('get', $url, $params);
    }

    public function post(string $url, array $params = [])
    {
        return $this->request('post', $url, $params);
    }

    /**
     * Base url for client.
     *
     * @return string
     */
    public function baseUri()
    {
        return Bancard::staging() ?
                $this->stagingBaseUrl :
                $this->productionBaseUrl;
    }
}
