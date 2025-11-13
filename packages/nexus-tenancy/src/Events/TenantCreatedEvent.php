<?php

declare(strict_types=1);

namespace Nexus\Tenancy\Events;

use Nexus\Tenancy\Models\Tenant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tenant Created Event
 *
 * Dispatched when a new tenant is successfully created.
 */
class TenantCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance
     *
     * @param  Tenant  $tenant  The newly created tenant
     */
    public function __construct(
        public readonly Tenant $tenant
    ) {}
}
