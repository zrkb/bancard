<?php

declare(strict_types=1);

namespace Bancard\Response;

class ChargeResponse extends Response
{
    public function getProcessId(): ?string
    {
        return $this->data->confirmation->shop_process_id ?? null;
    }

    public function isApproved(): bool
    {
        $confirmation = $this->data->confirmation ?? null;

        return $confirmation !== null
            && isset($confirmation->response_code)
            && $confirmation->response_code === '00';
    }

    public function getResponseCode(): ?string
    {
        return $this->data->confirmation->response_code ?? null;
    }

    public function is3dsRedirect(): bool
    {
        return isset($this->data->redirect_agent) && $this->data->redirect_agent === true;
    }
}
