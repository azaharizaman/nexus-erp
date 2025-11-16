<?php

declare(strict_types=1);

namespace Nexus\OrgStructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportingLine extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'org_reporting_lines';

    protected $fillable = [
        'tenant_id',
        'manager_employee_id',
        'subordinate_employee_id',
        'position_id',
        'effective_from',
        'effective_to',
        'metadata',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the position for this reporting line
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Scope to filter by tenant
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by manager
     */
    public function scopeForManager(Builder $query, string $managerId): Builder
    {
        return $query->where('manager_employee_id', $managerId);
    }

    /**
     * Scope to filter by subordinate
     */
    public function scopeForSubordinate(Builder $query, string $subordinateId): Builder
    {
        return $query->where('subordinate_employee_id', $subordinateId);
    }

    /**
     * Scope to get current reporting lines (effective now)
     */
    public function scopeCurrent(Builder $query, ?string $date = null): Builder
    {
        $date = $date ?? now()->toDateString();
        return $query->where('effective_from', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('effective_to')
                          ->orWhere('effective_to', '>=', $date);
                    });
    }

    /**
     * Check if reporting line is currently active
     */
    public function isActive(?string $date = null): bool
    {
        $date = $date ?? now()->toDateString();
        return $this->effective_from <= $date &&
               ($this->effective_to === null || $this->effective_to >= $date);
    }
}
