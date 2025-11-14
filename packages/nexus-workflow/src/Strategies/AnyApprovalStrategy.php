<?php

declare(strict_types=1);

namespace Nexus\Workflow\Strategies;

use Illuminate\Support\Collection;
use Nexus\Workflow\Contracts\ApprovalStrategyContract;

/**
 * Any Approval Strategy
 * 
 * Any single approver can approve (first approval wins).
 * Useful for scenarios where any authorized person can proceed.
 * 
 * @package Nexus\Workflow\Strategies
 */
class AnyApprovalStrategy implements ApprovalStrategyContract
{
    /**
     * {@inheritdoc}
     */
    public function evaluate(Collection $members, Collection $completedTasks, array $config = []): bool
    {
        if ($members->isEmpty()) {
            return false;
        }

        // Get completed approver user IDs
        $completedUserIds = $completedTasks
            ->where('status', 'completed')
            ->pluck('user_id')
            ->unique();

        // Check if any member has approved
        $memberUserIds = $members->pluck('user_id');
        
        return $completedUserIds->intersect($memberUserIds)->isNotEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'any';
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
            ->unique();

        $memberUserIds = $members->pluck('user_id');
        $approvedMembers = $completedUserIds->intersect($memberUserIds);
        $hasApproval = $approvedMembers->isNotEmpty();

        return [
            'strategy' => $this->getName(),
            'total_approvers' => $totalCount,
            'required_approvals' => 1,
            'completed_approvals' => $hasApproval ? 1 : 0,
            'approved_by' => $hasApproval ? $approvedMembers->first() : null,
            'is_complete' => $hasApproval,
            'progress_percentage' => $hasApproval ? 100 : 0,
        ];
    }
}
