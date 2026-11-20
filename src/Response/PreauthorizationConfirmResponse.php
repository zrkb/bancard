<?php

declare(strict_types=1);

namespace Bancard\Response;

class PreauthorizationConfirmResponse extends Response
{
    /**
     * Get the confirmation data.
     *
     * @return \stdClass|null
     */
    public function getConfirmation(): ?\stdClass
    {
        return self::objectValue($this->data->confirmation ?? null);
    }

    /**
     * Check whether the preauthorization was approved (response code "00").
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        return self::stringValue($this->getConfirmation()->response_code ?? null) === '00';
    }
}
