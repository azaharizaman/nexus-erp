<?php

use Nexus\Manufacturing\Enums\ProductType;
use Nexus\Manufacturing\Enums\BOMStatus;
use Nexus\Manufacturing\Enums\WorkOrderStatus;
use Nexus\Manufacturing\Models\Product;
use Nexus\Manufacturing\Models\BillOfMaterial;
use Nexus\Manufacturing\Models\BOMItem;
use Nexus\Manufacturing\Models\WorkOrder;
use Nexus\Manufacturing\Services\WorkOrderPlanningService;
use Nexus\Manufacturing\Services\ProductionExecutionService;
use Nexus\Manufacturing\Repositories\WorkOrderRepository;
use Nexus\Manufacturing\Repositories\BillOfMaterialRepository;
use Nexus\Manufacturing\Services\BOMExplosionService;

beforeEach(function () {
    $this->workOrderRepo = app(WorkOrderRepository::class);
    $this->bomRepo = app(BillOfMaterialRepository::class);
    $this->bomExplosionService = new BOMExplosionService($this->bomRepo);
    $this->planningService = new WorkOrderPlanningService($this->workOrderRepo, $this->bomRepo, $this->bomExplosionService);
    $this->executionService = new ProductionExecutionService($this->workOrderRepo);
});

test('complete work order lifecycle from planning to completion', function () {
    // Setup: Create product with BOM
    $product = Product::create([
        'code' => 'FG-001',
        'name' => 'Finished Good',
        'type' => ProductType::FinishedGood,
        'uom' => 'EA',
    ]);

    $component = Product::create([
        'code' => 'COMP-001',
        'name' => 'Component',
        'type' => ProductType::Component,
        'uom' => 'EA',
        'standard_cost' => 10,
    ]);

    $bom = BillOfMaterial::create([
        'product_id' => $product->id,
        'version' => '1',
        'status' => BOMStatus::Active,
    ]);

    BOMItem::create([
        'bill_of_material_id' => $bom->id,
        'component_product_id' => $component->id,
        'line_number' => 10,
        'quantity' => 5,
        'uom' => 'EA',
    ]);

    // Step 1: Create work order
    $workOrder = $this->planningService->createWorkOrder([
        'product_id' => $product->id,
        'quantity_ordered' => 100,
        'planned_start_date' => now()->format('Y-m-d'),
    ]);

    expect($workOrder)->toBeInstanceOf(WorkOrder::class)
        ->and($workOrder->status)->toBe(WorkOrderStatus::Planned)
        ->and($workOrder->quantity_ordered)->toBe(100.0);

    // Step 2: Release work order (allocates materials)
    $this->planningService->releaseWorkOrder($workOrder->id);
    
    $workOrder->refresh();
    expect($workOrder->status)->toBe(WorkOrderStatus::Released)
        ->and($workOrder->materialAllocations)->toHaveCount(1);

    $allocation = $workOrder->materialAllocations->first();
    expect($allocation->component_product_id)->toBe($component->id)
        ->and($allocation->quantity_required)->toBe(500.0); // 5 per unit * 100 units

    // Step 3: Start production
    $this->executionService->startProduction($workOrder->id);
    
    $workOrder->refresh();
    expect($workOrder->status)->toBe(WorkOrderStatus::InProduction)
        ->and($workOrder->actual_start_date)->not->toBeNull();

    // Step 4: Report production (partial)
    $this->executionService->reportProduction($workOrder->id, [
        'quantity_completed' => 50,
        'quantity_scrapped' => 5,
        'labor_hours' => 40,
    ]);

    $workOrder->refresh();
    expect($workOrder->quantity_completed)->toBe(50.0)
        ->and($workOrder->quantity_scrapped)->toBe(5.0)
        ->and($workOrder->productionReports)->toHaveCount(1);

    // Step 5: Report more production
    $this->executionService->reportProduction($workOrder->id, [
        'quantity_completed' => 50,
        'quantity_scrapped' => 0,
        'labor_hours' => 38,
    ]);

    $workOrder->refresh();
    expect($workOrder->quantity_completed)->toBe(100.0)
        ->and($workOrder->quantity_scrapped)->toBe(5.0)
        ->and($workOrder->productionReports)->toHaveCount(2);

    // Step 6: Complete work order
    $this->executionService->completeWorkOrder($workOrder->id);

    $workOrder->refresh();
    expect($workOrder->status)->toBe(WorkOrderStatus::Completed)
        ->and($workOrder->actual_end_date)->not->toBeNull();
});

test('work order can be paused and resumed', function () {
    $product = Product::create([
        'code' => 'FG-001',
        'name' => 'Finished Good',
        'type' => ProductType::FinishedGood,
        'uom' => 'EA',
    ]);

    $bom = BillOfMaterial::create([
        'product_id' => $product->id,
        'version' => '1',
        'status' => BOMStatus::Active,
    ]);

    $workOrder = $this->planningService->createWorkOrder([
        'product_id' => $product->id,
        'quantity_ordered' => 100,
    ]);

    $this->planningService->releaseWorkOrder($workOrder->id);
    $this->executionService->startProduction($workOrder->id);

    // Pause production
    $this->executionService->pauseWorkOrder($workOrder->id, 'Machine breakdown');

    $workOrder->refresh();
    expect($workOrder->status)->toBe(WorkOrderStatus::OnHold);

    // Resume production
    $this->executionService->resumeWorkOrder($workOrder->id);

    $workOrder->refresh();
    expect($workOrder->status)->toBe(WorkOrderStatus::InProduction);
});

test('work order progress tracking works correctly', function () {
    $product = Product::create([
        'code' => 'FG-001',
        'name' => 'Finished Good',
        'type' => ProductType::FinishedGood,
        'uom' => 'EA',
    ]);

    $bom = BillOfMaterial::create([
        'product_id' => $product->id,
        'version' => '1',
        'status' => BOMStatus::Active,
    ]);

    $workOrder = $this->planningService->createWorkOrder([
        'product_id' => $product->id,
        'quantity_ordered' => 100,
    ]);

    $this->planningService->releaseWorkOrder($workOrder->id);
    $this->executionService->startProduction($workOrder->id);

    // Report 60% completion
    $this->executionService->reportProduction($workOrder->id, [
        'quantity_completed' => 60,
        'quantity_scrapped' => 5,
        'labor_hours' => 50,
    ]);

    $progress = $this->executionService->getWorkOrderProgress($workOrder->id);

    expect($progress['completion_percentage'])->toBe(60.0)
        ->and($progress['quantity_remaining'])->toBe(40.0)
        ->and($progress['total_labor_hours'])->toBe(50.0)
        ->and($progress['report_count'])->toBe(1);
});
