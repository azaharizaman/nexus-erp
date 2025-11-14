<?php

declare(strict_types=1);

namespace Nexus\Workflow\Engines;

use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Nexus\Workflow\Core\Contracts\WorkflowEngineContract;
use Nexus\Workflow\Core\DTOs\TransitionResult;
use Nexus\Workflow\Core\DTOs\WorkflowInstance as WorkflowInstanceDTO;
use Nexus\Workflow\Core\Services\StateTransitionService;
use Nexus\Workflow\Models\WorkflowDefinition;
use RuntimeException;

/**
 * Database-Driven Workflow Engine
 * 
 * Implements WorkflowEngineContract by loading workflow definitions from the database.
 * Integrates with Phase 1's StateTransitionService for actual state machine execution.
 * Includes caching layer for performance optimization.
 * 
 * @package Nexus\Workflow\Engines
 */
class DatabaseWorkflowEngine implements WorkflowEngineContract
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    protected int $cacheTtl = 3600;

    /**
     * Cache key prefix.
     */
    protected string $cachePrefix = 'workflow:definition:';

    /**
     * State transition service from Phase 1.
     */
    protected StateTransitionService $transitionService;

    /**
     * Constructor.
     * 
     * @param StateTransitionService|null $transitionService
     */
    public function __construct(?StateTransitionService $transitionService = null)
    {
        $this->transitionService = $transitionService ?? new StateTransitionService();
    }

    /**
     * {@inheritdoc}
     */
    public function canTransition(
        WorkflowInstanceDTO $instance,
        string $transitionName,
        array $context = []
    ): bool {
        $definition = $this->loadDefinition($instance->workflowId);

        return $this->transitionService->canTransition(
            $definition,
            $instance->currentState,
            $transitionName,
            $context
        );
    }

    /**
     * {@inheritdoc}
     */
    public function applyTransition(
        WorkflowInstanceDTO $instance,
        string $transitionName,
        array $context = []
    ): TransitionResult {
        $definition = $this->loadDefinition($instance->workflowId);

        return $this->transitionService->applyTransition(
            $definition,
            $instance->currentState,
            $transitionName,
            $context
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableTransitions(
        WorkflowInstanceDTO $instance,
        array $context = []
    ): array {
        $definition = $this->loadDefinition($instance->workflowId);

        return $this->transitionService->getAvailableTransitions(
            $definition,
            $instance->currentState,
            $context
        );
    }

    /**
     * {@inheritdoc}
     */
    public function validateDefinition(array $definition): bool
    {
        return $this->transitionService->validateDefinition($definition);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentState(WorkflowInstanceDTO $instance): string
    {
        return $instance->currentState;
    }

    /**
     * {@inheritdoc}
     */
    public function stateExists(WorkflowInstanceDTO $instance, string $stateName): bool
    {
        $definition = $this->loadDefinition($instance->workflowId);

        return $this->transitionService->stateExists($definition, $stateName);
    }

    /**
     * Load workflow definition from database with caching.
     * 
     * @param string $workflowId Workflow definition ID or code
     * @return array Workflow definition array
     * @throws InvalidArgumentException If definition not found
     */
    protected function loadDefinition(string $workflowId): array
    {
        $cacheKey = $this->cachePrefix . $workflowId;

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($workflowId) {
            // Try to find by ID first
            $definition = WorkflowDefinition::where('id', $workflowId)
                ->where('is_active', true)
                ->first();

            // If not found, try by code
            if (!$definition) {
                $definition = WorkflowDefinition::where('code', $workflowId)
                    ->where('is_active', true)
                    ->orderBy('version', 'desc')
                    ->first();
            }

            if (!$definition) {
                throw new InvalidArgumentException(
                    "Workflow definition not found or inactive: {$workflowId}"
                );
            }

            return $definition->definition;
        });
    }

    /**
     * Clear workflow definition from cache.
     * 
     * @param string $workflowId Workflow definition ID or code
     * @return void
     */
    public function clearCache(string $workflowId): void
    {
        $cacheKey = $this->cachePrefix . $workflowId;
        Cache::forget($cacheKey);
    }

    /**
     * Clear all workflow definition caches.
     * 
     * @return void
     */
    public function clearAllCaches(): void
    {
        // This is a simple implementation
        // In production, you might want to use cache tags or a more sophisticated approach
        Cache::flush();
    }

    /**
     * Set cache TTL in seconds.
     * 
     * @param int $ttl TTL in seconds
     * @return self
     */
    public function setCacheTtl(int $ttl): self
    {
        $this->cacheTtl = $ttl;
        return $this;
    }

    /**
     * Preload and cache a workflow definition.
     * 
     * @param string $workflowId Workflow definition ID or code
     * @return array Workflow definition
     */
    public function preloadDefinition(string $workflowId): array
    {
        $this->clearCache($workflowId);
        return $this->loadDefinition($workflowId);
    }

    /**
     * Get workflow definition without caching.
     * 
     * @param string $workflowId Workflow definition ID or code
     * @return array Workflow definition
     * @throws InvalidArgumentException If definition not found
     */
    public function getDefinitionFresh(string $workflowId): array
    {
        $definition = WorkflowDefinition::where('id', $workflowId)
            ->where('is_active', true)
            ->first();

        if (!$definition) {
            $definition = WorkflowDefinition::where('code', $workflowId)
                ->where('is_active', true)
                ->orderBy('version', 'desc')
                ->first();
        }

        if (!$definition) {
            throw new InvalidArgumentException(
                "Workflow definition not found or inactive: {$workflowId}"
            );
        }

        return $definition->definition;
    }

    /**
     * Check if a workflow definition exists and is active.
     * 
     * @param string $workflowId Workflow definition ID or code
     * @return bool
     */
    public function definitionExists(string $workflowId): bool
    {
        $exists = WorkflowDefinition::where('id', $workflowId)
            ->where('is_active', true)
            ->exists();

        if (!$exists) {
            $exists = WorkflowDefinition::where('code', $workflowId)
                ->where('is_active', true)
                ->exists();
        }

        return $exists;
    }

    /**
     * Get all active workflow definitions.
     * 
     * @return array Array of workflow definitions
     */
    public function getAllDefinitions(): array
    {
        return WorkflowDefinition::where('is_active', true)
            ->get()
            ->map(fn($def) => [
                'id' => (string) $def->id,
                'code' => $def->code,
                'name' => $def->name,
                'version' => $def->version,
                'definition' => $def->definition,
            ])
            ->toArray();
    }

    /**
     * Get the state transition service.
     * 
     * @return StateTransitionService
     */
    public function getTransitionService(): StateTransitionService
    {
        return $this->transitionService;
    }
}
