<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Enums;

enum DispositionType: string
{
    case ACCEPT = 'accept';
    case REJECT = 'reject';
    case REWORK = 'rework';
    case QUARANTINE = 'quarantine';
    case USE_AS_IS = 'use_as_is';
    case RETURN_TO_VENDOR = 'return_to_vendor';

    public function label(): string
    {
        return match($this) {
            self::ACCEPT => 'Accept',
            self::REJECT => 'Reject',
            self::REWORK => 'Rework',
            self::QUARANTINE => 'Quarantine',
            self::USE_AS_IS => 'Use As-Is',
            self::RETURN_TO_VENDOR => 'Return to Vendor',
        };
    }

    public function allowsUsage(): bool
    {
        return in_array($this, [self::ACCEPT, self::USE_AS_IS]);
    }

    public function requiresAction(): bool
    {
        return in_array($this, [self::REWORK, self::RETURN_TO_VENDOR]);
    }
}
