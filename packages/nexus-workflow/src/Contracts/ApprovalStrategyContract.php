<?php

declare(strict_types=1);

namespace Nexus\Workflow\Contracts;

use Illuminate\Support\Collection;

/**
 * Approval Strategy Contract
 * 
 * Defines the interface for approval strategy implementations.
 * Each strategy determines how multiple approvers must approve
 * a task or workflow transition.
 * 
 * @package Nexus\Workflow\Contracts
 */
interface ApprovalStrategyContract
{
    /**
     * Evaluate if approval requirements are met.
     * 
     * @param Collection $members All approver group members
     * @param Collection $completedTasks Tasks that have been completed
     * @param array $config Strategy-specific configuration
     * @return bool True if approval requirements are met
     */
    public function evaluate(Collection $members, Collection $completedTasks, array $config = []): bool;

    /**
     * Get the approval strategy name.
     * 
     * @return string
     */
    public function getName(): string;

    /**
     * Get approval progress information.
     * 
     * @param Collection $members All approver group members
     * @param Collection $completedTasks Tasks that have been completed
     * @param array $config Strategy-specific configuration
     * @return array Progress information
     */
    public function getProgress(Collection $members, Collection $completedTasks, array $config = []): array;
}
