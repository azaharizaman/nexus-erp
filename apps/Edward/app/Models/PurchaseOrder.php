<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Sequencing\Traits\HasSequence;

/**
 * Purchase Order Model (Demo with Sequence Integration)
 * 
 * This is a demo model for testing HasSequence trait integration
 * and showcasing the nexus-sequencing package capabilities.
 * 
 * Sequence Pattern: PO-{?PROJECT_CODE?{PROJECT_CODE:SHORT}-:}{YEAR}-{COUNTER:4}
 * Example: PO-ALPHA-2025-0001 or PO-2025-0001
 */
class PurchaseOrder extends Model
{
    use HasUuids, SoftDeletes, HasSequence;

    protected $fillable = [
        'po_number',
        'vendor_id',
        'total_amount',
        'status',
        'project_code',
        'priority',
        'department_code',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'priority' => 'integer',
    ];

    /**
     * Configure sequence generation for purchase orders.
     */
    public function getSequenceName(): string
    {
        return 'purchase_orders';
    }

    /**
     * Define the field to store the generated sequence.
     */
    public function getSequenceField(): string
    {
        return 'po_number';
    }

    /**
     * Provide context for sequence generation.
     */
    public function getSequenceContext(): array
    {
        return [
            'project_code' => $this->project_code,
            'priority' => $this->priority,
            'department_code' => $this->department_code,
        ];
    }

    /**
     * Handle sequence generation failure (optional).
     */
    public function onSequenceGenerationFailure(\Exception $exception): void
    {
        // Log the error or handle gracefully
        logger()->error('PO sequence generation failed', [
            'model' => static::class,
            'attributes' => $this->getAttributes(),
            'error' => $exception->getMessage(),
        ]);

        // Set a fallback value
        $this->po_number = 'PO-TEMP-' . now()->format('YmdHis');
    }
}
