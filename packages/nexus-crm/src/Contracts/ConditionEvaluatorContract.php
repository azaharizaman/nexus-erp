<?php

namespace Nexus\Crm\Contracts;

interface ConditionEvaluatorContract
{
    /**
     * Evaluate a condition against the given entity and context.
     *
     * @param array $condition The condition definition
     * @param \Nexus\Crm\Models\CrmEntity $entity The CRM entity
     * @param array $context Additional context data
     * @return bool
     */
    public function evaluate(array $condition, $entity, array $context = []): bool;
}