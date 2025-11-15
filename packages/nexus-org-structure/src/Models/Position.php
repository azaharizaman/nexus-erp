<?php

declare(strict_types=1);

namespace Nexus\OrgStructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'org_positions';

    protected $fillable = [
        'tenant_id',
        'title',
        'code',
        'org_unit_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the organizational unit this position belongs to
     */
    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    /**
     * Get all assignments for this position
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /**
     * Get all reporting lines for this position
     */
    public function reportingLines(): HasMany
    {
        return $this->hasMany(ReportingLine::class);
    }

    /**
     * Scope to filter by tenant
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by organizational unit
     */
    public function scopeInOrgUnit(Builder $query, string $orgUnitId): Builder
    {
        return $query->where('org_unit_id', $orgUnitId);
    }
}
