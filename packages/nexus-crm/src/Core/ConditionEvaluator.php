<?php

declare(strict_types=1);

namespace Nexus\Crm\Core;

use Nexus\Crm\Models\CrmEntity;

/**
 * Condition Evaluator
 *
 * Evaluates conditional expressions for pipeline transitions and actions.
 */
class ConditionEvaluator
{
    /**
     * Evaluate a condition against an entity and context.
     */
    public function evaluate(array $condition, CrmEntity $entity, array $context = []): bool
    {
        $type = $condition['type'] ?? 'simple';

        return match ($type) {
            'simple' => $this->evaluateSimple($condition, $entity, $context),
            'compound' => $this->evaluateCompound($condition, $entity, $context),
            default => false
        };
    }

    /**
     * Evaluate a simple condition.
     */
    private function evaluateSimple(array $condition, CrmEntity $entity, array $context = []): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? null;

        if (!$field) {
            return false;
        }

        $actualValue = $this->getFieldValue($entity, $field, $context);

        return match ($operator) {
            '==' => $actualValue == $value,
            '!=' => $actualValue != $value,
            '>' => $actualValue > $value,
            '<' => $actualValue < $value,
            '>=' => $actualValue >= $value,
            '<=' => $actualValue <= $value,
            'contains' => is_array($actualValue) && in_array($value, $actualValue),
            'not_contains' => is_array($actualValue) && !in_array($value, $actualValue),
            'empty' => empty($actualValue),
            'not_empty' => !empty($actualValue),
            default => false
        };
    }

    /**
     * Evaluate a compound condition (AND/OR).
     */
    private function evaluateCompound(array $condition, CrmEntity $entity, array $context = []): bool
    {
        $operator = $condition['operator'] ?? 'AND';
        $conditions = $condition['conditions'] ?? [];

        if (empty($conditions)) {
            return false;
        }

        $results = [];
        foreach ($conditions as $subCondition) {
            $results[] = $this->evaluate($subCondition, $entity, $context);
        }

        return match ($operator) {
            'AND' => !in_array(false, $results),
            'OR' => in_array(true, $results),
            default => false
        };
    }

    /**
     * Get the value of a field from the entity or context.
     */
    private function getFieldValue(CrmEntity $entity, string $field, array $context = []): mixed
    {
        // Check context first
        if (isset($context[$field])) {
            return $context[$field];
        }

        // Check entity data
        $data = $entity->data ?? [];
        if (isset($data[$field])) {
            return $data[$field];
        }

        // Check entity attributes
        if (isset($entity->$field)) {
            return $entity->$field;
        }

        // Check related data (dot notation)
        if (str_contains($field, '.')) {
            return $this->getNestedValue($entity, $field);
        }

        return null;
    }

    /**
     * Get nested value using dot notation.
     */
    private function getNestedValue(CrmEntity $entity, string $field): mixed
    {
        $parts = explode('.', $field);
        $value = $entity;

        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } elseif (is_object($value) && isset($value->$part)) {
                $value = $value->$part;
            } else {
                return null;
            }
        }

        return $value;
    }
}