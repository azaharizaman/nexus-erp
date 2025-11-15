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

class OrgUnit extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'org_org_units';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'parent_org_unit_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the parent organizational unit
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'parent_org_unit_id');
    }

    /**
     * Get the child organizational units
     */
    public function children(): HasMany
    {
        return $this->hasMany(OrgUnit::class, 'parent_org_unit_id');
    }

    /**
     * Get all positions in this organizational unit
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    /**
     * Get all assignments in this organizational unit
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /**
     * Scope to get root organizational units (no parent)
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_org_unit_id');
    }

    /**
     * Scope to filter by tenant
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Get the full hierarchy path as a string
     */
    public function getHierarchyPath(): string
    {
        $path = [$this->name];
        $current = $this;

        while ($current->parent) {
            $current = $current->parent;
            array_unshift($path, $current->name);
        }

        return implode(' > ', $path);
    }
}
