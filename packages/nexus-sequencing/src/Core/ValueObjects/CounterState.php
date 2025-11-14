<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\ValueObjects;

use DateTimeInterface;
use DateTimeImmutable;

/**
 * Counter State Value Object
 * 
 * Immutable representation of a sequence counter's current state,
 * including the current counter value and reset tracking information.
 * 
 * This is a pure PHP Value Object with zero external dependencies.
 * 
 * @package Nexus\Sequencing\Core\ValueObjects
 */
readonly class CounterState
{
    public function __construct(
        public int $counter,
        public DateTimeInterface $timestamp,
        public ?DateTimeInterface $lastResetAt = null
    ) {
        $this->validate();
    }

    /**
     * Validate state parameters
     * 
     * @throws \InvalidArgumentException If any parameter is invalid
     */
    private function validate(): void
    {
        if ($this->counter < 0) {
            throw new \InvalidArgumentException('Counter cannot be negative');
        }

        if ($this->lastResetAt !== null && $this->lastResetAt > $this->timestamp) {
            throw new \InvalidArgumentException('Last reset cannot be after current timestamp');
        }
    }

    /**
     * Create initial state with counter at zero
     */
    public static function initial(): self
    {
        return new self(
            counter: 0,
            timestamp: new DateTimeImmutable(),
            lastResetAt: null
        );
    }

    /**
     * Create state with incremented counter
     */
    public function increment(int $stepSize = 1): self
    {
        return new self(
            counter: $this->counter + $stepSize,
            timestamp: new DateTimeImmutable(),
            lastResetAt: $this->lastResetAt
        );
    }

    /**
     * Create state after reset
     */
    public function reset(int $newCounter = 1): self
    {
        $now = new DateTimeImmutable();
        
        return new self(
            counter: $newCounter,
            timestamp: $now,
            lastResetAt: $now
        );
    }

    /**
     * Check if counter has been reset before
     */
    public function hasBeenReset(): bool
    {
        return $this->lastResetAt !== null;
    }

    /**
     * Get the next counter value without modifying state
     */
    public function getNextValue(int $stepSize = 1): int
    {
        return $this->counter + $stepSize;
    }

    /**
     * Convert to array representation
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'counter' => $this->counter,
            'timestamp' => $this->timestamp->format(DateTimeInterface::ATOM),
            'last_reset_at' => $this->lastResetAt?->format(DateTimeInterface::ATOM),
        ];
    }
}