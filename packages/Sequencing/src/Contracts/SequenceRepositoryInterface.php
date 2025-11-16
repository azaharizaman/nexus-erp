<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Contracts;

/**
 * Sequence Repository Interface
 *
 * Defines persistence operations for sequence configurations.
 * This interface defines HOW to save/find sequences, not what they are.
 */
interface SequenceRepositoryInterface
{
    /**
     * Find a sequence by tenant ID and sequence name.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @return SequenceInterface|null The sequence or null if not found
     */
    public function find(string $tenantId, string $sequenceName): ?SequenceInterface;

    /**
     * Find a sequence by ID.
     *
     * @param  int|string  $id  The sequence identifier
     * @return SequenceInterface|null The sequence or null if not found
     */
    public function findById($id): ?SequenceInterface;

    /**
     * Lock a sequence row and increment its counter atomically.
     *
     * This method uses database-level locking (e.g., SELECT FOR UPDATE) to prevent
     * race conditions and ensures atomic counter increment.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @return int The new counter value after increment
     *
     * @throws \Nexus\Sequencing\Exceptions\SequenceNotFoundException
     */
    public function lockAndIncrement(string $tenantId, string $sequenceName): int;

    /**
     * Create a new sequence configuration.
     *
     * @param  array<string, mixed>  $data  Sequence data
     * @return SequenceInterface The created sequence
     */
    public function create(array $data): SequenceInterface;

    /**
     * Update an existing sequence configuration.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @param  array<string, mixed>  $data  Updated data
     * @return bool Success status
     */
    public function update(string $tenantId, string $sequenceName, array $data): bool;

    /**
     * Reset a sequence counter to initial value.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @return void
     *
     * @throws \Nexus\Sequencing\Exceptions\SequenceNotFoundException
     */
    public function reset(string $tenantId, string $sequenceName): void;

    /**
     * Override sequence counter to a specific value.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @param  int  $newValue  The new counter value
     * @return void
     *
     * @throws \Nexus\Sequencing\Exceptions\SequenceNotFoundException
     */
    public function override(string $tenantId, string $sequenceName, int $newValue): void;

    /**
     * Delete a sequence configuration.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @return bool Success status
     */
    public function delete(string $tenantId, string $sequenceName): bool;

    /**
     * Get all sequences for a tenant.
     *
     * @param  string  $tenantId  The tenant identifier
     * @return array<SequenceInterface> Array of sequences
     */
    public function getAllForTenant(string $tenantId): array;

    /**
     * Check if a sequence exists.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @return bool True if exists, false otherwise
     */
    public function exists(string $tenantId, string $sequenceName): bool;
}
