<?php

declare(strict_types=1);

namespace Nexus\Crm\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Contact Deleted Event
 *
 * Fired when a contact is deleted from a CRM-enabled model.
 */
class ContactDeletedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Model $model,
        public array $contact
    ) {}
}