<?php

declare(strict_types=1);

namespace Nexus\Workflow\Strategies;

use Illuminate\Support\Collection;
use Nexus\Workflow\Contracts\ApprovalStrategyContract;

/**
 * Sequential Approval Strategy
 * 
 * Approvers must approve in a specific sequence/order.
 * Each approver can only approve after all previous approvers
 * have completed their approval.
 * 
 * @package Nexus\Workflow\Strategies
 */
class SequentialApprovalStrategy implements ApprovalStrategyContract
{
    /**
     * {@inheritdoc}
     */
    public function evaluate(Collection $members, Collection $completedTasks, array $config = []): bool
    {
        // Order members by sequence
        $orderedMembers = $members->sortBy('sequence')->values();
        
        // Get completed approver user IDs
        $completedUserIds = $completedTasks
            ->where('status', 'completed')
            ->pluck('user_id')
            ->toArray();

        // Check if all members have approved
        foreach ($orderedMembers as $index => $member) {
            if (!in_array($member->user_id, $completedUserIds)) {
                // This member hasn't approved yet
                // All previous members must have approved
                for ($i = 0; $i < $index; $i++) {
                    if (!in_array($orderedMembers[$i]->user_id, $completedUserIds)) {
                        // A previous member hasn't approved - requirements not met
                        return false;
                    }
                }
                // This is the next member to approve - requirements not met yet
                return false;
            }
        }

        // All members have approved in sequence
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'sequential';
    }

    /**
     * {@inheritdoc}
     */
    public function getProgress(Collection $members, Collection $completedTasks, array $config = []): array
    {
        $orderedMembers = $members->sortBy('sequence')->values();
        $completedUserIds = $completedTasks
            ->where('status', 'completed')
            ->pluck('user_id')
            ->toArray();

        $totalCount = $orderedMembers->count();
        $completedCount = 0;
        $nextApprover = null;

        foreach ($orderedMembers as $member) {
            if (in_array($member->user_id, $completedUserIds)) {
                $completedCount++;
            } else {
                $nextApprover = $member->user_id;
                break;
            }
        }

        return [
            'strategy' => $this->getName(),
            'total_approvers' => $totalCount,
            'completed_approvals' => $completedCount,
            'pending_approvals' => $totalCount - $completedCount,
            'next_approver' => $nextApprover,
            'is_complete' => $completedCount === $totalCount,
            'progress_percentage' => $totalCount > 0 ? round(($completedCount / $totalCount) * 100, 2) : 0,
        ];
    }
}
