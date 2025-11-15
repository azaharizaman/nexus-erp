<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Enums;

enum ProductType: string
{
    case RAW_MATERIAL = 'raw_material';
    case COMPONENT = 'component';
    case SUB_ASSEMBLY = 'sub_assembly';
    case FINISHED_GOOD = 'finished_good';

    public function label(): string
    {
        return match($this) {
            self::RAW_MATERIAL => 'Raw Material',
            self::COMPONENT => 'Component',
            self::SUB_ASSEMBLY => 'Sub-Assembly',
            self::FINISHED_GOOD => 'Finished Good',
        };
    }

    public function canHaveBOM(): bool
    {
        return in_array($this, [self::SUB_ASSEMBLY, self::FINISHED_GOOD]);
    }

    public function canBeProduced(): bool
    {
        return in_array($this, [self::SUB_ASSEMBLY, self::FINISHED_GOOD]);
    }

    public function canBePurchased(): bool
    {
        return in_array($this, [self::RAW_MATERIAL, self::COMPONENT]);
    }
}
