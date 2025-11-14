<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Engine;

use Nexus\Sequencing\Core\Contracts\VariableRegistryInterface;
use Nexus\Sequencing\Core\Contracts\CustomVariableInterface;
use Nexus\Sequencing\Core\Contracts\ValidationResult;
use Nexus\Sequencing\Core\ValueObjects\GenerationContext;

/**
 * Custom Variable Registry
 * 
 * Concrete implementation for managing custom pattern variables.
 * Provides thread-safe registration and lookup of custom variables
 * for use in sequence pattern evaluation.
 * 
 * @package Nexus\Sequencing\Core\Engine
 */
class VariableRegistry implements VariableRegistryInterface
{
    /**
     * Registry of custom variables
     * 
     * @var array<string, CustomVariableInterface>
     */
    private array $variables = [];

    public function register(CustomVariableInterface $variable): void
    {
        $name = strtoupper($variable->getName());

        // Validate variable name
        if (empty($name)) {
            throw new \InvalidArgumentException('Variable name cannot be empty');
        }

        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException(
                "Invalid variable name '{$name}'. Must be UPPERCASE with letters, numbers, and underscores only."
            );
        }

        // Check for reserved variable names
        if ($this->isReservedVariable($name)) {
            throw new \InvalidArgumentException(
                "Cannot register variable '{$name}'. This is a reserved built-in variable name."
            );
        }

        // Check if already registered
        if (isset($this->variables[$name])) {
            throw new \InvalidArgumentException(
                "Variable '{$name}' is already registered. Use remove() first to replace it."
            );
        }

        $this->variables[$name] = $variable;
    }

    public function has(string $name): bool
    {
        return isset($this->variables[strtoupper($name)]);
    }

    public function get(string $name): ?CustomVariableInterface
    {
        return $this->variables[strtoupper($name)] ?? null;
    }

    public function all(): array
    {
        return array_values($this->variables);
    }

    public function getNames(): array
    {
        return array_keys($this->variables);
    }

    public function remove(string $name): bool
    {
        $upperName = strtoupper($name);
        
        if (isset($this->variables[$upperName])) {
            unset($this->variables[$upperName]);
            return true;
        }

        return false;
    }

    public function clear(): void
    {
        $this->variables = [];
    }

    public function getResolvableVariables(array $availableKeys): array
    {
        $resolvable = [];
        $upperKeys = array_map('strtoupper', $availableKeys);

        foreach ($this->variables as $variable) {
            $requiredKeys = array_map('strtoupper', $variable->getRequiredContextKeys());
            
            // Check if all required keys are available
            if (empty(array_diff($requiredKeys, $upperKeys))) {
                $resolvable[] = $variable;
            }
        }

        return $resolvable;
    }

    public function validateContext(GenerationContext $context): ValidationResult
    {
        $errors = [];
        $warnings = [];

        foreach ($this->variables as $variable) {
            $result = $variable->validate($context);
            
            if ($result->hasErrors()) {
                foreach ($result->getErrors() as $error) {
                    $errors[] = "Variable {$variable->getName()}: {$error}";
                }
            }
            
            if ($result->hasWarnings()) {
                foreach ($result->getWarnings() as $warning) {
                    $warnings[] = "Variable {$variable->getName()}: {$warning}";
                }
            }
        }

        return new \Nexus\Sequencing\Core\Contracts\ValidationResult(
            isValid: empty($errors),
            errors: $errors,
            warnings: $warnings
        );
    }

    /**
     * Check if a variable name is reserved by the system.
     * 
     * @param string $name Variable name to check
     * @return bool True if variable is reserved
     */
    private function isReservedVariable(string $name): bool
    {
        $reservedVariables = [
            'YEAR',
            'MONTH',
            'DAY',
            'HOUR',
            'MINUTE',
            'SECOND',
            'COUNTER',
            'TIMESTAMP',
            'WEEK',         // Phase 2.3 additions
            'QUARTER',
            'WEEK_YEAR',
            'DAY_OF_WEEK',
            'DAY_OF_YEAR',
        ];

        return in_array($name, $reservedVariables, true);
    }
}