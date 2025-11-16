<?php

declare(strict_types=1);

namespace Nexus\Workflow\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Nexus\Workflow\Models\ApproverGroup;
use Nexus\Workflow\Models\ApproverGroupMember;
use InvalidArgumentException;

/**
 * Approver Group Service
 * 
 * Manages approver groups and their members for multi-approver workflows.
 * Supports 5 approval strategies:
 * - Sequential: Approvers must approve in order
 * - Parallel: All approvers must approve
 * - Quorum: N of M approvers must approve
 * - Any: First approver wins
 * - Weighted: Based on approver weights/hierarchy
 * 
 * @package Nexus\Workflow\Services
 */
class ApproverGroupService
{
    /**
     * Valid approval strategies.
     */
    public const STRATEGIES = [
        ApproverGroup::STRATEGY_SEQUENTIAL,
        ApproverGroup::STRATEGY_PARALLEL,
        ApproverGroup::STRATEGY_QUORUM,
        ApproverGroup::STRATEGY_ANY,
        ApproverGroup::STRATEGY_WEIGHTED,
    ];

    /**
     * Create a new approver group.
     * 
     * @param array $data Group data
     * @return ApproverGroup
     * @throws InvalidArgumentException
     */
    public function create(array $data): ApproverGroup
    {
        $this->validateGroupData($data);

        return DB::transaction(function () use ($data) {
            return ApproverGroup::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'strategy' => $data['strategy'],
                'quorum_count' => $data['quorum_count'] ?? null,
                'min_weight' => $data['min_weight'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);
        });
    }

    /**
     * Update an approver group.
     * 
     * @param string $groupId Group ID
     * @param array $data Update data
     * @return ApproverGroup
     * @throws InvalidArgumentException
     */
    public function update(string $groupId, array $data): ApproverGroup
    {
        $group = $this->findOrFail($groupId);
        
        // Validate if strategy is being changed
        if (isset($data['strategy'])) {
            $this->validateStrategy($data['strategy'], $data);
        }

        return DB::transaction(function () use ($group, $data) {
            $group->update(array_filter([
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'strategy' => $data['strategy'] ?? null,
                'quorum_count' => $data['quorum_count'] ?? null,
                'min_weight' => $data['min_weight'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ], fn($value) => $value !== null));

            return $group->fresh();
        });
    }

    /**
     * Delete an approver group.
     * 
     * @param string $groupId Group ID
     * @return bool
     */
    public function delete(string $groupId): bool
    {
        $group = $this->findOrFail($groupId);

        return DB::transaction(function () use ($group) {
            // Delete all members first
            $group->members()->delete();
            
            return $group->delete();
        });
    }

    /**
     * Get approver group by ID.
     * 
     * @param string $groupId Group ID
     * @return ApproverGroup|null
     */
    public function find(string $groupId): ?ApproverGroup
    {
        return ApproverGroup::with('members')->find($groupId);
    }

    /**
     * Get approver group by ID or fail.
     * 
     * @param string $groupId Group ID
     * @return ApproverGroup
     * @throws InvalidArgumentException
     */
    public function findOrFail(string $groupId): ApproverGroup
    {
        $group = $this->find($groupId);

        if (!$group) {
            throw new InvalidArgumentException("Approver group not found: {$groupId}");
        }

        return $group;
    }

    /**
     * Get all approver groups.
     * 
     * @return Collection
     */
    public function all(): Collection
    {
        return ApproverGroup::with('members')->get();
    }

    /**
     * Add member to approver group.
     * 
     * @param string $groupId Group ID
     * @param string $userId User ID
     * @param array $options Additional options (sequence, weight)
     * @return ApproverGroupMember
     * @throws InvalidArgumentException
     */
    public function addMember(string $groupId, string $userId, array $options = []): ApproverGroupMember
    {
        $group = $this->findOrFail($groupId);
        
        // Check if member already exists
        $existing = ApproverGroupMember::where('approver_group_id', $groupId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            throw new InvalidArgumentException("User {$userId} is already a member of this group");
        }

        // Validate sequence for sequential strategy
        if ($group->strategy === ApproverGroup::STRATEGY_SEQUENTIAL) {
            if (!isset($options['sequence'])) {
                throw new InvalidArgumentException('Sequence is required for sequential approval groups');
            }
        }

        // Validate weight for weighted strategy
        if ($group->strategy === ApproverGroup::STRATEGY_WEIGHTED) {
            if (!isset($options['weight'])) {
                throw new InvalidArgumentException('Weight is required for weighted approval groups');
            }
        }

        return DB::transaction(function () use ($group, $userId, $options) {
            return ApproverGroupMember::create([
                'approver_group_id' => $group->id,
                'user_id' => $userId,
                'sequence' => $options['sequence'] ?? null,
                'weight' => $options['weight'] ?? null,
                'metadata' => $options['metadata'] ?? null,
            ]);
        });
    }

    /**
     * Remove member from approver group.
     * 
     * @param string $groupId Group ID
     * @param string $userId User ID
     * @return bool
     * @throws InvalidArgumentException
     */
    public function removeMember(string $groupId, string $userId): bool
    {
        $this->findOrFail($groupId);

        $member = ApproverGroupMember::where('approver_group_id', $groupId)
            ->where('user_id', $userId)
            ->first();

        if (!$member) {
            throw new InvalidArgumentException("User {$userId} is not a member of this group");
        }

        return DB::transaction(function () use ($member) {
            return $member->delete();
        });
    }

    /**
     * Update member details.
     * 
     * @param string $groupId Group ID
     * @param string $userId User ID
     * @param array $data Update data
     * @return ApproverGroupMember
     * @throws InvalidArgumentException
     */
    public function updateMember(string $groupId, string $userId, array $data): ApproverGroupMember
    {
        $group = $this->findOrFail($groupId);

        $member = ApproverGroupMember::where('approver_group_id', $groupId)
            ->where('user_id', $userId)
            ->first();

        if (!$member) {
            throw new InvalidArgumentException("User {$userId} is not a member of this group");
        }

        return DB::transaction(function () use ($member, $data) {
            $member->update(array_filter([
                'sequence' => $data['sequence'] ?? null,
                'weight' => $data['weight'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ], fn($value) => $value !== null));

            return $member->fresh();
        });
    }

    /**
     * Get members of an approver group.
     * 
     * @param string $groupId Group ID
     * @param bool $ordered Whether to order by sequence/weight
     * @return Collection
     */
    public function getMembers(string $groupId, bool $ordered = true): Collection
    {
        $this->findOrFail($groupId);

        $query = ApproverGroupMember::where('approver_group_id', $groupId);

        if ($ordered) {
            $query->orderBy('sequence')->orderBy('weight', 'desc');
        }

        return $query->get();
    }

    /**
     * Validate group data.
     * 
     * @param array $data Group data
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateGroupData(array $data): void
    {
        if (empty($data['name'])) {
            throw new InvalidArgumentException('Group name is required');
        }

        if (empty($data['strategy'])) {
            throw new InvalidArgumentException('Approval strategy is required');
        }

        $this->validateStrategy($data['strategy'], $data);
    }

    /**
     * Validate approval strategy configuration.
     * 
     * @param string $strategy Strategy type
     * @param array $data Strategy configuration
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateStrategy(string $strategy, array $data): void
    {
        if (!in_array($strategy, self::STRATEGIES)) {
            throw new InvalidArgumentException(
                "Invalid approval strategy: {$strategy}. Must be one of: " . implode(', ', self::STRATEGIES)
            );
        }

        // Validate strategy-specific requirements
        switch ($strategy) {
            case ApproverGroup::STRATEGY_QUORUM:
                if (empty($data['quorum_count'])) {
                    throw new InvalidArgumentException('Quorum count is required for quorum strategy');
                }
                if ($data['quorum_count'] < 1) {
                    throw new InvalidArgumentException('Quorum count must be at least 1');
                }
                break;

            case ApproverGroup::STRATEGY_WEIGHTED:
                if (empty($data['min_weight'])) {
                    throw new InvalidArgumentException('Minimum weight is required for weighted strategy');
                }
                if ($data['min_weight'] < 0) {
                    throw new InvalidArgumentException('Minimum weight must be non-negative');
                }
                break;
        }
    }

    /**
     * Clone an approver group.
     * 
     * @param string $groupId Group ID to clone
     * @param string $newName Name for the new group
     * @return ApproverGroup
     */
    public function clone(string $groupId, string $newName): ApproverGroup
    {
        $original = $this->findOrFail($groupId);

        return DB::transaction(function () use ($original, $newName) {
            // Create new group
            $newGroup = ApproverGroup::create([
                'name' => $newName,
                'description' => $original->description,
                'strategy' => $original->strategy,
                'quorum_count' => $original->quorum_count,
                'min_weight' => $original->min_weight,
                'metadata' => $original->metadata,
            ]);

            // Clone all members
            foreach ($original->members as $member) {
                ApproverGroupMember::create([
                    'approver_group_id' => $newGroup->id,
                    'user_id' => $member->user_id,
                    'sequence' => $member->sequence,
                    'weight' => $member->weight,
                    'metadata' => $member->metadata,
                ]);
            }

            return $newGroup->fresh(['members']);
        });
    }

    /**
     * Get groups by strategy type.
     * 
     * @param string $strategy Strategy type
     * @return Collection
     */
    public function getByStrategy(string $strategy): Collection
    {
        if (!in_array($strategy, self::STRATEGIES)) {
            throw new InvalidArgumentException("Invalid strategy: {$strategy}");
        }

        return ApproverGroup::where('strategy', $strategy)
            ->with('members')
            ->get();
    }
}
