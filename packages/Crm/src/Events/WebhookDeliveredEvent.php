<?php

declare(strict_types=1);

namespace Nexus\Crm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nexus\Crm\Models\CrmEntity;

/**
 * Webhook Delivered Event
 *
 * Fired when a webhook is successfully delivered.
 */
class WebhookDeliveredEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param CrmEntity $entity The CRM entity
     * @param string $url The webhook URL
     * @param array $payload The payload sent
     */
    public function __construct(
        public readonly CrmEntity $entity,
        public readonly string $url,
        public readonly array $payload
    ) {}
}
