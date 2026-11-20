<?php

declare(strict_types=1);

namespace Bancard\Response;

class SingleBuyResponse extends Response
{
    /**
     * Get the process ID used to load the payment iframe.
     *
     * @return string|null
     */
    public function getProcessId(): ?string
    {
        return self::stringValue($this->data->process_id ?? null);
    }
}
