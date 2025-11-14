<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Contracts;

use Nexus\Sequencing\Core\ValueObjects\GenerationContext;
use DateTimeInterface;

/**
 * Custom Pattern Variable Interface
 * 
 * Contract for implementing custom pattern variables that can be injected
 * into the pattern evaluator system. Custom variables allow developers to
 * extend the sequence generation with domain-specific data.
 * 
 * Examples:
 * - {DEPARTMENT} -> "SALES", "HR", "IT" 
 * - {PROJECT_CODE} -> "PROJ001", "ALPHA" 
 * - {CUSTOMER_TIER} -> "VIP", "STANDARD"
 * 
 * @package Nexus\Sequencing\Core\Contracts
 */
interface CustomVariableInterface
{
    /**
     * Get the variable name (uppercase, no brackets).
     * 
     * @return string Variable name like "DEPARTMENT", "PROJECT_CODE"
     */
    public function getName(): string;

    /**
     * Get human-readable description of the variable.
     * 
     * @return string Description for documentation
     */
    public function getDescription(): string;

    /**
     * Resolve variable value from generation context.
     * 
     * This method receives the current generation context and timestamp
     * and should return the appropriate value for this variable.
     * 
     * @param GenerationContext $context User-provided context data
     * @param DateTimeInterface $timestamp Current generation timestamp
     * @return string The resolved variable value
     * 
     * @throws \InvalidArgumentException When required context is missing
     * @throws \RuntimeException When variable resolution fails
     */
    public function resolve(GenerationContext $context, DateTimeInterface $timestamp): string;

    /**
     * Validate that this variable can be resolved with given context.
     * 
     * This method should check if all required context data is available
     * without actually resolving the variable.
     * 
     * @param GenerationContext $context Context to validate
     * @return ValidationResult Validation result with any errors/warnings
     */
    public function validate(GenerationContext $context): ValidationResult;

    /**
     * Get list of context keys required by this variable.
     * 
     * @return string[] List of required context keys
     */
    public function getRequiredContextKeys(): array;

    /**
     * Get list of context keys optionally used by this variable.
     * 
     * @return string[] List of optional context keys
     */
    public function getOptionalContextKeys(): array;

    /**
     * Check if this variable supports parameterized syntax.
     * 
     * Example: {DEPARTMENT:UPPER} vs {DEPARTMENT}
     * 
     * @return bool True if variable supports parameters
     */
    public function supportsParameters(): bool;

    /**
     * Get list of supported parameters for this variable.
     * 
     * @return string[] List of supported parameter names
     */
    public function getSupportedParameters(): array;

    /**
     * Resolve variable with parameter.
     * 
     * Only called if supportsParameters() returns true.
     * 
     * @param GenerationContext $context User-provided context
     * @param DateTimeInterface $timestamp Current generation timestamp
     * @param string $parameter The parameter value
     * @return string The resolved variable value with parameter applied
     */
    public function resolveWithParameter(
        GenerationContext $context, 
        DateTimeInterface $timestamp, 
        string $parameter
    ): string;
}