<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Services;

use Nexus\Sequencing\Core\Contracts\CounterRepositoryInterface;
use Nexus\Sequencing\Core\Contracts\PatternEvaluatorInterface;
use Nexus\Sequencing\Core\Contracts\ResetStrategyInterface;
use Nexus\Sequencing\Core\ValueObjects\SequenceConfig;
use Nexus\Sequencing\Core\ValueObjects\GenerationContext;
use Nexus\Sequencing\Core\ValueObjects\GeneratedNumber;
use Nexus\Sequencing\Core\ValueObjects\CounterState;
use Nexus\Sequencing\Core\ValueObjects\PatternTemplate;
use DateTimeInterface;
use DateTimeImmutable;

/**
 * Generation Service
 * 
 * Pure PHP service for atomic serial number generation.
 * Orchestrates counter management, reset logic, and pattern evaluation.
 * 
 * This service has ZERO Laravel dependencies and can be used in any PHP environment.
 * 
 * @package Nexus\Sequencing\Core\Services
 */
class GenerationService
{
    public function __construct(
        private readonly CounterRepositoryInterface $counterRepository,
        private readonly PatternEvaluatorInterface $patternEvaluator,
        private readonly ResetStrategyInterface $resetStrategy
    ) {}

    /**
     * Generate a new serial number atomically
     * 
     * This is the main orchestration method that:
     * 1. Checks if counter needs reset
     * 2. Atomically increments counter 
     * 3. Evaluates pattern with context
     * 4. Returns generated number with metadata
     * 
     * @param SequenceConfig $config The sequence configuration
     * @param GenerationContext $context Variables for pattern evaluation
     * @param DateTimeInterface|null $now Current timestamp (for testing)
     * @return GeneratedNumber The generated number with metadata
     * 
     * @throws \RuntimeException If generation fails
     * @throws \InvalidArgumentException If configuration is invalid
     */
    public function generate(
        SequenceConfig $config,
        GenerationContext $context,
        ?DateTimeInterface $now = null
    ): GeneratedNumber {
        $now = $now ?? new DateTimeImmutable();

        // Validate pattern is supported by evaluator
        $template = new PatternTemplate($config->pattern);
        $validationResult = $this->patternEvaluator->validateSyntax($template);
        
        if (!$validationResult->isValid) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Pattern validation failed: %s',
                    implode(', ', $validationResult->errors)
                )
            );
        }

        // Check if sequence exists, create if not
        if (!$this->counterRepository->exists($config)) {
            $this->counterRepository->saveSequence($config);
        }

        // Get current counter state
        $currentState = $this->counterRepository->getCurrentState($config);

        // Check if counter should reset based on time or count
        if ($this->shouldReset($config, $currentState, $now)) {
            $resetState = $this->performReset($config, $currentState, $now);
            $currentState = $resetState;
        }

        // Atomically increment counter and get generated number
        $generatedNumber = $this->counterRepository->lockAndIncrement($config);

        // Verify the generated number matches our pattern evaluation
        $this->verifyGeneratedNumber($config, $generatedNumber, $context);

        return $generatedNumber;
    }

    /**
     * Preview next number without consuming counter
     * 
     * @param SequenceConfig $config The sequence configuration
     * @param GenerationContext $context Variables for pattern evaluation
     * @param DateTimeInterface|null $now Current timestamp (for reset calculations)
     * @return GeneratedNumber Preview of the next number
     */
    public function preview(
        SequenceConfig $config,
        GenerationContext $context,
        ?DateTimeInterface $now = null
    ): GeneratedNumber {
        $now = $now ?? new DateTimeImmutable();
        
        // Get current state (or initial if sequence doesn't exist)
        $currentState = $this->counterRepository->exists($config)
            ? $this->counterRepository->getCurrentState($config)
            : CounterState::initial();

        // Calculate what the counter would be after potential reset
        if ($this->shouldReset($config, $currentState, $now)) {
            $currentState = CounterState::initial();
        }

        // Calculate next counter value
        $nextCounter = $currentState->getNextValue($config->stepSize);

        // Create preview state
        $previewState = $currentState->increment($config->stepSize);

        // Evaluate pattern with preview counter
        $template = new PatternTemplate($config->pattern);
        $evaluatedValue = $this->patternEvaluator->evaluate($template, $previewState, $context);

        return GeneratedNumber::createAt(
            value: $evaluatedValue,
            counter: $nextCounter,
            generatedAt: $now,
            metadata: [
                'is_preview' => true,
                'would_reset' => $this->shouldReset($config, $currentState, $now),
            ]
        );
    }

    /**
     * Check if counter should reset
     */
    private function shouldReset(
        SequenceConfig $config,
        CounterState $currentState,
        DateTimeInterface $now
    ): bool {
        return $this->resetStrategy->shouldReset(
            $currentState,
            $config->resetPeriod,
            $config->resetLimit,
            $now
        );
    }

    /**
     * Perform counter reset
     */
    private function performReset(
        SequenceConfig $config,
        CounterState $currentState,
        DateTimeInterface $now
    ): CounterState {
        // Determine reset counter value (usually 1, but configurable)
        $resetCounterValue = $config->stepSize; // Start at step_size, not 0

        $newState = $currentState->reset($resetCounterValue);
        
        // Update repository with new state
        return $this->counterRepository->reset($config, $newState);
    }

    /**
     * Verify generated number matches expected pattern evaluation
     * 
     * This is a sanity check to ensure the repository and evaluator are consistent.
     */
    private function verifyGeneratedNumber(
        SequenceConfig $config,
        GeneratedNumber $generated,
        GenerationContext $context
    ): void {
        // Create counter state from generated number
        $counterState = new CounterState(
            counter: $generated->counter,
            timestamp: $generated->generatedAt
        );

        // Re-evaluate pattern with same context
        $template = new PatternTemplate($config->pattern);
        $expectedValue = $this->patternEvaluator->evaluate($template, $counterState, $context);

        if ($expectedValue !== $generated->value) {
            throw new \RuntimeException(
                sprintf(
                    'Generated number verification failed. Expected "%s", got "%s"',
                    $expectedValue,
                    $generated->value
                )
            );
        }
    }

    /**
     * Get remaining count until next reset
     * 
     * @param SequenceConfig $config The sequence configuration
     * @param DateTimeInterface|null $now Current timestamp
     * @return array{count: int|null, time_seconds: int|null} Remaining counts and seconds
     */
    public function getRemainingUntilReset(
        SequenceConfig $config,
        ?DateTimeInterface $now = null
    ): array {
        $now = $now ?? new DateTimeImmutable();
        
        if (!$this->counterRepository->exists($config)) {
            return ['count' => null, 'time_seconds' => null];
        }

        $currentState = $this->counterRepository->getCurrentState($config);

        // Calculate remaining based on count limit
        $remainingCount = null;
        if ($config->resetLimit !== null) {
            $remainingCount = $this->resetStrategy->remainingUntilCountReset(
                $currentState,
                $config->resetLimit
            );
        }

        // Calculate remaining time until next reset
        $remainingTime = null;
        if ($config->resetPeriod->isTimeBased()) {
            $remainingTime = $this->resetStrategy->remainingUntilTimeReset(
                $config->resetPeriod,
                $now
            );
        }

        return [
            'count' => $remainingCount,
            'time_seconds' => $remainingTime,
        ];
    }
}