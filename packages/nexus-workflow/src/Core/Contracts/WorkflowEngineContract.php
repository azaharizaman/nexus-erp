<?php

declare(strict_types=1);

namespace Nexus\Workflow\Core\Contracts;

use Nexus\Workflow\Core\DTOs\TransitionResult;
use Nexus\Workflow\Core\DTOs\WorkflowInstance;

/**
 * Core workflow engine contract - framework-agnostic
 * 
 * Defines the contract for the workflow state machine engine.
 * Implementations must handle state transitions, validation, and history tracking.
 */
interface WorkflowEngineContract
{
    /**
     * Check if a transition is allowed from the current state
     *
     * @param WorkflowInstance $instance Current workflow instance
     * @param string $transitionName Name of the transition to check
     * @param array<string, mixed> $context Optional context for guard evaluation
     * @return bool True if transition is allowed
     */
    public function canTransition(
        WorkflowInstance $instance,
        string $transitionName,
        array $context = []
    ): bool;

    /**
     * Apply a transition to the workflow instance
     *
     * @param WorkflowInstance $instance Current workflow instance
     * @param string $transitionName Name of the transition to apply
     * @param array<string, mixed> $context Optional context for guards/hooks
     * @return TransitionResult Result of the transition attempt
     * @throws \InvalidArgumentException If transition doesn't exist
     * @throws \RuntimeException If transition is not allowed
     */
    public function applyTransition(
        WorkflowInstance $instance,
        string $transitionName,
        array $context = []
    ): TransitionResult;

    /**
     * Get all available transitions from the current state
     *
     * @param WorkflowInstance $instance Current workflow instance
     * @param array<string, mixed> $context Optional context for guard evaluation
     * @return array<string> Array of transition names
     */
    public function getAvailableTransitions(
        WorkflowInstance $instance,
        array $context = []
    ): array;

    /**
     * Validate workflow definition structure
     *
     * @param array<string, mixed> $definition Workflow definition array
     * @return bool True if definition is valid
     * @throws \InvalidArgumentException If definition is invalid
     */
    public function validateDefinition(array $definition): bool;

    /**
     * Get the current state of a workflow instance
     *
     * @param WorkflowInstance $instance Workflow instance
     * @return string Current state name
     */
    public function getCurrentState(WorkflowInstance $instance): string;

    /**
     * Check if a state exists in the workflow definition
     *
     * @param WorkflowInstance $instance Workflow instance
     * @param string $stateName State name to check
     * @return bool True if state exists
     */
    public function stateExists(WorkflowInstance $instance, string $stateName): bool;
}
