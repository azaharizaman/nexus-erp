<?php

declare(strict_types=1);

namespace Nexus\Crm\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Contact Updated Event
 *
 * Fired when a contact is updated on a CRM-enabled model.
 */
class ContactUpdatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Model $model,
        public array $contact
    ) {}
}