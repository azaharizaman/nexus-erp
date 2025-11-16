<?php

declare(strict_types=1);

namespace Nexus\Crm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nexus\Crm\Models\CrmEntity;

/**
 * Webhook Failed Event
 *
 * Fired when a webhook delivery fails after all retries.
 */
class WebhookFailedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param CrmEntity $entity The CRM entity
     * @param string $url The webhook URL
     * @param string $error The error message
     */
    public function __construct(
        public readonly CrmEntity $entity,
        public readonly string $url,
        public readonly string $error
    ) {}
}
