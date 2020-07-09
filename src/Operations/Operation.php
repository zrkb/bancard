<?php

namespace Bancard\Operations;

use Bancard\Bancard;
use InvalidArgumentException;

abstract class Operation
{
    /**
     * @var string
     */
    protected $endpoint;

    /**
     * @var string
     */
    protected $method = 'POST';

    /**
     * Operation payload.
     *
     * @var array
     */
    protected $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Make a new operation.
     *
     * @param $payload
     * @return static
     */
    public static function make($payload)
    {
        return (new static($payload))->execute();
    }

    /**
     * Send request using an Http\ClientInterface object.
     *
     * @return mixed
     */
    public function execute()
    {
        return (new Bancard)->request($this->method, $this->endpoint, $this->data());
    }

    /**
     * The data that should sent to make an operation.
     *
     * @return array
     */
    public function data()
    {
        $operationData = array_filter($this->payload + ['token' => $this->token()]);

        return [
            'public_key' => Bancard::publicKey(),
            'operation' => $operationData,
        ];
    }

    /**
     * Return the value from the payload with the specified key.
     *
     * @param string $key
     * @return mixed|null
     */
    public function payload(string $key)
    {
        if (isset($this->payload[$key]) == false) {
            throw new InvalidArgumentException("Invalid key \"{$key}\" in payload.");
        }

        return $this->payload[$key];
    }

    /**
     * Make a new token.
     *
     * @return string
     */
    abstract public function token();
}
