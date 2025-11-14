<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Sequencing\Traits\HasSequence;

/**
 * Employee Model (Demo with Sequence Integration)
 * 
 * This is a demo model for testing HasSequence trait integration
 * and showcasing the nexus-sequencing package capabilities.
 * 
 * Sequence Pattern: EMP-{DEPARTMENT:ABBREV}-{YEAR}-{COUNTER:3}
 * Example: EMP-SLS-2025-001, EMP-IT-2025-002
 */
class Employee extends Model
{
    use HasUuids, SoftDeletes, HasSequence;

    protected $fillable = [
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'department_code',
        'position',
        'hire_date',
        'salary',
        'status',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'salary' => 'decimal:2',
    ];

    /**
     * Configure sequence generation for employees.
     */
    public function getSequenceName(): string
    {
        return 'employees';
    }

    /**
     * Define the field to store the generated sequence.
     */
    public function getSequenceField(): string
    {
        return 'employee_id';
    }

    /**
     * Provide context for sequence generation.
     * Uses EmployeeIdTemplate pattern: EMP-{DEPARTMENT:ABBREV}-{YEAR}-{COUNTER:3}
     */
    public function getSequenceContext(): array
    {
        return [
            'department_code' => $this->department_code ?? 'GEN',
        ];
    }

    /**
     * Handle sequence generation failure (optional).
     */
    public function onSequenceGenerationFailure(\Exception $exception): void
    {
        // Log the error or handle gracefully
        logger()->error('Employee sequence generation failed', [
            'model' => static::class,
            'attributes' => $this->getAttributes(),
            'error' => $exception->getMessage(),
        ]);

        // Set a fallback value
        $this->employee_id = 'EMP-TEMP-' . now()->format('YmdHis');
    }

    /**
     * Get full name attribute.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get the department abbreviation for demo purposes.
     */
    public function getDepartmentAbbreviation(): string
    {
        $departments = [
            'SALES' => 'SLS',
            'MARKETING' => 'MKT', 
            'FINANCE' => 'FIN',
            'OPERATIONS' => 'OPS',
            'HR' => 'HR',
            'IT' => 'IT',
            'LEGAL' => 'LEG',
            'ADMIN' => 'ADM',
        ];

        return $departments[strtoupper($this->department_code ?? '')] ?? 'GEN';
    }

    /**
     * Scope to filter by department.
     */
    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department_code', $department);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by active employees.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}