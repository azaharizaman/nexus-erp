<?php

use Nexus\Manufacturing\Enums\InspectionResult;
use Nexus\Manufacturing\Enums\DispositionType;

test('inspection result business rules work correctly', function () {
    expect(InspectionResult::PASSED->isAcceptable())->toBeTrue()
        ->and(InspectionResult::FAILED->isAcceptable())->toBeFalse()
        ->and(InspectionResult::CONDITIONAL_PASS->isAcceptable())->toBeTrue();
});

test('disposition type business rules work correctly', function () {
    expect(DispositionType::ACCEPT->allowsUsage())->toBeTrue()
        ->and(DispositionType::REJECT->allowsUsage())->toBeFalse()
        ->and(DispositionType::QUARANTINE->allowsUsage())->toBeFalse()
        ->and(DispositionType::USE_AS_IS->allowsUsage())->toBeTrue()
        ->and(DispositionType::REWORK->requiresAction())->toBeTrue()
        ->and(DispositionType::RETURN_TO_VENDOR->requiresAction())->toBeTrue()
        ->and(DispositionType::ACCEPT->requiresAction())->toBeFalse();
});
