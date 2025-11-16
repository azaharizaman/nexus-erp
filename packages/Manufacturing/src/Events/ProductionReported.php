<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Events;

use Nexus\Manufacturing\Models\ProductionReport;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductionReported
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ProductionReport $productionReport
    ) {}
}
