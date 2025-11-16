<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Variables;

use Nexus\Sequencing\Core\Contracts\CustomVariableInterface;
use Nexus\Sequencing\Core\Contracts\ValidationResult;
use Nexus\Sequencing\Core\ValueObjects\GenerationContext;
use DateTimeInterface;

/**
 * Project Code Custom Variable
 * 
 * Resolves project codes from context for use in sequence patterns.
 * Example: PO-{PROJECT_CODE}-{YEAR}-{COUNTER:3} -> PO-ALPHA-2025-001
 * 
 * @package Nexus\Sequencing\Core\Variables
 */
class ProjectCodeVariable implements CustomVariableInterface
{
    public function getName(): string
    {
        return 'PROJECT_CODE';
    }

    public function getDescription(): string
    {
        return 'Project identifier code for project-specific sequence generation';
    }

    public function resolve(GenerationContext $context, DateTimeInterface $timestamp): string
    {
        $projectCode = $context->get('project_code') ?? 
                      $context->get('project_id') ?? 
                      $context->get('project') ?? '';
        
        if (empty($projectCode)) {
            throw new \InvalidArgumentException(
                'Project code variable requires "project_code", "project_id", or "project" in generation context'
            );
        }

        return strtoupper(trim((string) $projectCode));
    }

    public function validate(GenerationContext $context): ValidationResult
    {
        $projectCode = $context->get('project_code') ?? 
                      $context->get('project_id') ?? 
                      $context->get('project');
        
        if ($projectCode === null) {
            return ValidationResult::failed([
                'Project code variable requires one of: "project_code", "project_id", or "project" in generation context'
            ]);
        }

        $projectCode = trim((string) $projectCode);
        
        if (empty($projectCode)) {
            return ValidationResult::failed([
                'Project code cannot be empty'
            ]);
        }

        if (strlen($projectCode) > 20) {
            return ValidationResult::failed([
                'Project code cannot exceed 20 characters'
            ]);
        }

        if (!preg_match('/^[A-Z0-9_-]+$/i', $projectCode)) {
            return ValidationResult::failed([
                'Project code can only contain letters, numbers, underscores, and hyphens'
            ]);
        }

        return ValidationResult::success();
    }

    public function getRequiredContextKeys(): array
    {
        return ['project_code']; // Primary preferred key
    }

    public function getOptionalContextKeys(): array
    {
        return ['project_id', 'project']; // Alternative keys
    }

    public function supportsParameters(): bool
    {
        return true;
    }

    public function getSupportedParameters(): array
    {
        return ['UPPER', 'LOWER', 'SHORT'];
    }

    public function resolveWithParameter(
        GenerationContext $context, 
        DateTimeInterface $timestamp, 
        string $parameter
    ): string {
        $baseValue = $this->resolve($context, $timestamp);
        
        return match (strtoupper($parameter)) {
            'UPPER' => strtoupper($baseValue),
            'LOWER' => strtolower($baseValue),
            'SHORT' => $this->getShortCode($baseValue),
            default => throw new \InvalidArgumentException(
                "Unsupported parameter '{$parameter}' for PROJECT_CODE variable. Supported: " . 
                implode(', ', $this->getSupportedParameters())
            ),
        };
    }

    private function getShortCode(string $projectCode): string
    {
        // Create a short code from project name
        // Remove common words and take first letters
        $words = preg_split('/[^A-Z0-9]+/i', $projectCode, -1, PREG_SPLIT_NO_EMPTY);
        
        if (empty($words)) {
            return substr($projectCode, 0, 4);
        }

        // Filter out common words
        $commonWords = ['THE', 'AND', 'OR', 'PROJECT', 'SYSTEM', 'APPLICATION', 'APP'];
        $significantWords = array_filter($words, function($word) use ($commonWords) {
            return !in_array(strtoupper($word), $commonWords);
        });

        if (empty($significantWords)) {
            $significantWords = $words; // Use all words if none are significant
        }

        // Take first letter of each significant word
        $short = '';
        foreach ($significantWords as $word) {
            $short .= strtoupper($word[0] ?? '');
        }

        return $short ?: substr($projectCode, 0, 4); // Fallback to first 4 characters
    }
}