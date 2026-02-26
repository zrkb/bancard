<?php

declare(strict_types=1);

namespace Bancard\Response;

class ConfirmTokenResponse extends Response
{
    private readonly string $token;

    public function __construct(string $token)
    {
        $this->token = $token;

        parent::__construct((object) ['status' => 'success', 'token' => $token]);
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
