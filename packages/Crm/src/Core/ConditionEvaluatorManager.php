<?php

namespace Nexus\Crm\Core;

use Nexus\Crm\Contracts\ConditionEvaluatorContract;
use Nexus\Crm\Models\CrmEntity;

class ConditionEvaluatorManager implements ConditionEvaluatorContract
{
    protected array $evaluators = [];

    public function registerEvaluator(string $type, ConditionEvaluatorContract $evaluator): void
    {
        $this->evaluators[$type] = $evaluator;
    }

    public function evaluate(array $condition, $entity, array $context = []): bool
    {
        $type = $condition['type'] ?? 'default';

        if (isset($this->evaluators[$type])) {
            return $this->evaluators[$type]->evaluate($condition, $entity, $context);
        }

        // Fallback to default evaluator
        return $this->defaultEvaluate($condition, $entity, $context);
    }

    protected function defaultEvaluate(array $condition, $entity, array $context = []): bool
    {
        // Implement the existing logic here
        if (isset($condition['field'], $condition['operator'], $condition['value'])) {
            return $this->evaluateSimple($condition, $entity);
        }

        if (isset($condition['logic'], $condition['conditions'])) {
            return $this->evaluateCompound($condition, $entity, $context);
        }

        return false;
    }

    protected function evaluateSimple(array $condition, CrmEntity $entity): bool
    {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'];

        $fieldValue = $entity->getFieldValue($field);

        switch ($operator) {
            case 'equals':
                return $fieldValue == $value;
            case 'not_equals':
                return $fieldValue != $value;
            case 'greater_than':
                return $fieldValue > $value;
            case 'less_than':
                return $fieldValue < $value;
            case 'contains':
                return str_contains($fieldValue, $value);
            // Add more operators as needed
            default:
                return false;
        }
    }

    protected function evaluateCompound(array $condition, CrmEntity $entity, array $context): bool
    {
        $logic = $condition['logic'];
        $conditions = $condition['conditions'];

        $results = [];
        foreach ($conditions as $subCondition) {
            $results[] = $this->evaluate($subCondition, $entity, $context);
        }

        if ($logic === 'and') {
            return !in_array(false, $results);
        } elseif ($logic === 'or') {
            return in_array(true, $results);
        }

        return false;
    }
}