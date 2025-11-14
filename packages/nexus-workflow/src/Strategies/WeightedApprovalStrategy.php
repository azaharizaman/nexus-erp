<?php

declare(strict_types=1);

namespace Nexus\Workflow\Strategies;

use Illuminate\Support\Collection;
use Nexus\Workflow\Contracts\ApprovalStrategyContract;

/**
 * Weighted Approval Strategy
 * 
 * Approvals are weighted based on approver hierarchy/role.
 * Approval is complete when the sum of completed approver weights
 * meets or exceeds the minimum required weight.
 * 
 * Example: CEO weight=100, CFO weight=50, Manager weight=25
 * If min_weight=75, then CEO alone OR CFO+Manager can approve.
 * 
 * @package Nexus\Workflow\Strategies
 */
class WeightedApprovalStrategy implements ApprovalStrategyContract
{
    /**
     * {@inheritdoc}
     */
    public function evaluate(Collection $members, Collection $completedTasks, array $config = []): bool
    {
        $minWeight = $config['min_weight'] ?? 0;
        
        if ($minWeight <= 0) {
            return false;
        }

        // Get completed approver user IDs
        $completedUserIds = $completedTasks
            ->where('status', 'completed')
            ->pluck('user_id')
            ->unique()
            ->toArray();

        // Calculate total weight of completed approvals
        $totalWeight = $members
            ->filter(fn($member) => in_array($member->user_id, $completedUserIds))
            ->sum('weight');

        return $totalWeight >= $minWeight;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'weighted';
    }

    /**
     * {@inheritdoc}
     */
    public function getProgress(Collection $members, Collection $completedTasks, array $config = []): array
    {
        $minWeight = $config['min_weight'] ?? 0;
        $totalPossibleWeight = $members->sum('weight');

        $completedUserIds = $completedTasks
            ->where('status', 'completed')
            ->pluck('user_id')
            ->unique()
            ->toArray();

        $completedWeight = $members
            ->filter(fn($member) => in_array($member->user_id, $completedUserIds))
            ->sum('weight');

        $completedApprovers = $members
            ->filter(fn($member) => in_array($member->user_id, $completedUserIds))
            ->map(fn($member) => [
                'user_id' => $member->user_id,
                'weight' => $member->weight,
            ])
            ->values()
            ->toArray();

        $pendingApprovers = $members
            ->filter(fn($member) => !in_array($member->user_id, $completedUserIds))
            ->map(fn($member) => [
                'user_id' => $member->user_id,
                'weight' => $member->weight,
            ])
            ->values()
            ->toArray();

        return [
            'strategy' => $this->getName(),
            'total_approvers' => $members->count(),
            'required_weight' => $minWeight,
            'completed_weight' => $completedWeight,
            'remaining_weight' => max(0, $minWeight - $completedWeight),
            'total_possible_weight' => $totalPossibleWeight,
            'completed_approvers' => $completedApprovers,
            'pending_approvers' => $pendingApprovers,
            'is_complete' => $completedWeight >= $minWeight,
            'progress_percentage' => $minWeight > 0 ? round(($completedWeight / $minWeight) * 100, 2) : 0,
        ];
    }
}
