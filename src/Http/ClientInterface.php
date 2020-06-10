<?php

namespace Bancard\Http;

interface ClientInterface
{
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
    public function request(string $method, string $url, array $params = [], array $headers = []);
}
