<?php

declare(strict_types=1);

namespace Nexus\OrgStructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'org_assignments';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'position_id',
        'org_unit_id',
        'effective_from',
        'effective_to',
        'is_primary',
        'metadata',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_primary' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the position for this assignment
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the organizational unit for this assignment
     */
    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    /**
     * Scope to filter by tenant
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by employee
     */
    public function scopeForEmployee(Builder $query, string $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope to get current assignments (effective now)
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
     * Scope to get primary assignments only
     */
    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    /**
     * Check if assignment is currently active
     */
    public function isActive(?string $date = null): bool
    {
        $date = $date ?? now()->toDateString();
        return $this->effective_from <= $date &&
               ($this->effective_to === null || $this->effective_to >= $date);
    }
}
