<?php

declare(strict_types=1);

namespace Bancard\Response;

class SingleBuyResponse extends Response
{
    public function getProcessId(): ?string
    {
        return $this->data->process_id ?? null;
    }
}
