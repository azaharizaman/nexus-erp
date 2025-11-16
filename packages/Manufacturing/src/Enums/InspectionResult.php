<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Enums;

enum InspectionResult: string
{
    case PASSED = 'passed';
    case FAILED = 'failed';
    case CONDITIONAL_PASS = 'conditional_pass';

    public function label(): string
    {
        return match($this) {
            self::PASSED => 'Passed',
            self::FAILED => 'Failed',
            self::CONDITIONAL_PASS => 'Conditional Pass',
        };
    }

    public function isAcceptable(): bool
    {
        return in_array($this, [self::PASSED, self::CONDITIONAL_PASS]);
    }
}
