<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Contracts;

/**
 * Validation Result Value Object
 * 
 * Encapsulates the result of a validation operation with
 * success status, error messages, and warnings.
 * 
 * @package Nexus\Sequencing\Core\Contracts
 */
readonly class ValidationResult
{
    public function __construct(
        public bool $isValid,
        public array $errors = [],
        public array $warnings = []
    ) {}

    /**
     * Create a successful validation result.
     */
    public static function success(array $warnings = []): self
    {
        return new self(true, [], $warnings);
    }

    /**
     * Create a failed validation result.
     */
    public static function failed(array $errors, array $warnings = []): self
    {
        return new self(false, $errors, $warnings);
    }

    /**
     * Get error messages.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get warning messages.
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get all messages (errors + warnings).
     */
    public function getAllMessages(): array
    {
        return array_merge($this->errors, $this->warnings);
    }

    /**
     * Check if result has any errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if result has any warnings.
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }
}