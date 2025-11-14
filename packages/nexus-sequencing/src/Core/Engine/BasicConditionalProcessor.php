<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Engine;

use Nexus\Sequencing\Core\Contracts\ConditionalProcessorInterface;
use Nexus\Sequencing\Core\Contracts\ValidationResult;
use Nexus\Sequencing\Core\ValueObjects\GenerationContext;
use DateTimeInterface;

/**
 * Basic Conditional Processor
 * 
 * Implements conditional pattern segments with simple comparison operators.
 * Supports equality, inequality, and basic numeric comparisons.
 * 
 * Syntax Examples:
 * - {?department=SALES?SAL:GEN} -> SAL if department=SALES, GEN otherwise
 * - {?tier=VIP?VIP-{COUNTER:4}:{COUNTER:4}} -> VIP-0001 or 0001
 * - {?amount>1000?LARGE:SMALL} -> LARGE if amount > 1000
 * 
 * @package Nexus\Sequencing\Core\Engine
 */
class BasicConditionalProcessor implements ConditionalProcessorInterface
{
    /**
     * Supported comparison operators
     */
    private const SUPPORTED_OPERATORS = ['=', '!=', '>', '<', '>=', '<=', 'in', 'not_in'];

    public function processConditionals(
        string $pattern,
        GenerationContext $context,
        DateTimeInterface $timestamp
    ): string {
        $processedPattern = $pattern;

        // Find all conditional segments: {?condition?true_value:false_value}
        $conditionalPattern = '/\{\?([^?]+)\?([^}:]+)(?::([^}]+))?\}/';
        
        $processedPattern = preg_replace_callback(
            $conditionalPattern,
            function ($matches) use ($context, $timestamp) {
                $condition = trim($matches[1]);
                $trueValue = trim($matches[2]);
                $falseValue = isset($matches[3]) ? trim($matches[3]) : '';

                $conditionMet = $this->evaluateCondition($condition, $context, $timestamp);

                return $conditionMet ? $trueValue : $falseValue;
            },
            $processedPattern
        );

        return $processedPattern ?? $pattern;
    }

    public function validateConditionalSyntax(string $pattern): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Extract conditionals to validate
        $conditionals = $this->extractConditionals($pattern);

        foreach ($conditionals as $conditional) {
            // Parse conditional: {?condition?true:false}
            if (!preg_match('/\{\?([^?]+)\?([^}:]+)(?::([^}]+))?\}/', $conditional, $matches)) {
                $errors[] = "Malformed conditional syntax: {$conditional}";
                continue;
            }

            $condition = trim($matches[1]);
            $trueValue = trim($matches[2]);
            $falseValue = isset($matches[3]) ? trim($matches[3]) : '';

            // Validate condition syntax
            $conditionErrors = $this->validateCondition($condition);
            $errors = array_merge($errors, $conditionErrors);

            // Check for empty values
            if (empty($trueValue)) {
                $warnings[] = "Empty true value in conditional: {$conditional}";
            }

            if (empty($falseValue) && isset($matches[3])) {
                $warnings[] = "Empty false value in conditional: {$conditional}";
            }

            // Check for nested conditionals (not supported yet)
            if (strpos($trueValue, '{?') !== false || strpos($falseValue, '{?') !== false) {
                $errors[] = "Nested conditionals not supported: {$conditional}";
            }
        }

        return new ValidationResult(
            isValid: empty($errors),
            errors: $errors,
            warnings: $warnings
        );
    }

    public function extractConditionals(string $pattern): array
    {
        $conditionals = [];
        
        if (preg_match_all('/\{\?[^}]+\}/', $pattern, $matches)) {
            $conditionals = $matches[0];
        }

        return $conditionals;
    }

    public function hasConditionals(string $pattern): bool
    {
        return strpos($pattern, '{?') !== false;
    }

    public function getSupportedOperators(): array
    {
        return self::SUPPORTED_OPERATORS;
    }

    /**
     * Evaluate a single condition against the context.
     * 
     * @param string $condition The condition to evaluate (e.g., "department=SALES")
     * @param GenerationContext $context Context data
     * @param DateTimeInterface $timestamp Current timestamp for date conditions
     * @return bool True if condition is met
     */
    private function evaluateCondition(
        string $condition,
        GenerationContext $context,
        DateTimeInterface $timestamp
    ): bool {
        // Parse condition: variable operator value
        foreach (self::SUPPORTED_OPERATORS as $operator) {
            if (strpos($condition, $operator) !== false) {
                [$variable, $value] = array_map('trim', explode($operator, $condition, 2));
                
                return $this->compareValues(
                    $variable,
                    $operator,
                    $value,
                    $context,
                    $timestamp
                );
            }
        }

        // If no operator found, treat as existence check
        $variable = trim($condition);
        $contextValue = $this->getContextValue($variable, $context, $timestamp);
        
        return !empty($contextValue);
    }

    /**
     * Compare context value with expected value using operator.
     */
    private function compareValues(
        string $variable,
        string $operator,
        string $expectedValue,
        GenerationContext $context,
        DateTimeInterface $timestamp
    ): bool {
        $actualValue = $this->getContextValue($variable, $context, $timestamp);

        // Handle null/empty values
        if ($actualValue === null || $actualValue === '') {
            return $operator === '!=' ? $expectedValue !== '' : false;
        }

        // Convert values for comparison
        $actual = $this->normalizeValue($actualValue);
        $expected = $this->normalizeValue($expectedValue);

        return match ($operator) {
            '=' => $actual === $expected,
            '!=' => $actual !== $expected,
            '>' => $this->numericComparison($actual, $expected, '>'),
            '<' => $this->numericComparison($actual, $expected, '<'),
            '>=' => $this->numericComparison($actual, $expected, '>='),
            '<=' => $this->numericComparison($actual, $expected, '<='),
            'in' => $this->inArray($actual, $expected),
            'not_in' => !$this->inArray($actual, $expected),
            default => false,
        };
    }

    /**
     * Get value from context, supporting both regular context and built-in variables.
     */
    private function getContextValue(
        string $variable,
        GenerationContext $context,
        DateTimeInterface $timestamp
    ): mixed {
        // Check for built-in date variables
        $builtInValue = $this->getBuiltInVariable($variable, $timestamp);
        if ($builtInValue !== null) {
            return $builtInValue;
        }

        // Check context
        return $context->get($variable) ?? $context->get(strtolower($variable));
    }

    /**
     * Get built-in variable value (subset of date variables for conditions).
     */
    private function getBuiltInVariable(string $variable, DateTimeInterface $timestamp): ?string
    {
        return match (strtoupper($variable)) {
            'YEAR' => $timestamp->format('Y'),
            'MONTH' => $timestamp->format('m'),
            'DAY' => $timestamp->format('d'),
            'QUARTER' => $this->getQuarter($timestamp),
            'WEEK' => $timestamp->format('W'),
            'DAY_OF_WEEK' => $timestamp->format('N'),
            default => null,
        };
    }

    /**
     * Get quarter number from date.
     */
    private function getQuarter(DateTimeInterface $date): string
    {
        $month = (int) $date->format('n');
        
        return match (true) {
            $month >= 1 && $month <= 3 => '1',
            $month >= 4 && $month <= 6 => '2',
            $month >= 7 && $month <= 9 => '3',
            $month >= 10 && $month <= 12 => '4',
            default => '1',
        };
    }

    /**
     * Normalize value for comparison (trim, case-insensitive).
     */
    private function normalizeValue(mixed $value): string
    {
        return strtoupper(trim((string) $value));
    }

    /**
     * Perform numeric comparison if both values are numeric.
     */
    private function numericComparison(string $actual, string $expected, string $operator): bool
    {
        if (!is_numeric($actual) || !is_numeric($expected)) {
            return false; // Non-numeric values can't be compared numerically
        }

        $actualNum = (float) $actual;
        $expectedNum = (float) $expected;

        return match ($operator) {
            '>' => $actualNum > $expectedNum,
            '<' => $actualNum < $expectedNum,
            '>=' => $actualNum >= $expectedNum,
            '<=' => $actualNum <= $expectedNum,
            default => false,
        };
    }

    /**
     * Check if value is in comma-separated list.
     */
    private function inArray(string $actual, string $expected): bool
    {
        $expectedValues = array_map('trim', explode(',', $expected));
        $normalizedExpected = array_map(fn($v) => $this->normalizeValue($v), $expectedValues);
        
        return in_array($actual, $normalizedExpected, true);
    }

    /**
     * Validate condition syntax.
     */
    private function validateCondition(string $condition): array
    {
        $errors = [];

        if (empty(trim($condition))) {
            $errors[] = 'Empty condition';
            return $errors;
        }

        $hasOperator = false;
        foreach (self::SUPPORTED_OPERATORS as $operator) {
            if (strpos($condition, $operator) !== false) {
                $hasOperator = true;
                $parts = explode($operator, $condition);
                
                if (count($parts) !== 2) {
                    $errors[] = "Invalid condition syntax: {$condition}";
                }
                
                if (empty(trim($parts[0]))) {
                    $errors[] = "Missing variable in condition: {$condition}";
                }
                
                if (empty(trim($parts[1])) && !in_array($operator, ['=', '!='])) {
                    $errors[] = "Missing value in condition: {$condition}";
                }
                
                break;
            }
        }

        if (!$hasOperator) {
            // Existence check - just validate variable name
            $variable = trim($condition);
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $variable)) {
                $errors[] = "Invalid variable name in condition: {$variable}";
            }
        }

        return $errors;
    }
}