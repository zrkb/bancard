<?php

declare(strict_types=1);

namespace Bancard\Response;

class PreauthorizationConfirmResponse extends Response
{
    public function getConfirmation(): ?\stdClass
    {
        return $this->data->confirmation ?? null;
    }

    public function isApproved(): bool
    {
        $confirmation = $this->getConfirmation();

        return $confirmation !== null
            && isset($confirmation->response_code)
            && $confirmation->response_code === '00';
    }
}
