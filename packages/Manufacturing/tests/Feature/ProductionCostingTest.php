<?php

use Nexus\Manufacturing\Enums\ProductType;
use Nexus\Manufacturing\Enums\BOMStatus;
use Nexus\Manufacturing\Models\Product;
use Nexus\Manufacturing\Models\BillOfMaterial;
use Nexus\Manufacturing\Models\BOMItem;
use Nexus\Manufacturing\Models\ProductionCosting;
use Nexus\Manufacturing\Models\WorkOrder;
use Nexus\Manufacturing\Enums\WorkOrderStatus;
use Nexus\Manufacturing\Services\ProductionCostingService;
use Nexus\Manufacturing\Repositories\WorkOrderRepository;

beforeEach(function () {
    $this->workOrderRepo = app(WorkOrderRepository::class);
    $this->costingService = new ProductionCostingService($this->workOrderRepo);
});

test('production costing calculates standard costs correctly', function () {
    $product = Product::create([
        'code' => 'FG-001',
        'name' => 'Finished Good',
        'type' => ProductType::FinishedGood,
        'uom' => 'EA',
    ]);

    $component1 = Product::create([
        'code' => 'COMP-001',
        'name' => 'Component 1',
        'type' => ProductType::Component,
        'uom' => 'EA',
        'standard_cost' => 10,
    ]);

    $component2 = Product::create([
        'code' => 'COMP-002',
        'name' => 'Component 2',
        'type' => ProductType::Component,
        'uom' => 'EA',
        'standard_cost' => 5,
    ]);

    $bom = BillOfMaterial::create([
        'product_id' => $product->id,
        'version' => '1',
        'status' => BOMStatus::Active,
    ]);

    BOMItem::create([
        'bill_of_material_id' => $bom->id,
        'component_product_id' => $component1->id,
        'line_number' => 10,
        'quantity' => 2, // 2 units per FG
        'uom' => 'EA',
    ]);

    BOMItem::create([
        'bill_of_material_id' => $bom->id,
        'component_product_id' => $component2->id,
        'line_number' => 20,
        'quantity' => 4, // 4 units per FG
        'uom' => 'EA',
    ]);

    $workOrder = WorkOrder::create([
        'work_order_number' => 'WO-001',
        'product_id' => $product->id,
        'bill_of_material_id' => $bom->id,
        'status' => WorkOrderStatus::Planned,
        'quantity_ordered' => 100,
    ]);

    // Allocate materials
    foreach ($bom->components as $component) {
        $workOrder->materialAllocations()->create([
            'component_product_id' => $component->component_product_id,
            'quantity_required' => $component->quantity * $workOrder->quantity_ordered,
        ]);
    }

    $standardCost = $this->costingService->getStandardCost($workOrder->id);

    // Expected material cost: (2 * 10 * 100) + (4 * 5 * 100) = 2000 + 2000 = 4000
    expect($standardCost['material_cost'])->toBe(4000.0);
});

test('production costing tracks material variance', function () {
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
        'quantity' => 2,
        'uom' => 'EA',
    ]);

    $workOrder = WorkOrder::create([
        'work_order_number' => 'WO-001',
        'product_id' => $product->id,
        'bill_of_material_id' => $bom->id,
        'status' => WorkOrderStatus::Completed,
        'quantity_ordered' => 100,
        'quantity_completed' => 100,
    ]);

    // Standard: 200 units (2 per FG * 100)
    // Actual: 220 units consumed (10% over)
    $workOrder->materialAllocations()->create([
        'component_product_id' => $component->id,
        'quantity_required' => 200,
        'quantity_consumed' => 220, // Unfavorable variance
    ]);

    $costing = $this->costingService->calculateCosting($workOrder->id);

    expect($costing->standard_material_cost)->toBe(2000.0) // 200 * 10
        ->and($costing->actual_material_cost)->toBe(2200.0) // 220 * 10
        ->and($costing->getMaterialVariance())->toBe(200.0) // Unfavorable
        ->and($costing->isFavorableVariance('material'))->toBeFalse();
});

test('cost per unit calculation works correctly', function () {
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

    $workOrder = WorkOrder::create([
        'work_order_number' => 'WO-001',
        'product_id' => $product->id,
        'bill_of_material_id' => $bom->id,
        'status' => WorkOrderStatus::Completed,
        'quantity_ordered' => 100,
        'quantity_completed' => 95, // Some scrap
    ]);

    $workOrder->materialAllocations()->create([
        'component_product_id' => $component->id,
        'quantity_required' => 500,
        'quantity_consumed' => 500,
    ]);

    $costPerUnit = $this->costingService->getCostPerUnit($workOrder->id);

    // Standard cost per unit: (500 * 10) / 100 = 50
    // Actual cost per unit: (500 * 10) / 95 = 52.63
    expect($costPerUnit['standard_cost_per_unit'])->toBe(50.0)
        ->and($costPerUnit['actual_cost_per_unit'])->toBe(52.63);
});
