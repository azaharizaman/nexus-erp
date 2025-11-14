<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Variables;

use Nexus\Sequencing\Core\Contracts\CustomVariableInterface;
use Nexus\Sequencing\Core\Contracts\ValidationResult;
use Nexus\Sequencing\Core\ValueObjects\GenerationContext;
use DateTimeInterface;

/**
 * Department Custom Variable
 * 
 * Resolves department codes from context for use in sequence patterns.
 * Example: INV-{DEPARTMENT}-{COUNTER:4} -> INV-SALES-0001
 * 
 * @package Nexus\Sequencing\Core\Variables
 */
class DepartmentVariable implements CustomVariableInterface
{
    public function getName(): string
    {
        return 'DEPARTMENT';
    }

    public function getDescription(): string
    {
        return 'Department code for organizing sequences by department (e.g., SALES, HR, IT)';
    }

    public function resolve(GenerationContext $context, DateTimeInterface $timestamp): string
    {
        $department = $context->get('department_code') ?? $context->get('department') ?? '';
        
        if (empty($department)) {
            throw new \InvalidArgumentException(
                'Department variable requires "department_code" or "department" in generation context'
            );
        }

        return strtoupper(trim((string) $department));
    }

    public function validate(GenerationContext $context): ValidationResult
    {
        $department = $context->get('department_code') ?? $context->get('department');
        
        if ($department === null) {
            return ValidationResult::failed([
                'Department variable requires "department_code" or "department" in generation context'
            ]);
        }

        $department = trim((string) $department);
        
        if (empty($department)) {
            return ValidationResult::failed([
                'Department code cannot be empty'
            ]);
        }

        if (strlen($department) > 10) {
            return ValidationResult::failed([
                'Department code cannot exceed 10 characters'
            ]);
        }

        if (!preg_match('/^[A-Z0-9_]+$/i', $department)) {
            return ValidationResult::failed([
                'Department code can only contain letters, numbers, and underscores'
            ]);
        }

        return ValidationResult::success();
    }

    public function getRequiredContextKeys(): array
    {
        return ['department_code']; // Primary key
    }

    public function getOptionalContextKeys(): array
    {
        return ['department']; // Alternative key
    }

    public function supportsParameters(): bool
    {
        return true;
    }

    public function getSupportedParameters(): array
    {
        return ['UPPER', 'LOWER', 'ABBREV'];
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
            'ABBREV' => $this->getAbbreviation($baseValue),
            default => throw new \InvalidArgumentException(
                "Unsupported parameter '{$parameter}' for DEPARTMENT variable. Supported: " . 
                implode(', ', $this->getSupportedParameters())
            ),
        };
    }

    private function getAbbreviation(string $department): string
    {
        // Common department abbreviations
        return match (strtoupper($department)) {
            'SALES' => 'SAL',
            'HUMAN_RESOURCES', 'HR' => 'HR',
            'INFORMATION_TECHNOLOGY', 'IT' => 'IT',
            'MARKETING' => 'MKT',
            'FINANCE' => 'FIN',
            'OPERATIONS' => 'OPS',
            'RESEARCH_AND_DEVELOPMENT', 'R_AND_D', 'RND' => 'RND',
            'CUSTOMER_SERVICE' => 'CS',
            'LEGAL' => 'LEG',
            'PROCUREMENT' => 'PROC',
            default => substr($department, 0, 3), // First 3 characters as fallback
        };
    }
}