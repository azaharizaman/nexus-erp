<?php

declare(strict_types=1);

namespace Nexus\Tenancy\Models;

use Nexus\Tenancy\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'domain',
        'status',
        'configuration',
        'subscription_plan',
        'billing_email',
        'contact_name',
        'contact_email',
        'contact_phone',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => TenantStatus::class,
        'configuration' => 'encrypted:array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the users associated with the tenant.
     * 
     * Note: This should be configured by the application layer
     * since atomic packages shouldn't reference app models directly.
     * Override this in your app's Tenant model if needed.
     */
    public function users(): HasMany
    {
        // TODO: Make this configurable via config or override in app
        throw new \RuntimeException('users() relationship must be configured at application level');
    }

    /**
     * Check if tenant is active.
     */
    public function isActive(): bool
    {
        return $this->status === TenantStatus::ACTIVE;
    }

    /**
     * Check if tenant is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === TenantStatus::SUSPENDED;
    }

    /**
     * Check if tenant is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === TenantStatus::ARCHIVED;
    }

    /**
     * Scope a query to only include active tenants.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', TenantStatus::ACTIVE);
    }

    /**
     * Configure activity logging for this model.
     *
     * @return array<string, mixed>
     */
    protected function configureActivityLogging(): array
    {
        return [
            'log_name' => 'tenants',
            'log_attributes' => [
                'name',
                'domain',
                'status',
                'subscription_plan',
                'billing_email',
                'contact_name',
                'contact_email',
                'contact_phone',
            ],
            'log_only_dirty' => true,
            'dont_submit_empty_logs' => true,
        ];
    }

    /**
     * Configure search behavior for this model.
     *
     * @return array<string, mixed>
     */
    protected function configureSearchable(): array
    {
        return [
            'index_name' => 'tenants',
            'searchable_fields' => [
                'id',
                'name',
                'domain',
                'status',
                'subscription_plan',
                'billing_email',
                'contact_name',
                'contact_email',
                'created_at',
            ],
        ];
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\TenantFactory::new();
    }
}
