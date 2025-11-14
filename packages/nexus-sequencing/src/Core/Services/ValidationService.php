<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Services;

use Nexus\Sequencing\Core\ValueObjects\PatternTemplate;
use Nexus\Sequencing\Core\ValueObjects\GenerationContext;
use Nexus\Sequencing\Core\Contracts\ValidationResult;

/**
 * Validation Service
 * 
 * Provides comprehensive validation for sequence patterns, contexts,
 * and configuration before sequence creation or number generation.
 * 
 * This is a pure PHP service with zero external dependencies.
 * 
 * @package Nexus\Sequencing\Core\Services
 */
class ValidationService
{
    /**
     * Validate a sequence pattern for syntax and supported variables.
     *
     * @param string $pattern The pattern to validate
     * @return ValidationResult
     */
    public function validatePattern(string $pattern): ValidationResult
    {
        $errors = [];
        $warnings = [];

        try {
            // Basic syntax validation
            if (empty($pattern)) {
                $errors[] = 'Pattern cannot be empty';
                return ValidationResult::failed($errors, $warnings);
            }

            if (strlen($pattern) > 500) {
                $errors[] = 'Pattern is too long (maximum 500 characters)';
            }

            // Create pattern template to analyze structure
            $template = PatternTemplate::from($pattern);
            $variables = $template->extractVariables();

            // Validate variable syntax
            foreach ($variables as $variable) {
                $variableErrors = $this->validateVariable($variable);
                $errors = array_merge($errors, $variableErrors);
            }

            // Pattern complexity validation
            $complexity = $template->getComplexity();
            if ($complexity > 100) {
                $warnings[] = "High pattern complexity ({$complexity}). Consider simplifying for better performance.";
            }

            // Check for required COUNTER variable
            if (!$template->hasCounter()) {
                $warnings[] = 'Pattern does not contain {COUNTER} variable. Static patterns may cause conflicts.';
            }

            // Validate bracket balance
            if (!$this->areBracketsBalanced($pattern)) {
                $errors[] = 'Unbalanced brackets in pattern';
            }

            // Check for conflicting variables
            $conflicts = $this->findVariableConflicts($variables);
            foreach ($conflicts as $conflict) {
                $errors[] = "Variable conflict: {$conflict}";
            }

        } catch (\Exception $e) {
            $errors[] = "Pattern analysis failed: {$e->getMessage()}";
        }

        return empty($errors) 
            ? ValidationResult::success($warnings)
            : ValidationResult::failed($errors, $warnings);
    }

    /**
     * Validate generation context against a pattern.
     *
     * @param GenerationContext $context The context to validate
     * @param string $pattern The pattern that will use this context
     * @return ValidationResult
     */
    public function validateContext(GenerationContext $context, string $pattern): ValidationResult
    {
        $errors = [];
        $warnings = [];

        try {
            $template = PatternTemplate::from($pattern);
            $requiredVariables = $template->extractVariables();
            $providedVariables = array_keys($context->all());

            // Check for missing context variables (excluding built-in ones)
            $builtInVariables = ['YEAR', 'MONTH', 'DAY', 'COUNTER', 'TIMESTAMP'];
            $missingVariables = [];

            foreach ($requiredVariables as $variable) {
                $baseVariable = explode(':', $variable)[0]; // Remove parameters like COUNTER:4
                if (!in_array($baseVariable, $builtInVariables) && !in_array($baseVariable, $providedVariables)) {
                    $missingVariables[] = $baseVariable;
                }
            }

            if (!empty($missingVariables)) {
                $errors[] = 'Missing context variables: ' . implode(', ', $missingVariables);
            }

            // Validate individual context values
            foreach ($context->all() as $key => $value) {
                $valueErrors = $this->validateContextValue($key, $value);
                $errors = array_merge($errors, $valueErrors);
            }

            // Check for unused context variables
            $unusedVariables = array_diff($providedVariables, $requiredVariables);
            if (!empty($unusedVariables)) {
                $warnings[] = 'Unused context variables: ' . implode(', ', $unusedVariables);
            }

        } catch (\Exception $e) {
            $errors[] = "Context validation failed: {$e->getMessage()}";
        }

        return empty($errors)
            ? ValidationResult::success($warnings)
            : ValidationResult::failed($errors, $warnings);
    }

    /**
     * Validate a complete sequence configuration.
     *
     * @param string $pattern The sequence pattern
     * @param GenerationContext|null $context Optional context for validation
     * @param array<string, mixed> $options Additional validation options
     * @return ValidationResult
     */
    public function validateSequenceConfiguration(
        string $pattern,
        ?GenerationContext $context = null,
        array $options = []
    ): ValidationResult {
        $errors = [];
        $warnings = [];

        // Validate pattern
        $patternResult = $this->validatePattern($pattern);
        $errors = array_merge($errors, $patternResult->getErrors());
        $warnings = array_merge($warnings, $patternResult->getWarnings());

        // Validate context if provided
        if ($context !== null) {
            $contextResult = $this->validateContext($context, $pattern);
            $errors = array_merge($errors, $contextResult->getErrors());
            $warnings = array_merge($warnings, $contextResult->getWarnings());
        }

        // Additional configuration validation
        if (isset($options['max_length']) && strlen($pattern) > $options['max_length']) {
            $errors[] = "Pattern exceeds maximum length of {$options['max_length']} characters";
        }

        if (isset($options['forbidden_variables'])) {
            $template = PatternTemplate::from($pattern);
            $variables = $template->extractVariables();
            $forbidden = array_intersect($variables, $options['forbidden_variables']);
            if (!empty($forbidden)) {
                $errors[] = 'Forbidden variables detected: ' . implode(', ', $forbidden);
            }
        }

        return empty($errors)
            ? ValidationResult::success($warnings)
            : ValidationResult::failed($errors, $warnings);
    }

    /**
     * Generate regex pattern from sequence pattern.
     * 
     * This creates a regular expression that can match generated numbers
     * from the given pattern.
     *
     * @param string $pattern The sequence pattern
     * @return string Regular expression pattern
     */
    public function generateRegexPattern(string $pattern): string
    {
        // Escape special regex characters except our variables
        $escaped = preg_quote($pattern, '/');
        
        // Replace our variable placeholders with regex patterns
        $replacements = [
            '\{YEAR\}' => '(\d{4})',
            '\{MONTH\}' => '(\d{2})',
            '\{DAY\}' => '(\d{2})',
            '\{TIMESTAMP\}' => '(\d+)',
            '\{COUNTER(?:\:\d+)?\}' => '(\d+)', // Matches {COUNTER} and {COUNTER:4}
        ];

        $regexPattern = $escaped;
        foreach ($replacements as $placeholder => $regex) {
            $regexPattern = preg_replace('/' . $placeholder . '/', $regex, $regexPattern);
        }

        // Handle custom variables (anything else in curly braces)
        $regexPattern = preg_replace('/\\\\\\{([^}]+)\\\\\\}/', '([^\\-]+)', $regexPattern);

        return '/^' . $regexPattern . '$/';
    }

    /**
     * Validate an individual variable name and syntax.
     */
    private function validateVariable(string $variable): array
    {
        $errors = [];

        // Check for empty variable
        if (empty($variable)) {
            $errors[] = 'Empty variable found in pattern';
            return $errors;
        }

        // Extract base variable and parameters
        $parts = explode(':', $variable);
        $baseVariable = $parts[0];
        $parameter = $parts[1] ?? null;

        // Validate variable name
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $baseVariable)) {
            $errors[] = "Invalid variable name: {$baseVariable}. Must be UPPERCASE with letters, numbers, and underscores only.";
        }

        // Validate specific variables and their parameters
        switch ($baseVariable) {
            case 'COUNTER':
                if ($parameter !== null) {
                    if (!ctype_digit($parameter)) {
                        $errors[] = "COUNTER parameter must be a number: {COUNTER:{$parameter}}";
                    } elseif ((int)$parameter < 1 || (int)$parameter > 20) {
                        $errors[] = "COUNTER parameter must be between 1 and 20: {COUNTER:{$parameter}}";
                    }
                }
                break;

            case 'YEAR':
            case 'MONTH':
            case 'DAY':
            case 'HOUR':
            case 'MINUTE':
            case 'SECOND':
            case 'TIMESTAMP':
                // Basic date/time variables - parameters for formatting are allowed
                if ($parameter !== null) {
                    // Validate formatting parameters
                    if (!$this->isValidDateFormatParameter($baseVariable, $parameter)) {
                        $errors[] = "Invalid format parameter '{$parameter}' for {$baseVariable} variable";
                    }
                }
                break;

            case 'WEEK':
            case 'QUARTER':
            case 'WEEK_YEAR':
            case 'DAY_OF_WEEK':
            case 'DAY_OF_YEAR':
                // Advanced date variables (Phase 2.3) - support formatting parameters
                if ($parameter !== null) {
                    if (!$this->isValidAdvancedDateFormatParameter($baseVariable, $parameter)) {
                        $errors[] = "Invalid format parameter '{$parameter}' for {$baseVariable} variable";
                    }
                }
                break;
        }

        return $errors;
    }

    /**
     * Check if brackets are properly balanced in the pattern.
     */
    private function areBracketsBalanced(string $pattern): bool
    {
        $count = 0;
        $length = strlen($pattern);

        for ($i = 0; $i < $length; $i++) {
            if ($pattern[$i] === '{') {
                $count++;
            } elseif ($pattern[$i] === '}') {
                $count--;
                if ($count < 0) {
                    return false; // More closing than opening brackets
                }
            }
        }

        return $count === 0; // Should end with balanced brackets
    }

    /**
     * Find conflicting variable names.
     */
    private function findVariableConflicts(array $variables): array
    {
        $conflicts = [];
        $seen = [];

        foreach ($variables as $variable) {
            $baseVariable = explode(':', $variable)[0];
            
            if (in_array($baseVariable, $seen)) {
                $conflicts[] = "Duplicate variable: {$baseVariable}";
            } else {
                $seen[] = $baseVariable;
            }
        }

        return $conflicts;
    }

    /**
     * Validate individual context value.
     */
    private function validateContextValue(string $key, mixed $value): array
    {
        $errors = [];

        // Check value type
        if (!is_scalar($value) && $value !== null) {
            $errors[] = "Context variable '{$key}' must be scalar (string, number, boolean, or null)";
            return $errors;
        }

        // Validate string values
        if (is_string($value)) {
            if (strlen($value) > 100) {
                $errors[] = "Context variable '{$key}' is too long (maximum 100 characters)";
            }

            if (strpos($value, '{') !== false || strpos($value, '}') !== false) {
                $errors[] = "Context variable '{$key}' cannot contain curly braces";
            }
        }

        return $errors;
    }

    /**
     * Validate format parameters for basic date/time variables.
     */
    private function isValidDateFormatParameter(string $variable, string $parameter): bool
    {
        // Numeric parameters for padding are always valid
        if (is_numeric($parameter) && (int)$parameter >= 1 && (int)$parameter <= 10) {
            return true;
        }

        // Variable-specific format validation
        return match ($variable) {
            'DAY' => in_array(strtoupper($parameter), ['ST', 'ND', 'RD', 'TH']),
            'YEAR' => in_array(strtoupper($parameter), ['YY', 'YYYY']),
            'MONTH' => in_array(strtoupper($parameter), ['MM', 'MON', 'MONTH']),
            'HOUR' => in_array(strtoupper($parameter), ['H', 'HH']),
            'MINUTE' => in_array(strtoupper($parameter), ['M', 'MM']),
            'SECOND' => in_array(strtoupper($parameter), ['S', 'SS']),
            'TIMESTAMP' => in_array(strtoupper($parameter), ['S', 'MS', 'US']),
            default => false,
        };
    }

    /**
     * Validate format parameters for advanced date variables (Phase 2.3).
     */
    private function isValidAdvancedDateFormatParameter(string $variable, string $parameter): bool
    {
        // Numeric parameters for padding are always valid
        if (is_numeric($parameter) && (int)$parameter >= 1 && (int)$parameter <= 10) {
            return true;
        }

        // Variable-specific format validation
        return match ($variable) {
            'QUARTER' => in_array(strtoupper($parameter), ['Q1', 'Q2', 'Q3', 'Q4', 'QTR', 'QUARTER']),
            'WEEK' => in_array(strtoupper($parameter), ['W', 'WEEK', 'WK']),
            'DAY_OF_WEEK' => in_array(strtoupper($parameter), ['SHORT', 'LONG', 'ISO']),
            'DAY_OF_YEAR' => is_numeric($parameter),
            'WEEK_YEAR' => in_array(strtoupper($parameter), ['YY', 'YYYY']),
            default => false,
        };
    }
}