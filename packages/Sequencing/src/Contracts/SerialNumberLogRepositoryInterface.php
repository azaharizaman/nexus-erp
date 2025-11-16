<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Contracts;

/**
 * Serial Number Log Repository Interface
 *
 * Defines persistence operations for serial number generation logs.
 */
interface SerialNumberLogRepositoryInterface
{
    /**
     * Create a new log entry for a generated serial number.
     *
     * @param  array<string, mixed>  $data  Log entry data
     * @return SerialNumberLogInterface The created log entry
     */
    public function create(array $data): SerialNumberLogInterface;

    /**
     * Find log entries by sequence.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @param  int  $limit  Maximum number of entries to return
     * @return array<SerialNumberLogInterface> Array of log entries
     */
    public function findBySequence(string $tenantId, string $sequenceName, int $limit = 100): array;

    /**
     * Find a specific log entry by generated number.
     *
     * @param  string  $generatedNumber  The generated serial number
     * @return SerialNumberLogInterface|null The log entry or null if not found
     */
    public function findByGeneratedNumber(string $generatedNumber): ?SerialNumberLogInterface;

    /**
     * Get the last generated number for a sequence.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @return SerialNumberLogInterface|null The last log entry or null if none exists
     */
    public function getLastGenerated(string $tenantId, string $sequenceName): ?SerialNumberLogInterface;

    /**
     * Get audit history with filtering.
     *
     * @param  array<string, mixed>  $filters  Filter criteria
     * @return array<SerialNumberLogInterface> Array of log entries
     */
    public function getHistory(array $filters = []): array;
}
