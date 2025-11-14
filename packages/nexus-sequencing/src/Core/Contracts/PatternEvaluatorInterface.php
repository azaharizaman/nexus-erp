<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Contracts;

use Nexus\Sequencing\Core\ValueObjects\PatternTemplate;
use Nexus\Sequencing\Core\ValueObjects\CounterState;
use Nexus\Sequencing\Core\ValueObjects\GenerationContext;

/**
 * Pattern Evaluator Interface
 * 
 * Contract for pattern parsing and variable substitution.
 * Allows swapping different pattern engines (regex, Twig, Blade, etc.)
 * for different complexity requirements.
 * 
 * This interface is framework-agnostic and can be implemented using
 * various template engines or custom parsing logic.
 * 
 * @package Nexus\Sequencing\Core\Contracts
 */
interface PatternEvaluatorInterface
{
    /**
     * Evaluate pattern with context variables and counter state
     * 
     * @param PatternTemplate $template The pattern template to evaluate
     * @param CounterState $counterState Current counter state
     * @param GenerationContext $context Variables for substitution
     * @return string The evaluated pattern with all variables substituted
     * 
     * @throws \InvalidArgumentException If pattern contains unsupported variables
     * @throws \RuntimeException If evaluation fails
     */
    public function evaluate(
        PatternTemplate $template,
        CounterState $counterState,
        GenerationContext $context
    ): string;

    /**
     * Validate pattern syntax without evaluation
     * 
     * @param PatternTemplate $template The pattern to validate
     * @return ValidationResult Result containing validation status and any errors
     */
    public function validateSyntax(PatternTemplate $template): ValidationResult;

    /**
     * Get list of variables supported by this evaluator
     * 
     * @return array<string> Array of supported variable names
     */
    public function getSupportedVariables(): array;

    /**
     * Check if evaluator supports a specific variable
     * 
     * @param string $variable Variable name to check
     * @return bool True if variable is supported
     */
    public function supportsVariable(string $variable): bool;

    /**
     * Get evaluator type identifier
     * 
     * @return string Unique identifier for this evaluator type
     */
    public function getType(): string;

    /**
     * Get evaluator description
     * 
     * @return string Human-readable description of evaluator capabilities
     */
    public function getDescription(): string;

    /**
     * Preview pattern evaluation without using counter
     * 
     * Uses mock counter value for preview purposes.
     * 
     * @param PatternTemplate $template The pattern template to preview
     * @param GenerationContext $context Variables for substitution
     * @param int $previewCounter Mock counter value to use
     * @return string The evaluated pattern with preview counter
     * 
     * @throws \InvalidArgumentException If pattern contains unsupported variables
     * @throws \RuntimeException If preview fails
     */
    public function preview(
        PatternTemplate $template,
        GenerationContext $context,
        int $previewCounter = 1
    ): string;
}