<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Contracts;

use Nexus\Sequencing\Core\ValueObjects\GenerationContext;
use DateTimeInterface;

/**
 * Conditional Pattern Processor Interface
 * 
 * Contract for processing conditional pattern segments that change based
 * on context values. Enables dynamic pattern generation where different
 * segments are used based on runtime conditions.
 * 
 * Supported syntax:
 * - {?context_key=value?pattern_if_true}
 * - {?context_key=value?pattern_if_true:pattern_if_false}
 * - {?DEPARTMENT=SALES?SAL:GEN}-{COUNTER:4} -> SAL-0001 or GEN-0001
 * 
 * @package Nexus\Sequencing\Core\Contracts
 */
interface ConditionalProcessorInterface
{
    /**
     * Process conditional segments in a pattern.
     * 
     * @param string $pattern The pattern containing conditional segments
     * @param GenerationContext $context Context data for condition evaluation
     * @param DateTimeInterface $timestamp Current timestamp for date-based conditions
     * @return string Pattern with conditional segments resolved
     * 
     * @throws \InvalidArgumentException If conditional syntax is malformed
     * @throws \RuntimeException If condition evaluation fails
     */
    public function processConditionals(
        string $pattern,
        GenerationContext $context,
        DateTimeInterface $timestamp
    ): string;

    /**
     * Validate conditional syntax in a pattern.
     * 
     * @param string $pattern The pattern to validate
     * @return ValidationResult Validation result with any syntax errors
     */
    public function validateConditionalSyntax(string $pattern): ValidationResult;

    /**
     * Extract conditional segments from a pattern.
     * 
     * @param string $pattern The pattern to analyze
     * @return array<string> List of conditional segments found
     */
    public function extractConditionals(string $pattern): array;

    /**
     * Check if pattern contains conditional segments.
     * 
     * @param string $pattern The pattern to check
     * @return bool True if pattern contains conditionals
     */
    public function hasConditionals(string $pattern): bool;

    /**
     * Get supported condition operators.
     * 
     * @return string[] List of supported operators (=, !=, >, <, etc.)
     */
    public function getSupportedOperators(): array;
}