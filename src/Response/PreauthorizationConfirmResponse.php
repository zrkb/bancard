<?php

declare(strict_types=1);

namespace Bancard\Response;

class PreauthorizationConfirmResponse extends Response
{
    use ConfirmationDetails;

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
     * Get the confirmation object the ConfirmationDetails getters read from.
     *
     * @return \stdClass|null
     */
    protected function confirmationData(): ?\stdClass
    {
        return $this->getConfirmation();
    }
}
