<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Enums;

enum BOMStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case OBSOLETE = 'obsolete';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::ACTIVE => 'Active',
            self::OBSOLETE => 'Obsolete',
        };
    }

    public function canEdit(): bool
    {
        return $this === self::DRAFT;
    }

    public function canActivate(): bool
    {
        return $this === self::DRAFT;
    }

    public function canObsolete(): bool
    {
        return $this === self::ACTIVE;
    }
}
