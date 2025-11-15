<?php

use Nexus\Manufacturing\Enums\WorkOrderStatus;
use Nexus\Manufacturing\Enums\ProductType;
use Nexus\Manufacturing\Models\Product;
use Nexus\Manufacturing\Models\WorkOrder;

test('work order status transitions work correctly', function () {
    $planned = WorkOrderStatus::Planned;
    expect($planned->canRelease())->toBeTrue()
        ->and($planned->canStartProduction())->toBeFalse()
        ->and($planned->canCancel())->toBeTrue();

    $released = WorkOrderStatus::Released;
    expect($released->canRelease())->toBeFalse()
        ->and($released->canStartProduction())->toBeTrue()
        ->and($released->canCancel())->toBeTrue();

    $inProduction = WorkOrderStatus::InProduction;
    expect($inProduction->canStartProduction())->toBeFalse()
        ->and($inProduction->canPause())->toBeTrue()
        ->and($inProduction->canComplete())->toBeTrue()
        ->and($inProduction->canCancel())->toBeTrue();

    $onHold = WorkOrderStatus::OnHold;
    expect($onHold->canPause())->toBeFalse()
        ->and($onHold->canResume())->toBeTrue()
        ->and($onHold->canCancel())->toBeTrue();

    $completed = WorkOrderStatus::Completed;
    expect($completed->isClosed())->toBeTrue()
        ->and($completed->canCancel())->toBeFalse();

    $cancelled = WorkOrderStatus::Cancelled;
    expect($cancelled->isClosed())->toBeTrue();
});

test('work order calculates completion percentage correctly', function () {
    $product = Product::create([
        'code' => 'PROD-001',
        'name' => 'Test Product',
        'type' => ProductType::FinishedGood,
        'uom' => 'EA',
    ]);

    $workOrder = WorkOrder::create([
        'work_order_number' => 'WO-001',
        'product_id' => $product->id,
        'status' => WorkOrderStatus::InProduction,
        'quantity_ordered' => 100,
        'quantity_completed' => 50,
        'quantity_scrapped' => 10,
    ]);

    expect($workOrder->getCompletionPercentage())->toBe(50.0)
        ->and($workOrder->getRemainingQuantity())->toBe(50.0);
});

test('work order detects overdue status', function () {
    $product = Product::create([
        'code' => 'PROD-001',
        'name' => 'Test Product',
        'type' => ProductType::FinishedGood,
        'uom' => 'EA',
    ]);

    $overdueWorkOrder = WorkOrder::create([
        'work_order_number' => 'WO-001',
        'product_id' => $product->id,
        'status' => WorkOrderStatus::InProduction,
        'quantity_ordered' => 100,
        'planned_end_date' => now()->subDays(5),
    ]);

    $onTimeWorkOrder = WorkOrder::create([
        'work_order_number' => 'WO-002',
        'product_id' => $product->id,
        'status' => WorkOrderStatus::InProduction,
        'quantity_ordered' => 100,
        'planned_end_date' => now()->addDays(5),
    ]);

    expect($overdueWorkOrder->isOverdue())->toBeTrue()
        ->and($onTimeWorkOrder->isOverdue())->toBeFalse();
});

test('work order validates completion requirements', function () {
    $product = Product::create([
        'code' => 'PROD-001',
        'name' => 'Test Product',
        'type' => ProductType::FinishedGood,
        'uom' => 'EA',
    ]);

    $workOrder = WorkOrder::create([
        'work_order_number' => 'WO-001',
        'product_id' => $product->id,
        'status' => WorkOrderStatus::InProduction,
        'quantity_ordered' => 100,
        'quantity_completed' => 50,
    ]);

    // Cannot complete if quantity doesn't match
    expect($workOrder->canComplete())->toBeFalse();

    $workOrder->update(['quantity_completed' => 100]);
    expect($workOrder->canComplete())->toBeTrue();
});
