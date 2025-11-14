<?php

declare(strict_types=1);

namespace Nexus\Workflow\Adapters\Laravel\Traits;

use Nexus\Workflow\Adapters\Laravel\Services\WorkflowManager;
use Nexus\Workflow\Core\DTOs\WorkflowDefinition;

/**
 * HasWorkflow Trait - Laravel adapter
 * 
 * Provides workflow capabilities to Eloquent models.
 * This is the Level 1 entry point - define workflow in the model itself.
 * 
 * Usage:
 * ```php
 * class Post extends Model
 * {
 *     use HasWorkflow;
 *     
 *     public function workflow(): array
 *     {
 *         return [
 *             'initialState' => 'draft',
 *             'states' => [...],
 *             'transitions' => [...],
 *         ];
 *     }
 * }
 * 
 * $post->workflow()->apply('submit');
 * if ($post->workflow()->can('approve')) { ... }
 * ```
 */
trait HasWorkflow
{
    /**
     * @var WorkflowManager|null Cached workflow manager instance
     */
    private ?WorkflowManager $workflowManager = null;

    /**
     * @var string|null Cached workflow state column name
     */
    private ?string $workflowStateColumn = null;

    /**
     * Get the workflow manager for this model
     */
    public function workflow(): WorkflowManager
    {
        if ($this->workflowManager === null) {
            $this->workflowManager = new WorkflowManager($this);
        }
        
        return $this->workflowManager;
    }

    /**
     * Define the workflow for this model
     * 
     * This method MUST be implemented by the model.
     * 
     * @return array<string, mixed> Workflow definition
     */
    abstract public function workflowDefinition(): array;

    /**
     * Get the workflow definition as a DTO
     */
    public function getWorkflowDefinition(): WorkflowDefinition
    {
        $definition = $this->workflowDefinition();
        
        // Auto-generate ID from model class if not provided
        if (!isset($definition['id'])) {
            $definition['id'] = strtolower(class_basename($this));
        }
        
        return WorkflowDefinition::fromArray($definition);
    }

    /**
     * Get the database column name for storing workflow state
     * 
     * Override this method to use a different column name.
     */
    public function getWorkflowStateColumn(): string
    {
        if ($this->workflowStateColumn === null) {
            $this->workflowStateColumn = property_exists($this, 'workflowStateColumn')
                ? $this->workflowStateColumn
                : 'workflow_state';
        }
        
        return $this->workflowStateColumn;
    }

    /**
     * Get the current workflow state from the model
     */
    public function getWorkflowState(): string
    {
        $column = $this->getWorkflowStateColumn();
        $state = $this->getAttribute($column);
        
        // If no state is set, use the initial state
        if ($state === null) {
            $definition = $this->getWorkflowDefinition();
            $state = $definition->initialState;
            
            // Optionally set it on the model (but don't save yet)
            $this->setAttribute($column, $state);
        }
        
        return $state;
    }

    /**
     * Set the workflow state on the model
     */
    public function setWorkflowState(string $state): void
    {
        $column = $this->getWorkflowStateColumn();
        $this->setAttribute($column, $state);
    }

    /**
     * Initialize workflow state when creating a new model
     */
    public static function bootHasWorkflow(): void
    {
        static::creating(function ($model) {
            $column = $model->getWorkflowStateColumn();
            
            // Only set initial state if not already set
            if ($model->getAttribute($column) === null) {
                $definition = $model->getWorkflowDefinition();
                $model->setAttribute($column, $definition->initialState);
            }
        });
    }
}
