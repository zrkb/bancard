<?php

declare(strict_types=1);

namespace Bancard\Exception;

class ValidationException extends BancardException
{
    /** @var list<string> */
    private readonly array $errors;

    /**
     * Create a validation exception for the given fields.
     *
     * @param list<string> $errors Names of the fields that failed validation
     * @param string|null $message Custom message; defaults to a "missing required fields" message
     */
    public function __construct(array $errors, ?string $message = null)
    {
        $this->errors = $errors;

        parent::__construct($message ?? 'Missing required fields: ' . implode(', ', $errors));
    }

    /**
     * Get the names of the fields that failed validation.
     *
     * @return list<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
