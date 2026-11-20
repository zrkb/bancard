<?php

declare(strict_types=1);

namespace Bancard\Response;

class ConfirmTokenResponse extends Response
{
    private readonly string $token;

    /**
     * Create a response holding a locally generated confirm token.
     *
     * @param string $token MD5 confirm token
     */
    public function __construct(string $token)
    {
        $this->token = $token;

        parent::__construct((object) ['status' => 'success', 'token' => $token]);
    }

    /**
     * Get the confirm token.
     *
     * @return string MD5 hash
     */
    public function getToken(): string
    {
        return $this->token;
    }
}
