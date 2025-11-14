<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\ValueObjects;

/**
 * Sequence Configuration Value Object
 * 
 * Immutable configuration for a sequence containing all parameters needed
 * for counter generation, pattern evaluation, and reset logic.
 * 
 * This is a pure PHP Value Object with zero external dependencies.
 * 
 * @package Nexus\Sequencing\Core\ValueObjects
 */
readonly class SequenceConfig
{
    public function __construct(
        public string $scopeIdentifier,
        public string $sequenceName,
        public string $pattern,
        public ResetPeriod $resetPeriod,
        public int $padding = 4,
        public int $stepSize = 1,
        public ?int $resetLimit = null,
        public string $evaluatorType = 'regex'
    ) {
        $this->validate();
    }

    /**
     * Validate configuration parameters
     * 
     * @throws \InvalidArgumentException If any parameter is invalid
     */
    private function validate(): void
    {
        if (empty($this->scopeIdentifier)) {
            throw new \InvalidArgumentException('Scope identifier cannot be empty');
        }

        if (empty($this->sequenceName)) {
            throw new \InvalidArgumentException('Sequence name cannot be empty');
        }

        if (empty($this->pattern)) {
            throw new \InvalidArgumentException('Pattern cannot be empty');
        }

        if ($this->padding < 1 || $this->padding > 20) {
            throw new \InvalidArgumentException('Padding must be between 1 and 20');
        }

        if ($this->stepSize < 1) {
            throw new \InvalidArgumentException('Step size must be greater than 0');
        }

        if ($this->resetLimit !== null && $this->resetLimit < 1) {
            throw new \InvalidArgumentException('Reset limit must be greater than 0');
        }
    }

    /**
     * Create configuration with different scope
     */
    public function withScope(string $scopeIdentifier): self
    {
        return new self(
            $scopeIdentifier,
            $this->sequenceName,
            $this->pattern,
            $this->resetPeriod,
            $this->padding,
            $this->stepSize,
            $this->resetLimit,
            $this->evaluatorType
        );
    }

    /**
     * Create configuration with different pattern
     */
    public function withPattern(string $pattern): self
    {
        return new self(
            $this->scopeIdentifier,
            $this->sequenceName,
            $pattern,
            $this->resetPeriod,
            $this->padding,
            $this->stepSize,
            $this->resetLimit,
            $this->evaluatorType
        );
    }

    /**
     * Get unique identifier for this sequence configuration
     */
    public function getUniqueKey(): string
    {
        return sprintf(
            '%s:%s:%s',
            $this->scopeIdentifier,
            $this->sequenceName,
            $this->resetPeriod->value
        );
    }

    /**
     * Convert to array representation
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'scope_identifier' => $this->scopeIdentifier,
            'sequence_name' => $this->sequenceName,
            'pattern' => $this->pattern,
            'reset_period' => $this->resetPeriod->value,
            'padding' => $this->padding,
            'step_size' => $this->stepSize,
            'reset_limit' => $this->resetLimit,
            'evaluator_type' => $this->evaluatorType,
        ];
    }
}