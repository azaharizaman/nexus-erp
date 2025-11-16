<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Contracts;

/**
 * Custom Variable Registry Interface
 * 
 * Contract for managing custom pattern variables. The registry provides
 * a centralized way to register, discover, and validate custom variables
 * for use in sequence patterns.
 * 
 * @package Nexus\Sequencing\Core\Contracts
 */
interface VariableRegistryInterface
{
    /**
     * Register a custom variable.
     * 
     * @param CustomVariableInterface $variable The variable to register
     * @throws \InvalidArgumentException If variable name is invalid or already registered
     */
    public function register(CustomVariableInterface $variable): void;

    /**
     * Check if a variable is registered.
     * 
     * @param string $name Variable name (case-insensitive)
     * @return bool True if variable exists
     */
    public function has(string $name): bool;

    /**
     * Get a registered variable by name.
     * 
     * @param string $name Variable name (case-insensitive)
     * @return CustomVariableInterface|null The variable or null if not found
     */
    public function get(string $name): ?CustomVariableInterface;

    /**
     * Get all registered variables.
     * 
     * @return CustomVariableInterface[] Array of all registered variables
     */
    public function all(): array;

    /**
     * Get all registered variable names.
     * 
     * @return string[] Array of variable names
     */
    public function getNames(): array;

    /**
     * Remove a variable from the registry.
     * 
     * @param string $name Variable name
     * @return bool True if variable was removed, false if not found
     */
    public function remove(string $name): bool;

    /**
     * Clear all registered variables.
     */
    public function clear(): void;

    /**
     * Get variables that require specific context keys.
     * 
     * @param string[] $availableKeys Available context keys
     * @return CustomVariableInterface[] Variables that can be resolved with given context
     */
    public function getResolvableVariables(array $availableKeys): array;

    /**
     * Validate all registered variables against a generation context.
     * 
     * @param \Nexus\Sequencing\Core\ValueObjects\GenerationContext $context
     * @return ValidationResult Combined validation result
     */
    public function validateContext(\Nexus\Sequencing\Core\ValueObjects\GenerationContext $context): ValidationResult;
}