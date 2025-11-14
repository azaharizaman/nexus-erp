<?php

namespace Nexus\Workflow\Traits;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Nexus\Workflow\Core\DTOs\WorkflowInstance as WorkflowInstanceDTO;
use Nexus\Workflow\Engines\DatabaseWorkflowEngine;
use Nexus\Workflow\Models\WorkflowInstance;
use RuntimeException;

/**
 * HasDatabaseWorkflow Trait
 * 
 * Provides workflow capabilities to Eloquent models using database-driven workflows.
 * Models using this trait can have workflow instances attached and can transition
 * between states defined in the workflow.
 * 
 * Usage:
 * ```php
 * class PurchaseOrder extends Model
 * {
 *     use HasDatabaseWorkflow;
 * 
 *     protected $workflowCode = 'purchase-order-approval';
 * }
 * 
 * // In your code:
 * $po = PurchaseOrder::find($id);
 * $po->initializeWorkflow('purchase-order-approval');
 * 
 * if ($po->canTransition('submit')) {
 *     $po->applyTransition('submit', ['user_id' => auth()->id()]);
 * }
 * 
 * $availableActions = $po->getAvailableTransitions();
 * $currentState = $po->getCurrentWorkflowState();
 * ```
 * 
 * @package Nexus\Workflow\Traits
 */
trait HasDatabaseWorkflow
{
    /**
     * Get the workflow instance for this model.
     * 
     * @return MorphOne
     */
    public function workflowInstance(): MorphOne
    {
        return $this->morphOne(WorkflowInstance::class, 'subject');
    }

    /**
     * Initialize a workflow for this model.
     * 
     * @param string $workflowCode Workflow definition code or ID
     * @param array $context Optional context data
     * @return WorkflowInstance
     * @throws RuntimeException
     */
    public function initializeWorkflow(string $workflowCode, array $context = []): WorkflowInstance
    {
        // Check if workflow already exists
        if ($this->workflowInstance()->exists()) {
            throw new RuntimeException('Workflow instance already exists for this model');
        }

        // Get the workflow engine
        $engine = $this->getWorkflowEngine();

        // Verify workflow exists
        if (!$engine->definitionExists($workflowCode)) {
            throw new RuntimeException("Workflow definition not found: {$workflowCode}");
        }

        // Load definition to get initial state
        $definition = $engine->getDefinitionFresh($workflowCode);
        $initialState = $definition['initial_state'] ?? 'draft';

        // Create workflow instance
        return WorkflowInstance::create([
            'workflow_definition_id' => $this->getWorkflowDefinitionId($workflowCode),
            'subject_type' => get_class($this),
            'subject_id' => $this->getKey(),
            'current_state' => $initialState,
            'context' => $context,
        ]);
    }

    /**
     * Check if a transition is allowed from the current state.
     * 
     * @param string $transitionName Transition name
     * @param array $context Optional context for guard evaluation
     * @return bool
     */
    public function canTransition(string $transitionName, array $context = []): bool
    {
        $instance = $this->workflowInstance;

        if (!$instance) {
            return false;
        }

        $engine = $this->getWorkflowEngine();
        $dto = $this->instanceToDTO($instance);

        return $engine->canTransition($dto, $transitionName, $context);
    }

    /**
     * Apply a transition to the workflow.
     * 
     * @param string $transitionName Transition name
     * @param array $context Optional context data
     * @return WorkflowInstance Updated workflow instance
     * @throws RuntimeException
     */
    public function applyTransition(string $transitionName, array $context = []): WorkflowInstance
    {
        $instance = $this->workflowInstance;

        if (!$instance) {
            throw new RuntimeException('No workflow instance found for this model');
        }

        $engine = $this->getWorkflowEngine();
        $dto = $this->instanceToDTO($instance);

        // Apply transition
        $result = $engine->applyTransition($dto, $transitionName, $context);

        // Update instance
        $instance->update([
            'current_state' => $result->toState,
            'context' => array_merge($instance->context ?? [], $context),
        ]);

        // Record transition
        $instance->transitions()->create([
            'from_state' => $result->fromState,
            'to_state' => $result->toState,
            'transition' => $transitionName,
            'context' => $context,
            'actor_id' => $context['actor_id'] ?? $context['user_id'] ?? null,
        ]);

        return $instance->fresh();
    }

    /**
     * Get available transitions from the current state.
     * 
     * @param array $context Optional context for guard evaluation
     * @return array Array of transition names
     */
    public function getAvailableTransitions(array $context = []): array
    {
        $instance = $this->workflowInstance;

        if (!$instance) {
            return [];
        }

        $engine = $this->getWorkflowEngine();
        $dto = $this->instanceToDTO($instance);

        return $engine->getAvailableTransitions($dto, $context);
    }

    /**
     * Get the current workflow state.
     * 
     * @return string|null Current state name or null if no workflow
     */
    public function getCurrentWorkflowState(): ?string
    {
        return $this->workflowInstance?->current_state;
    }

    /**
     * Check if the model is in a specific state.
     * 
     * @param string $state State name to check
     * @return bool
     */
    public function isInState(string $state): bool
    {
        return $this->getCurrentWorkflowState() === $state;
    }

    /**
     * Check if the model is in any of the given states.
     * 
     * @param array $states State names to check
     * @return bool
     */
    public function isInAnyState(array $states): bool
    {
        $currentState = $this->getCurrentWorkflowState();
        return $currentState && in_array($currentState, $states);
    }

    /**
     * Get workflow transition history.
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getWorkflowHistory()
    {
        return $this->workflowInstance?->transitions()
            ->orderBy('created_at', 'desc')
            ->get() ?? collect();
    }

    /**
     * Get the last workflow transition.
     * 
     * @return \Nexus\Workflow\Models\WorkflowTransition|null
     */
    public function getLastWorkflowTransition()
    {
        return $this->workflowInstance?->transitions()
            ->latest()
            ->first();
    }

    /**
     * Reload the workflow instance relationship.
     * 
     * @return self
     */
    public function refreshWorkflow(): self
    {
        $this->load('workflowInstance');
        return $this;
    }

    /**
     * Get the workflow engine instance.
     * 
     * @return DatabaseWorkflowEngine
     */
    protected function getWorkflowEngine(): DatabaseWorkflowEngine
    {
        return app(DatabaseWorkflowEngine::class);
    }

    /**
     * Convert WorkflowInstance model to DTO.
     * 
     * @param WorkflowInstance $instance Workflow instance model
     * @return WorkflowInstanceDTO
     */
    protected function instanceToDTO(WorkflowInstance $instance): WorkflowInstanceDTO
    {
        return new WorkflowInstanceDTO(
            workflowId: (string) $instance->workflow_definition_id,
            currentState: $instance->current_state,
            context: $instance->context ?? []
        );
    }

    /**
     * Get workflow definition ID from code.
     * 
     * @param string $codeOrId Workflow code or ID
     * @return string Workflow definition ID
     */
    protected function getWorkflowDefinitionId(string $codeOrId): string
    {
        // If it's a UUID, return as-is
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $codeOrId)) {
            return $codeOrId;
        }

        // Otherwise, look up by code
        $definition = \Nexus\Workflow\Models\WorkflowDefinition::where('code', $codeOrId)
            ->where('is_active', true)
            ->orderBy('version', 'desc')
            ->first();

        if (!$definition) {
            throw new RuntimeException("Workflow definition not found: {$codeOrId}");
        }

        return (string) $definition->id;
    }

    /**
     * Scope query to models in a specific workflow state.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $state State name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInWorkflowState($query, string $state)
    {
        return $query->whereHas('workflowInstance', function ($q) use ($state) {
            $q->where('current_state', $state);
        });
    }

    /**
     * Scope query to models in any of the given workflow states.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $states State names
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInAnyWorkflowState($query, array $states)
    {
        return $query->whereHas('workflowInstance', function ($q) use ($states) {
            $q->whereIn('current_state', $states);
        });
    }

    /**
     * Scope query to models with workflows.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasWorkflow($query)
    {
        return $query->has('workflowInstance');
    }

    /**
     * Scope query to models without workflows.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithoutWorkflow($query)
    {
        return $query->doesntHave('workflowInstance');
    }
}
