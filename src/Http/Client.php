<?php

namespace Bancard\Http;

use GuzzleHttp\Client as GuzzleClient;

abstract class Client implements ClientInterface
{
    /**
     * @var GuzzleHttp\ClientInterface
     */
    private $http;

    /**
     * Sets the http client.
     *
     * @param string $http
     * @return GuzzleHttp\ClientInterface
     */
    public function setHttp($http)
    {
        $this->http = $http;
    }

    /**
     * Returns the http client.
     *
     * @return GuzzleHttp\ClientInterface
     */
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
     * @throws \Throwable
     */
    public function request(string $method, string $url, array $params = [], array $headers = [])
    {
        $keyParams = strtoupper($method) == 'GET' ? 'query' : 'json';

        $response = $this->http()->request($method, $url, [
            $keyParams => $params,
            'debug' => false,
        ]);

        $body = json_decode((string) $response->getBody(), false, 512, JSON_THROW_ON_ERROR);

        return $body;
    }

    public function get(string $url, array $params = [], array $headers = [])
    {
        return $this->request('get', $url, $params, $headers);
    }

    public function post(string $url, array $params = [], array $headers = [])
    {
        return $this->request('post', $url, $params, $headers);
    }
}
