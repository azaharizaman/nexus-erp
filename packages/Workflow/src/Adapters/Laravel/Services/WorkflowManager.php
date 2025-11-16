<?php

declare(strict_types=1);

namespace Nexus\Workflow\Adapters\Laravel\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Nexus\Workflow\Core\Contracts\WorkflowEngineContract;
use Nexus\Workflow\Core\DTOs\TransitionResult;
use Nexus\Workflow\Core\DTOs\WorkflowInstance;
use Nexus\Workflow\Core\Services\StateTransitionService;

/**
 * Workflow Manager - Laravel adapter
 * 
 * Provides the fluent API for interacting with workflows on Eloquent models.
 * Bridges between the Eloquent model and the framework-agnostic core engine.
 */
final class WorkflowManager
{
    private WorkflowEngineContract $engine;
    private WorkflowInstance $instance;

    /**
     * @param Model $model The Eloquent model with HasWorkflow trait
     */
    public function __construct(
        private readonly Model $model
    ) {
        $this->engine = new StateTransitionService();
        $this->instance = $this->createInstance();
    }

    /**
     * Apply a transition to the model's workflow
     * 
     * This method wraps the state change in a database transaction to ensure ACID compliance.
     *
     * @param string $transitionName Name of the transition to apply
     * @param array<string, mixed> $context Optional context for guards/hooks
     * @return TransitionResult
     * @throws \RuntimeException If transition fails
     */
    public function apply(string $transitionName, array $context = []): TransitionResult
    {
        return DB::transaction(function () use ($transitionName, $context) {
            $result = $this->engine->applyTransition($this->instance, $transitionName, $context);
            
            if ($result->isSuccess()) {
                // Update the model's workflow state
                $this->model->setWorkflowState($result->toState);
                
                // Save the model to persist the state change
                $this->model->save();
            }
            
            return $result;
        });
    }

    /**
     * Check if a transition is allowed from the current state
     *
     * @param string $transitionName Name of the transition to check
     * @param array<string, mixed> $context Optional context for guard evaluation
     * @return bool
     */
    public function can(string $transitionName, array $context = []): bool
    {
        return $this->engine->canTransition($this->instance, $transitionName, $context);
    }

    /**
     * Get all available transitions from the current state
     *
     * @param array<string, mixed> $context Optional context for guard evaluation
     * @return array<string>
     */
    public function availableTransitions(array $context = []): array
    {
        return $this->engine->getAvailableTransitions($this->instance, $context);
    }

    /**
     * Get the current workflow state
     */
    public function currentState(): string
    {
        return $this->instance->getCurrentState();
    }

    /**
     * Get the transition history
     *
     * @return array<int, array<string, mixed>>
     */
    public function history(): array
    {
        return $this->instance->getHistory();
    }

    /**
     * Check if the model is in a specific state
     */
    public function isInState(string $stateName): bool
    {
        return $this->instance->isInState($stateName);
    }

    /**
     * Get the workflow definition
     */
    public function definition(): \Nexus\Workflow\Core\DTOs\WorkflowDefinition
    {
        return $this->instance->getDefinition();
    }

    /**
     * Get the workflow instance (for advanced usage)
     */
    public function instance(): WorkflowInstance
    {
        return $this->instance;
    }

    /**
     * Create a workflow instance from the model
     */
    private function createInstance(): WorkflowInstance
    {
        $definition = $this->model->getWorkflowDefinition();
        $currentState = $this->model->getWorkflowState();
        
        return new WorkflowInstance(
            definition: $definition,
            currentState: $currentState,
            subject: $this->model,
            data: [],
        );
    }
}
