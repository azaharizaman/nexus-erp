<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\ValueObjects;

/**
 * Pattern Template Value Object
 * 
 * Immutable representation of a sequence pattern with basic validation.
 * Contains the raw pattern string and provides methods for pattern analysis.
 * 
 * This is a pure PHP Value Object with zero external dependencies.
 * 
 * @package Nexus\Sequencing\Core\ValueObjects
 */
readonly class PatternTemplate
{
    public function __construct(
        public string $pattern
    ) {
        $this->validate();
    }

    /**
     * Validate pattern string
     * 
     * @throws \InvalidArgumentException If pattern is invalid
     */
    private function validate(): void
    {
        if (empty($this->pattern)) {
            throw new \InvalidArgumentException('Pattern cannot be empty');
        }

        if (strlen($this->pattern) > 255) {
            throw new \InvalidArgumentException('Pattern cannot exceed 255 characters');
        }
    }

    /**
     * Create from string
     */
    public static function from(string $pattern): self
    {
        return new self($pattern);
    }

    /**
     * Extract variable placeholders from pattern
     * 
     * Returns array of variable names found in the pattern.
     * For example: "PO-{YEAR}-{COUNTER}" returns ["YEAR", "COUNTER"]
     * 
     * @return array<string>
     */
    public function extractVariables(): array
    {
        $matches = [];
        preg_match_all('/\{([^}]+)\}/', $this->pattern, $matches);
        
        $variables = [];
        foreach ($matches[1] as $match) {
            // Handle variables with parameters like {COUNTER:4}
            $variable = explode(':', $match)[0];
            if (!in_array($variable, $variables, true)) {
                $variables[] = $variable;
            }
        }
        
        return $variables;
    }

    /**
     * Check if pattern contains a specific variable
     */
    public function hasVariable(string $variable): bool
    {
        return in_array($variable, $this->extractVariables(), true);
    }

    /**
     * Check if pattern contains counter variable
     */
    public function hasCounter(): bool
    {
        return $this->hasVariable('COUNTER');
    }

    /**
     * Check if pattern is static (no variables)
     */
    public function isStatic(): bool
    {
        return empty($this->extractVariables());
    }

    /**
     * Get pattern complexity score (0-100)
     * 
     * Simple metric based on variable count and pattern length
     */
    public function getComplexity(): int
    {
        $variableCount = count($this->extractVariables());
        $lengthScore = min(strlen($this->pattern) / 100 * 50, 50);
        $variableScore = min($variableCount * 10, 50);
        
        return (int) ($lengthScore + $variableScore);
    }

    /**
     * Convert to string
     */
    public function toString(): string
    {
        return $this->pattern;
    }

    /**
     * Convert to array representation
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'pattern' => $this->pattern,
            'variables' => $this->extractVariables(),
            'has_counter' => $this->hasCounter(),
            'is_static' => $this->isStatic(),
            'complexity' => $this->getComplexity(),
        ];
    }
}