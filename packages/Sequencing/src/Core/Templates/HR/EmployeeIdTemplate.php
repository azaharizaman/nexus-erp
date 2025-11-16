<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Templates\HR;

use Nexus\Sequencing\Core\Templates\AbstractPatternTemplate;

/**
 * Employee ID Pattern Template
 * 
 * Employee identification numbering with department and hire year integration.
 * 
 * Pattern: EMP-{DEPARTMENT:ABBREV}-{YEAR}-{COUNTER:3}
 * Example: EMP-HR-2025-001
 * 
 * @package Nexus\Sequencing\Core\Templates\HR
 */
class EmployeeIdTemplate extends AbstractPatternTemplate
{
    public function getId(): string
    {
        return 'hr.employee_id.departmental';
    }

    public function getName(): string
    {
        return 'Departmental Employee IDs';
    }

    public function getDescription(): string
    {
        return 'Employee ID generation organized by department with hire year for easy identification and sorting.';
    }

    public function getBasePattern(): string
    {
        return 'EMP-{DEPARTMENT:ABBREV}-{YEAR}-{COUNTER:3}';
    }

    public function getRequiredContext(): array
    {
        return ['department'];
    }

    public function getOptionalContext(): array
    {
        return [
            'department_code' => 'Alternative department key',
            'division' => 'Higher level organizational unit',
        ];
    }

    public function getExampleContext(): array
    {
        return [
            'department' => 'HUMAN_RESOURCES',
        ];
    }

    public function getCategory(): string
    {
        return 'Human Resources';
    }

    public function getTags(): array
    {
        return ['employee', 'hr', 'staff', 'department', 'hire_date'];
    }
}