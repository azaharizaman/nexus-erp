<?php

use Nexus\Manufacturing\Enums\ProductType;
use Nexus\Manufacturing\Enums\BOMStatus;
use Nexus\Manufacturing\Enums\WorkOrderStatus;
use Nexus\Manufacturing\Models\Product;
use Nexus\Manufacturing\Models\BillOfMaterial;
use Nexus\Manufacturing\Models\BOMItem;
use Nexus\Manufacturing\Models\WorkOrder;
use Nexus\Manufacturing\Services\BOMExplosionService;
use Nexus\Manufacturing\Repositories\BillOfMaterialRepository;

beforeEach(function () {
    $this->repository = app(BillOfMaterialRepository::class);
    $this->service = new BOMExplosionService($this->repository);
});

test('BOM explosion calculates multi-level component requirements', function () {
    // Create a 3-level BOM structure
    // FG -> SA1 -> COMP1
    //    -> COMP2
    
    $finishedGood = Product::create([
        'code' => 'FG-001',
        'name' => 'Finished Good',
        'type' => ProductType::FinishedGood,
        'uom' => 'EA',
        'standard_cost' => 100,
    ]);

    $subAssembly = Product::create([
        'code' => 'SA-001',
        'name' => 'Sub Assembly',
        'type' => ProductType::SubAssembly,
        'uom' => 'EA',
        'standard_cost' => 50,
    ]);

    $component1 = Product::create([
        'code' => 'COMP-001',
        'name' => 'Component 1',
        'type' => ProductType::Component,
        'uom' => 'EA',
        'standard_cost' => 20,
    ]);

    $component2 = Product::create([
        'code' => 'COMP-002',
        'name' => 'Component 2',
        'type' => ProductType::Component,
        'uom' => 'EA',
        'standard_cost' => 10,
    ]);

    // BOM for finished good
    $fgBom = BillOfMaterial::create([
        'product_id' => $finishedGood->id,
        'version' => '1',
        'status' => BOMStatus::Active,
    ]);

    BOMItem::create([
        'bill_of_material_id' => $fgBom->id,
        'component_product_id' => $subAssembly->id,
        'line_number' => 10,
        'quantity' => 2, // 2 sub-assemblies per FG
        'uom' => 'EA',
    ]);

    BOMItem::create([
        'bill_of_material_id' => $fgBom->id,
        'component_product_id' => $component2->id,
        'line_number' => 20,
        'quantity' => 3, // 3 component2 per FG
        'uom' => 'EA',
    ]);

    // BOM for sub-assembly
    $saBom = BillOfMaterial::create([
        'product_id' => $subAssembly->id,
        'version' => '1',
        'status' => BOMStatus::Active,
    ]);

    BOMItem::create([
        'bill_of_material_id' => $saBom->id,
        'component_product_id' => $component1->id,
        'line_number' => 10,
        'quantity' => 4, // 4 component1 per sub-assembly
        'uom' => 'EA',
    ]);

    // Explode the BOM
    $exploded = $this->service->explode($fgBom->id, 10); // 10 finished goods

    // Expected: 
    // - 20 sub-assemblies (2 * 10)
    // - 80 component1 (4 * 20)
    // - 30 component2 (3 * 10)
    
    expect($exploded)->toHaveCount(3);

    $component1Qty = collect($exploded)->firstWhere('product_id', $component1->id)['total_quantity'];
    $component2Qty = collect($exploded)->firstWhere('product_id', $component2->id)['total_quantity'];
    $subAssemblyQty = collect($exploded)->firstWhere('product_id', $subAssembly->id)['total_quantity'];

    expect($component1Qty)->toBe(80.0)
        ->and($component2Qty)->toBe(30.0)
        ->and($subAssemblyQty)->toBe(20.0);
});

test('BOM cost calculation rolls up component costs', function () {
    $finishedGood = Product::create([
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
        'standard_cost' => 10.50,
    ]);

    $component2 = Product::create([
        'code' => 'COMP-002',
        'name' => 'Component 2',
        'type' => ProductType::Component,
        'uom' => 'EA',
        'standard_cost' => 5.25,
    ]);

    $bom = BillOfMaterial::create([
        'product_id' => $finishedGood->id,
        'version' => '1',
        'status' => BOMStatus::Active,
    ]);

    BOMItem::create([
        'bill_of_material_id' => $bom->id,
        'component_product_id' => $component1->id,
        'line_number' => 10,
        'quantity' => 2,
        'uom' => 'EA',
    ]);

    BOMItem::create([
        'bill_of_material_id' => $bom->id,
        'component_product_id' => $component2->id,
        'line_number' => 20,
        'quantity' => 4,
        'uom' => 'EA',
    ]);

    $cost = $this->service->calculateBOMCost($bom->id);

    // Expected: (2 * 10.50) + (4 * 5.25) = 21.00 + 21.00 = 42.00
    expect($cost['material_cost'])->toBe(42.0);
});
