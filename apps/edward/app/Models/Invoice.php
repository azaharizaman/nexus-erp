<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Sequencing\Traits\HasSequence;

/**
 * Invoice Model (Demo with Sequence Integration)
 * 
 * This is a demo model for testing HasSequence trait integration
 * and showcasing the nexus-sequencing package capabilities.
 * 
 * Sequence Pattern: INV-{?DEPARTMENT?{DEPARTMENT:ABBREV}:GEN}-{YEAR}-{COUNTER:5}
 * Example: INV-SALES-2025-00001 or INV-GEN-2025-00001
 */
class Invoice extends Model
{
    use HasUuids, SoftDeletes, HasSequence;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'total_amount',
        'tax_amount',
        'net_amount',
        'status',
        'department_code',
        'issued_date',
        'due_date',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'issued_date' => 'date',
        'due_date' => 'date',
    ];

    /**
     * Configure sequence generation for invoices.
     */
    public function getSequenceName(): string
    {
        return 'invoices';
    }

    /**
     * Define the field to store the generated sequence.
     */
    public function getSequenceField(): string
    {
        return 'invoice_number';
    }

    /**
     * Provide context for sequence generation.
     * Uses InvoiceTemplate pattern: INV-{?DEPARTMENT?{DEPARTMENT:ABBREV}:GEN}-{YEAR}-{COUNTER:5}
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
        logger()->error('Invoice sequence generation failed', [
            'model' => static::class,
            'attributes' => $this->getAttributes(),
            'error' => $exception->getMessage(),
        ]);

        // Set a fallback value
        $this->invoice_number = 'INV-TEMP-' . now()->format('YmdHis');
    }

    /**
     * Get the department abbreviation for demo purposes.
     */
    public function getDepartmentAbbreviation(): ?string
    {
        $departments = [
            'SALES' => 'SLS',
            'MARKETING' => 'MKT', 
            'FINANCE' => 'FIN',
            'OPERATIONS' => 'OPS',
            'HR' => 'HR',
            'IT' => 'IT',
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
}