<?php

declare(strict_types=1);

namespace Nexus\Crm\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Contact Created Event
 *
 * Fired when a new contact is added to a CRM-enabled model.
 */
class ContactCreatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Model $model,
        public array $contact
    ) {}
}