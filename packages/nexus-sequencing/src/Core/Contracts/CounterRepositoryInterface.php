<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Contracts;

use Nexus\Sequencing\Core\ValueObjects\SequenceConfig;
use Nexus\Sequencing\Core\ValueObjects\CounterState;
use Nexus\Sequencing\Core\ValueObjects\GeneratedNumber;

/**
 * Counter Repository Interface
 * 
 * Contract for persistent storage and atomic manipulation of sequence counters.
 * Implementations must guarantee atomic operations and prevent race conditions.
 * 
 * This interface is framework-agnostic and can be implemented using any
 * database technology or storage mechanism.
 * 
 * @package Nexus\Sequencing\Core\Contracts
 */
interface CounterRepositoryInterface
{
    /**
     * Find current counter state for a sequence
     * 
     * @param SequenceConfig $config The sequence configuration
     * @return CounterState|null Current state or null if sequence doesn't exist
     */
    public function find(SequenceConfig $config): ?CounterState;

    /**
     * Atomically lock and increment counter
     * 
     * This operation MUST be atomic to prevent race conditions in concurrent environments.
     * Should use database-level locking (SELECT FOR UPDATE) or equivalent mechanism.
     * 
     * @param SequenceConfig $config The sequence configuration
     * @return GeneratedNumber The generated number with updated counter
     * 
     * @throws \RuntimeException If locking fails or counter cannot be incremented
     */
    public function lockAndIncrement(SequenceConfig $config): GeneratedNumber;

    /**
     * Reset counter to specified state
     * 
     * @param SequenceConfig $config The sequence configuration
     * @param CounterState $newState The new state after reset
     * @return CounterState The updated counter state
     * 
     * @throws \RuntimeException If reset operation fails
     */
    public function reset(SequenceConfig $config, CounterState $newState): CounterState;

    /**
     * Get current counter state without locking
     * 
     * Use this for preview operations or status checks.
     * Does not modify the counter value.
     * 
     * @param SequenceConfig $config The sequence configuration
     * @return CounterState Current state (may be initial state if sequence doesn't exist)
     */
    public function getCurrentState(SequenceConfig $config): CounterState;

    /**
     * Create or update sequence configuration
     * 
     * @param SequenceConfig $config The sequence configuration to store
     * @return bool True if operation succeeded
     * 
     * @throws \InvalidArgumentException If configuration is invalid
     * @throws \RuntimeException If storage operation fails
     */
    public function saveSequence(SequenceConfig $config): bool;

    /**
     * Delete sequence and its counter state
     * 
     * WARNING: This operation cannot be undone and will lose all counter history.
     * 
     * @param SequenceConfig $config The sequence to delete
     * @return bool True if sequence was deleted, false if it didn't exist
     * 
     * @throws \RuntimeException If deletion fails
     */
    public function deleteSequence(SequenceConfig $config): bool;

    /**
     * Check if sequence exists
     * 
     * @param SequenceConfig $config The sequence configuration
     * @return bool True if sequence exists
     */
    public function exists(SequenceConfig $config): bool;

    /**
     * Get all sequences for a scope
     * 
     * @param string $scopeIdentifier The scope to search
     * @return array<SequenceConfig> Array of sequence configurations
     */
    public function findByScope(string $scopeIdentifier): array;
}