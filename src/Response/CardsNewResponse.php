<?php

declare(strict_types=1);

namespace Bancard\Response;

class CardsNewResponse extends Response
{
    public function getProcessId(): ?string
    {
        return $this->data->process_id ?? null;
    }
}
