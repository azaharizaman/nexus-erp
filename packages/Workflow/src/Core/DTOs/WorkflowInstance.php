<?php

declare(strict_types=1);

namespace Nexus\Workflow\Core\DTOs;

/**
 * Workflow Instance DTO - framework-agnostic
 * 
 * Represents a running workflow instance with its current state and history.
 * This is the "execution context" for a specific model/entity going through the workflow.
 */
final class WorkflowInstance
{
    /**
     * @var array<int, array<string, mixed>> Transition history
     */
    private array $history = [];

    /**
     * @param WorkflowDefinition $definition The workflow blueprint
     * @param string $currentState Current state name
     * @param mixed $subject The model/entity this workflow is attached to
     * @param array<string, mixed> $data Workflow context data
     */
    public function __construct(
        private readonly WorkflowDefinition $definition,
        private string $currentState,
        private readonly mixed $subject = null,
        private array $data = [],
    ) {
    }

    /**
     * Get the workflow definition
     */
    public function getDefinition(): WorkflowDefinition
    {
        return $this->definition;
    }

    /**
     * Get the current state
     */
    public function getCurrentState(): string
    {
        return $this->currentState;
    }

    /**
     * Set the current state
     */
    public function setCurrentState(string $state): void
    {
        $this->currentState = $state;
    }

    /**
     * Get the subject (model/entity)
     */
    public function getSubject(): mixed
    {
        return $this->subject;
    }

    /**
     * Get workflow context data
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set workflow context data
     *
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Get transition history
     *
     * @return array<int, array<string, mixed>>
     */
    public function getHistory(): array
    {
        return $this->history;
    }

    /**
     * Add a transition to history
     *
     * @param string $transitionName
     * @param string $fromState
     * @param string $toState
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function addToHistory(
        string $transitionName,
        string $fromState,
        string $toState,
        array $metadata = []
    ): void {
        $this->history[] = [
            'transition' => $transitionName,
            'from' => $fromState,
            'to' => $toState,
            'timestamp' => time(),
            'metadata' => $metadata,
        ];
    }

    /**
     * Get the initial state from definition
     */
    public function getInitialState(): string
    {
        return $this->definition->initialState;
    }

    /**
     * Check if currently in a specific state
     */
    public function isInState(string $stateName): bool
    {
        return $this->currentState === $stateName;
    }

    /**
     * Get the last transition from history
     *
     * @return array<string, mixed>|null
     */
    public function getLastTransition(): ?array
    {
        if (empty($this->history)) {
            return null;
        }
        
        return $this->history[count($this->history) - 1];
    }

    /**
     * Count total transitions in history
     */
    public function getTransitionCount(): int
    {
        return count($this->history);
    }
}
