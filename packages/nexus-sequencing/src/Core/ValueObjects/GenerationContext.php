<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\ValueObjects;

/**
 * Generation Context Value Object
 * 
 * Immutable container for context variables used during pattern evaluation.
 * Provides type-safe access to variables like DEPARTMENT, PREFIX, etc.
 * 
 * This is a pure PHP Value Object with zero external dependencies.
 * 
 * @package Nexus\Sequencing\Core\ValueObjects
 */
readonly class GenerationContext
{
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        private array $variables = []
    ) {
        $this->validate();
    }

    /**
     * Validate context variables
     * 
     * @throws \InvalidArgumentException If any variable is invalid
     */
    private function validate(): void
    {
        foreach ($this->variables as $key => $value) {
            if (!is_string($key) || empty($key)) {
                throw new \InvalidArgumentException('Variable keys must be non-empty strings');
            }

            if (!is_scalar($value) && $value !== null) {
                throw new \InvalidArgumentException(
                    sprintf('Variable "%s" must be scalar or null, %s given', $key, gettype($value))
                );
            }
        }
    }

    /**
     * Create empty context
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Create context from array
     * 
     * @param array<string, mixed> $variables
     */
    public static function from(array $variables): self
    {
        return new self($variables);
    }

    /**
     * Check if variable exists
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->variables);
    }

    /**
     * Get variable value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->variables[$key] ?? $default;
    }

    /**
     * Get string variable value
     */
    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);
        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * Get integer variable value
     */
    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);
        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Add variable and return new context
     */
    public function with(string $key, mixed $value): self
    {
        $variables = $this->variables;
        $variables[$key] = $value;
        
        return new self($variables);
    }

    /**
     * Merge with another context
     */
    public function merge(GenerationContext $other): self
    {
        return new self(array_merge($this->variables, $other->variables));
    }

    /**
     * Get all variables
     * 
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->variables;
    }

    /**
     * Get variables as strings for template rendering
     * 
     * @return array<string, string>
     */
    public function toStringArray(): array
    {
        $strings = [];
        foreach ($this->variables as $key => $value) {
            $strings[$key] = is_scalar($value) ? (string) $value : '';
        }
        return $strings;
    }

    /**
     * Convert to array representation
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->variables;
    }
}