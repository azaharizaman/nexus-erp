<?php

declare(strict_types=1);

namespace Nexus\Crm\Actions;

use Nexus\Crm\Models\CrmEntity;
// Refactor: keep TransitionEntity as a plain service class (no laravel-actions)

/**
 * Transition Entity Action
 *
 * Transitions a CRM entity to a new stage in the pipeline.
 */
class TransitionEntity
{

    /**
     * Execute the action.
     */
    public function handle(CrmEntity $entity, string $targetStageId, array $context = []): bool
    {
        $pipelineEngine = app(\Nexus\Crm\Core\PipelineEngine::class);

        return $pipelineEngine->transition($entity, $targetStageId, $context);
    }
}