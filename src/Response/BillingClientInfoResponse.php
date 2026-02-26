<?php

declare(strict_types=1);

namespace Bancard\Response;

class BillingClientInfoResponse extends Response
{
    public function getClient(): ?\stdClass
    {
        return $this->data->client ?? null;
    }
}
