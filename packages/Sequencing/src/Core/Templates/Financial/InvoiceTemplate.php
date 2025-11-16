<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Templates\Financial;

use Nexus\Sequencing\Core\Templates\AbstractPatternTemplate;

/**
 * Invoice Pattern Template
 * 
 * Standard invoice numbering pattern supporting department-based
 * organization and fiscal year integration.
 * 
 * Pattern: INV-{?DEPARTMENT?{DEPARTMENT:ABBREV}:GEN}-{YEAR}-{COUNTER:5}
 * Example: INV-SAL-2025-00001
 * 
 * @package Nexus\Sequencing\Core\Templates\Financial
 */
class InvoiceTemplate extends AbstractPatternTemplate
{
    public function getId(): string
    {
        return 'financial.invoice.standard';
    }

    public function getName(): string
    {
        return 'Standard Invoice Numbers';
    }

    public function getDescription(): string
    {
        return 'Standard invoice numbering with department prefix, year, and sequential counter. Supports department-specific abbreviations.';
    }

    public function getBasePattern(): string
    {
        return 'INV-{?DEPARTMENT?{DEPARTMENT:ABBREV}:GEN}-{YEAR}-{COUNTER:5}';
    }

    public function getRequiredContext(): array
    {
        return [];
    }

    public function getOptionalContext(): array
    {
        return [
            'department' => 'Department code for prefix (e.g., SALES, HR, IT)',
            'department_code' => 'Alternative department key',
        ];
    }

    public function getExampleContext(): array
    {
        return [
            'department' => 'SALES',
        ];
    }

    public function getCategory(): string
    {
        return 'Financial';
    }

    public function getTags(): array
    {
        return ['invoice', 'financial', 'billing', 'accounting', 'department'];
    }
}