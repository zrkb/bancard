<?php

declare(strict_types=1);

namespace Bancard\Exception;

class ValidationException extends BancardException
{
    /** @var list<string> */
    private readonly array $errors;

    /**
     * @param list<string> $errors
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;

        parent::__construct('Missing required fields: ' . implode(', ', $errors));
    }

    /**
     * @return list<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
