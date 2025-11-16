<?php

declare(strict_types=1);

namespace App\Repositories\Sequencing;

use App\Models\Sequence;
use Illuminate\Support\Facades\DB;
use Nexus\Sequencing\Contracts\SequenceInterface;
use Nexus\Sequencing\Contracts\SequenceRepositoryInterface;
use Nexus\Sequencing\Exceptions\SequenceNotFoundException;

/**
 * Sequence Repository (Atomy Implementation)
 *
 * Concrete implementation of SequenceRepositoryInterface using Eloquent.
 * This is the data access layer for sequence configuration persistence.
 */
class SequenceRepository implements SequenceRepositoryInterface
{
    /**
     * Find a sequence by tenant ID and sequence name.
     *
     * @param  string  $tenantId
     * @param  string  $sequenceName
     * @return SequenceInterface|null
     */
    public function find(string $tenantId, string $sequenceName): ?SequenceInterface
    {
        return Sequence::where('tenant_id', $tenantId)
            ->where('sequence_name', $sequenceName)
            ->first();
    }

    /**
     * Find a sequence by ID.
     *
     * @param  int|string  $id
     * @return SequenceInterface|null
     */
    public function findById($id): ?SequenceInterface
    {
        return Sequence::find($id);
    }

    /**
     * Lock a sequence row and increment its counter atomically.
     *
     * Uses SELECT FOR UPDATE to prevent race conditions and ensures
     * atomic counter increment with database-level locking.
     *
     * @param  string  $tenantId
     * @param  string  $sequenceName
     * @return int The new counter value after increment
     *
     * @throws \Nexus\Sequencing\Exceptions\SequenceNotFoundException
     */
    public function lockAndIncrement(string $tenantId, string $sequenceName): int
    {
        return DB::transaction(function () use ($tenantId, $sequenceName) {
            // Lock the row using SELECT FOR UPDATE
            $sequence = Sequence::where('tenant_id', $tenantId)
                ->where('sequence_name', $sequenceName)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                throw SequenceNotFoundException::create($tenantId, $sequenceName);
            }

            // Check if reset is needed
            if ($sequence->shouldReset()) {
                $sequence->current_value = 0;
                $sequence->last_reset_at = now();
            }

            // Increment counter by step size
            $sequence->current_value += $sequence->step_size;
            $sequence->version += 1;
            $sequence->save();

            return $sequence->current_value;
        });
    }

    /**
     * Create a new sequence configuration.
     *
     * @param  array<string, mixed>  $data
     * @return SequenceInterface
     */
    public function create(array $data): SequenceInterface
    {
        $defaults = [
            'padding' => 5,
            'step_size' => 1,
            'current_value' => 0,
            'version' => 0,
            'reset_limit' => null,
            'metadata' => null,
        ];

        return Sequence::create(array_merge($defaults, $data));
    }

    /**
     * Update an existing sequence configuration.
     *
     * @param  string  $tenantId
     * @param  string  $sequenceName
     * @param  array<string, mixed>  $data
     * @return bool
     */
    public function update(string $tenantId, string $sequenceName, array $data): bool
    {
        $sequence = $this->find($tenantId, $sequenceName);

        if (!$sequence || !($sequence instanceof Sequence)) {
            return false;
        }

        return $sequence->update($data);
    }

    /**
     * Reset a sequence counter to initial value.
     *
     * @param  string  $tenantId
     * @param  string  $sequenceName
     * @return void
     *
     * @throws \Nexus\Sequencing\Exceptions\SequenceNotFoundException
     */
    public function reset(string $tenantId, string $sequenceName): void
    {
        $sequence = $this->find($tenantId, $sequenceName);

        if (!$sequence || !($sequence instanceof Sequence)) {
            throw SequenceNotFoundException::create($tenantId, $sequenceName);
        }

        $sequence->update([
            'current_value' => 0,
            'last_reset_at' => now(),
        ]);
    }

    /**
     * Override sequence counter to a specific value.
     *
     * @param  string  $tenantId
     * @param  string  $sequenceName
     * @param  int  $newValue
     * @return void
     *
     * @throws \Nexus\Sequencing\Exceptions\SequenceNotFoundException
     */
    public function override(string $tenantId, string $sequenceName, int $newValue): void
    {
        $sequence = $this->find($tenantId, $sequenceName);

        if (!$sequence || !($sequence instanceof Sequence)) {
            throw SequenceNotFoundException::create($tenantId, $sequenceName);
        }

        $sequence->update([
            'current_value' => $newValue,
        ]);
    }

    /**
     * Delete a sequence configuration.
     *
     * @param  string  $tenantId
     * @param  string  $sequenceName
     * @return bool
     */
    public function delete(string $tenantId, string $sequenceName): bool
    {
        $sequence = $this->find($tenantId, $sequenceName);

        if (!$sequence || !($sequence instanceof Sequence)) {
            return false;
        }

        return $sequence->delete();
    }

    /**
     * Get all sequences for a tenant.
     *
     * @param  string  $tenantId
     * @return array<SequenceInterface>
     */
    public function getAllForTenant(string $tenantId): array
    {
        return Sequence::where('tenant_id', $tenantId)
            ->orderBy('sequence_name')
            ->get()
            ->all();
    }

    /**
     * Check if a sequence exists.
     *
     * @param  string  $tenantId
     * @param  string  $sequenceName
     * @return bool
     */
    public function exists(string $tenantId, string $sequenceName): bool
    {
        return Sequence::where('tenant_id', $tenantId)
            ->where('sequence_name', $sequenceName)
            ->exists();
    }
}
