<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Events;

use Nexus\Manufacturing\Models\WorkOrder;
use Nexus\Manufacturing\Models\MaterialAllocation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaterialConsumed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WorkOrder $workOrder,
        public readonly MaterialAllocation $allocation,
        public readonly float $quantityConsumed
    ) {}
}
