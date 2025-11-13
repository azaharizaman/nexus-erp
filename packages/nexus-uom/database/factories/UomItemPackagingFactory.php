<?php

namespace Nexus\Uom\Database\Factories;

use Nexus\Uom\Models\UomItem;
use Nexus\Uom\Models\UomItemPackaging;
use Nexus\Uom\Models\UomPackaging;
use Illuminate\Database\Eloquent\Factories\Factory;

class UomItemPackagingFactory extends Factory
{
    protected $model = UomItemPackaging::class;

    public function definition(): array
    {
        return [
            'item_id' => UomItem::factory(),
            'packaging_id' => UomPackaging::factory(),
        ];
    }
}
