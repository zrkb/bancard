<?php

declare(strict_types=1);

namespace Bancard\Exception;

use Bancard\Response\Response;

class ApiException extends BancardException
{
    public function __construct(
        private readonly Response $response,
    ) {
        parent::__construct($response->getMessage() ?? 'API request failed');
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getErrorKey(): ?string
    {
        return $this->response->getErrorKey();
    }

    public function getStatus(): ?string
    {
        return $this->response->getStatus();
    }
}
