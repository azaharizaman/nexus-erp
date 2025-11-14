<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Examples;

use Nexus\Sequencing\Traits\HasSequence;
use Illuminate\Database\Eloquent\Model;

/**
 * Example Purchase Order Model
 *
 * This is a demonstration model showing how to use the HasSequence trait
 * for automatic sequence number generation.
 *
 * When a PurchaseOrder is created, it will automatically generate a
 * sequence number using the 'PURCHASE_ORDER_NUMBER' sequence and store
 * it in the 'po_number' field.
 */
class PurchaseOrder extends Model
{
    use HasSequence;

    protected $fillable = [
        'tenant_id',
        'vendor_name',
        'total_amount',
        'status',
        // Note: po_number is automatically generated, don't include in fillable
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Customize the sequence field (default: 'sequence_number')
    protected $sequenceField = 'po_number';

    // Customize the sequence name (default: 'PURCHASE_ORDER_NUMBER')
    protected $sequenceName = 'PURCHASE_ORDER';

    // Customize failure mode (default: 'silent')
    protected $sequenceFailureMode = 'strict'; // Throw error if sequence generation fails

    /**
     * Get additional context for sequence pattern variables.
     *
     * This context is passed to the sequence pattern evaluator,
     * allowing patterns like 'PO-{DEPARTMENT}-{YEAR}-{COUNTER:4}'
     *
     * @return array<string, mixed>
     */
    public function getSequenceContext(): array
    {
        return array_merge(parent::getSequenceContext(), [
            'department' => 'PURCHASING',
            'vendor_code' => $this->getVendorCode(),
            'amount_range' => $this->getAmountRange(),
        ]);
    }

    /**
     * Get vendor code for sequence context.
     */
    private function getVendorCode(): string
    {
        if (empty($this->vendor_name)) {
            return 'UNK';
        }

        // Extract first 3 characters of vendor name
        return strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $this->vendor_name), 0, 3));
    }

    /**
     * Get amount range for sequence context.
     */
    private function getAmountRange(): string
    {
        $amount = $this->total_amount ?? 0;

        if ($amount < 1000) return 'SM';      // Small
        if ($amount < 10000) return 'MD';     // Medium  
        if ($amount < 100000) return 'LG';    // Large
        return 'XL';                          // Extra Large
    }
}

/**
 * Example Invoice Model
 *
 * Shows minimal usage with default settings.
 */
class Invoice extends Model
{
    use HasSequence;

    protected $fillable = [
        'tenant_id',
        'customer_name',
        'amount',
    ];

    // Using all defaults:
    // - sequenceField: 'sequence_number'
    // - sequenceName: 'INVOICE_NUMBER' 
    // - sequenceFailureMode: 'silent'
}