<?php

declare(strict_types=1);

namespace App\Repositories\Sequencing;

use App\Models\SerialNumberLog;
use App\Models\Sequence;
use Nexus\Sequencing\Contracts\SerialNumberLogInterface;
use Nexus\Sequencing\Contracts\SerialNumberLogRepositoryInterface;

/**
 * Serial Number Log Repository (Atomy Implementation)
 *
 * Concrete implementation of SerialNumberLogRepositoryInterface using Eloquent.
 * This is the data access layer for serial number log persistence.
 */
class SerialNumberLogRepository implements SerialNumberLogRepositoryInterface
{
    /**
     * Create a new log entry for a generated serial number.
     *
     * @param  array<string, mixed>  $data
     * @return SerialNumberLogInterface
     */
    public function create(array $data): SerialNumberLogInterface
    {
        return SerialNumberLog::create($data);
    }

    /**
     * Find log entries by sequence.
     *
     * @param  string  $tenantId
     * @param  string  $sequenceName
     * @param  int  $limit
     * @return array<SerialNumberLogInterface>
     */
    public function findBySequence(string $tenantId, string $sequenceName, int $limit = 100): array
    {
        $sequence = Sequence::where('tenant_id', $tenantId)
            ->where('sequence_name', $sequenceName)
            ->first();

        if (!$sequence) {
            return [];
        }

        return SerialNumberLog::where('sequence_id', $sequence->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * Find a specific log entry by generated number.
     *
     * @param  string  $generatedNumber
     * @return SerialNumberLogInterface|null
     */
    public function findByGeneratedNumber(string $generatedNumber): ?SerialNumberLogInterface
    {
        return SerialNumberLog::where('generated_number', $generatedNumber)
            ->first();
    }

    /**
     * Get the last generated number for a sequence.
     *
     * @param  string  $tenantId
     * @param  string  $sequenceName
     * @return SerialNumberLogInterface|null
     */
    public function getLastGenerated(string $tenantId, string $sequenceName): ?SerialNumberLogInterface
    {
        $sequence = Sequence::where('tenant_id', $tenantId)
            ->where('sequence_name', $sequenceName)
            ->first();

        if (!$sequence) {
            return null;
        }

        return SerialNumberLog::where('sequence_id', $sequence->id)
            ->where('action_type', 'generated')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get audit history with filtering.
     *
     * @param  array<string, mixed>  $filters
     * @return array<SerialNumberLogInterface>
     */
    public function getHistory(array $filters = []): array
    {
        $query = SerialNumberLog::query();

        if (isset($filters['sequence_id'])) {
            $query->where('sequence_id', $filters['sequence_id']);
        }

        if (isset($filters['action_type'])) {
            $query->where('action_type', $filters['action_type']);
        }

        if (isset($filters['causer_id'])) {
            $query->where('causer_id', $filters['causer_id']);
        }

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        $limit = $filters['limit'] ?? 100;

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }
}
