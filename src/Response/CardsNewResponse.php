<?php

declare(strict_types=1);

namespace Bancard\Response;

class CardsNewResponse extends Response
{
    /**
     * Get the process ID used to load the card registration iframe.
     *
     * @return string|null
     */
    public function getProcessId(): ?string
    {
        return self::stringValue($this->data->process_id ?? null);
    }
}
