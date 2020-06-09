<?php

namespace Bancard\Http;

trait APIClient
{
    /**
     * Make an http request.
     *
     * @param string $method
     * @param string $url
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    public function request(string $method, string $url, array $params = [])
    {
        $key_params = $method == 'GET' ? 'query' : 'json';

        $response = $this->http->request($method, $url, [
            $key_params => $params,
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
}
