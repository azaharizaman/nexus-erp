<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Nexus\Tenancy\Traits\BelongsToTenant;

/**
 * Request for Quotation Model
 *
 * Manages formal requests for vendor quotations.
 */
class RequestForQuotation extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'rfq_number',
        'requisition_id',
        'created_by',
        'title',
        'description',
        'quote_deadline',
        'status',
        'evaluation_criteria',
        'evaluation_notes',
        'selected_vendor_id',
        'selected_quote_id',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'quote_deadline' => 'datetime',
        'evaluation_criteria' => 'json',
        'closed_at' => 'datetime',
    ];

    /**
     * Get the requisition this RFQ is based on.
     */
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'requisition_id');
    }

    /**
     * Get the user who created the RFQ.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the selected vendor.
     */
    public function selectedVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'selected_vendor_id');
    }

    /**
     * Get the selected quote.
     */
    public function selectedQuote(): BelongsTo
    {
        return $this->belongsTo(VendorQuote::class, 'selected_quote_id');
    }

    /**
     * Get the RFQ line items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(RFQItem::class, 'rfq_id');
    }

    /**
     * Get the invited vendors.
     */
    public function invitedVendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'rfq_vendor_invitations', 'rfq_id', 'vendor_id')
            ->withPivot('invited_at', 'responded_at', 'response_status')
            ->withTimestamps();
    }

    /**
     * Get all vendor quotes for this RFQ.
     */
    public function vendorQuotes(): HasMany
    {
        return $this->hasMany(VendorQuote::class, 'rfq_id');
    }

    /**
     * Check if RFQ is still open for quotes.
     */
    public function isOpen(): bool
    {
        return $this->status === 'sent' && $this->quote_deadline->isFuture();
    }

    /**
     * Check if RFQ has expired.
     */
    public function isExpired(): bool
    {
        return $this->quote_deadline->isPast() && $this->status === 'sent';
    }

    /**
     * Get the number of submitted quotes.
     */
    public function getSubmittedQuotesCount(): int
    {
        return $this->vendorQuotes()->where('status', 'submitted')->count();
    }

    /**
     * Get the lowest quote amount.
     */
    public function getLowestQuoteAmount(): ?float
    {
        return $this->vendorQuotes()
            ->where('status', 'submitted')
            ->min('total_quoted_price');
    }

    /**
     * Get the average quote amount.
     */
    public function getAverageQuoteAmount(): ?float
    {
        return $this->vendorQuotes()
            ->where('status', 'submitted')
            ->avg('total_quoted_price');
    }
}