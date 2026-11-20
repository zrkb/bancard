<?php

declare(strict_types=1);

namespace Bancard\Response;

class BillingClientInfoResponse extends Response
{
    /**
     * Get the billing client data.
     *
     * @return \stdClass|null
     */
    public function getClient(): ?\stdClass
    {
        return self::objectValue($this->data->client ?? null);
    }
}
