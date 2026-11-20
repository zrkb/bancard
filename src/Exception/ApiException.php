<?php

declare(strict_types=1);

namespace Bancard\Exception;

use Bancard\Response\Response;

class ApiException extends BancardException
{
    /**
     * Create an exception from a non-successful Bancard response.
     *
     * @param Response $response The failed response
     */
    public function __construct(
        private readonly Response $response,
    ) {
        parent::__construct($response->getMessage() ?? 'API request failed');
    }

    /**
     * Get the failed response.
     *
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Get the Bancard error key (e.g. "InvalidOperationError").
     *
     * @return string|null
     */
    public function getErrorKey(): ?string
    {
        return $this->response->getErrorKey();
    }

    /**
     * Get the response status.
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->response->getStatus();
    }
}
