<?php

declare(strict_types=1);

namespace Nexus\Workflow\Strategies;

use Illuminate\Support\Collection;
use Nexus\Workflow\Contracts\ApprovalStrategyContract;

/**
 * Parallel Approval Strategy
 * 
 * All approvers must approve (unanimous approval).
 * Order doesn't matter - all members must complete their approval.
 * 
 * @package Nexus\Workflow\Strategies
 */
class ParallelApprovalStrategy implements ApprovalStrategyContract
{
    /**
     * {@inheritdoc}
     */
    public function evaluate(Collection $members, Collection $completedTasks, array $config = []): bool
    {
        $totalMembers = $members->count();
        
        if ($totalMembers === 0) {
            return false;
        }

        // Get completed approver user IDs
        $completedUserIds = $completedTasks
            ->where('status', 'completed')
            ->pluck('user_id')
            ->unique()
            ->toArray();

        // Check if all members have approved
        $memberUserIds = $members->pluck('user_id')->toArray();
        
        foreach ($memberUserIds as $userId) {
            if (!in_array($userId, $completedUserIds)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'parallel';
    }

    /**
     * {@inheritdoc}
     */
    public function getProgress(Collection $members, Collection $completedTasks, array $config = []): array
    {
        $totalCount = $members->count();
        $completedUserIds = $completedTasks
            ->where('status', 'completed')
            ->pluck('user_id')
            ->unique()
            ->toArray();

        $memberUserIds = $members->pluck('user_id')->toArray();
        $completedCount = 0;
        $pendingApprovers = [];

        foreach ($memberUserIds as $userId) {
            if (in_array($userId, $completedUserIds)) {
                $completedCount++;
            } else {
                $pendingApprovers[] = $userId;
            }
        }

        return [
            'strategy' => $this->getName(),
            'total_approvers' => $totalCount,
            'completed_approvals' => $completedCount,
            'pending_approvals' => $totalCount - $completedCount,
            'pending_approvers' => $pendingApprovers,
            'is_complete' => $completedCount === $totalCount,
            'progress_percentage' => $totalCount > 0 ? round(($completedCount / $totalCount) * 100, 2) : 0,
        ];
    }
}
