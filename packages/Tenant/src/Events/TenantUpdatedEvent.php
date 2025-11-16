<?php

declare(strict_types=1);

namespace Nexus\Tenancy\Events;

use Nexus\Tenancy\Models\Tenant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tenant Updated Event
 *
 * Dispatched when a tenant is successfully updated.
 */
class TenantUpdatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance
     *
     * @param  Tenant  $tenant  The updated tenant
     * @param  array<string, mixed>  $originalData  The original data before update
     */
    public function __construct(
        public readonly Tenant $tenant,
        public readonly array $originalData
    ) {}
}
