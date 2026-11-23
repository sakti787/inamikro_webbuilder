<?php

namespace App\Services;

class PublishValidationResult
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        protected bool $valid,
        protected array $errors = []
    ) {}

    /**
     * Determine whether the website publishing validation passed.
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Get the list of validation error messages.
     *
     * @return list<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if a specific error message exists in the result.
     */
    public function hasError(string $error): bool
    {
        return in_array($error, $this->errors, true);
    }
}
