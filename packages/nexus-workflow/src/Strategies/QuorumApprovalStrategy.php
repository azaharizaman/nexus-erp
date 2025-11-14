<?php

declare(strict_types=1);

namespace Nexus\Workflow\Strategies;

use Illuminate\Support\Collection;
use Nexus\Workflow\Contracts\ApprovalStrategyContract;

/**
 * Quorum Approval Strategy
 * 
 * A minimum number (N) of approvers must approve.
 * For example: "At least 3 out of 5 managers must approve"
 * 
 * @package Nexus\Workflow\Strategies
 */
class QuorumApprovalStrategy implements ApprovalStrategyContract
{
    /**
     * {@inheritdoc}
     */
    public function evaluate(Collection $members, Collection $completedTasks, array $config = []): bool
    {
        $quorumCount = $config['quorum_count'] ?? 1;
        
        // Get unique completed approver user IDs
        $completedUserIds = $completedTasks
            ->where('status', 'completed')
            ->pluck('user_id')
            ->unique();

        // Count how many members have approved
        $memberUserIds = $members->pluck('user_id');
        $approvedCount = $completedUserIds->intersect($memberUserIds)->count();

        return $approvedCount >= $quorumCount;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'quorum';
    }

    /**
     * {@inheritdoc}
     */
    public function getProgress(Collection $members, Collection $completedTasks, array $config = []): array
    {
        $quorumCount = $config['quorum_count'] ?? 1;
        $totalCount = $members->count();

        $completedUserIds = $completedTasks
            ->where('status', 'completed')
            ->pluck('user_id')
            ->unique();

        $memberUserIds = $members->pluck('user_id');
        $approvedCount = $completedUserIds->intersect($memberUserIds)->count();

        $pendingApprovers = $memberUserIds
            ->diff($completedUserIds)
            ->values()
            ->toArray();

        return [
            'strategy' => $this->getName(),
            'total_approvers' => $totalCount,
            'required_approvals' => $quorumCount,
            'completed_approvals' => $approvedCount,
            'pending_approvals' => max(0, $quorumCount - $approvedCount),
            'pending_approvers' => $pendingApprovers,
            'is_complete' => $approvedCount >= $quorumCount,
            'progress_percentage' => $quorumCount > 0 ? round(($approvedCount / $quorumCount) * 100, 2) : 0,
        ];
    }
}
