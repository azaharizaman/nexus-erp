<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Nexus\Manufacturing\Enums\BOMStatus;

class BillOfMaterial extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'manufacturing_bill_of_materials';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'bom_version',
        'status',
        'effective_date',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'status' => BOMStatus::class,
        'effective_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Get the product this BOM produces.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the components (BOM items) for this BOM.
     */
    public function components(): HasMany
    {
        return $this->hasMany(BOMItem::class, 'bom_id')->orderBy('line_number');
    }

    /**
     * Get the routing for this BOM.
     */
    public function routing(): HasOne
    {
        return $this->hasOne(Routing::class, 'bom_id');
    }

    /**
     * Get work orders using this BOM.
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'bom_id');
    }

    /**
     * Check if BOM can be edited.
     */
    public function canEdit(): bool
    {
        return $this->status->canEdit();
    }

    /**
     * Check if BOM can be activated.
     */
    public function canActivate(): bool
    {
        return $this->status->canActivate() && $this->components()->count() > 0;
    }

    /**
     * Check if BOM can be made obsolete.
     */
    public function canObsolete(): bool
    {
        return $this->status->canObsolete();
    }

    /**
     * Check if BOM is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === BOMStatus::ACTIVE;
    }

    /**
     * Activate this BOM.
     */
    public function activate(): void
    {
        if (!$this->canActivate()) {
            throw new \RuntimeException('BOM cannot be activated in current state');
        }

        // Deactivate other active BOMs for the same product
        static::where('product_id', $this->product_id)
            ->where('id', '!=', $this->id)
            ->where('status', BOMStatus::ACTIVE)
            ->update(['status' => BOMStatus::OBSOLETE]);

        $this->status = BOMStatus::ACTIVE;
        $this->save();
    }

    /**
     * Make BOM obsolete.
     */
    public function makeObsolete(): void
    {
        if (!$this->canObsolete()) {
            throw new \RuntimeException('BOM cannot be made obsolete in current state');
        }

        $this->status = BOMStatus::OBSOLETE;
        $this->expiry_date = now();
        $this->save();
    }
}
