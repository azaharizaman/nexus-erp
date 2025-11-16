<?php

use Nexus\Manufacturing\Enums\BOMStatus;
use Nexus\Manufacturing\Enums\ProductType;
use Nexus\Manufacturing\Models\Product;
use Nexus\Manufacturing\Models\BillOfMaterial;
use Nexus\Manufacturing\Models\BOMItem;

test('BOM can be activated and sets other BOMs to obsolete', function () {
    $product = Product::create([
        'code' => 'PROD-001',
        'name' => 'Test Product',
        'type' => ProductType::FinishedGood,
        'uom' => 'EA',
    ]);

    $bom1 = BillOfMaterial::create([
        'product_id' => $product->id,
        'version' => '1',
        'status' => BOMStatus::Active,
    ]);

    $bom2 = BillOfMaterial::create([
        'product_id' => $product->id,
        'version' => '2',
        'status' => BOMStatus::Draft,
    ]);

    $bom2->activate();

    expect($bom2->fresh()->status)->toBe(BOMStatus::Active)
        ->and($bom1->fresh()->status)->toBe(BOMStatus::Obsolete);
});

test('component quantity includes scrap allowance', function () {
    $product = Product::create([
        'code' => 'PROD-001',
        'name' => 'Test Product',
        'type' => ProductType::FinishedGood,
        'uom' => 'EA',
    ]);

    $component = Product::create([
        'code' => 'COMP-001',
        'name' => 'Test Component',
        'type' => ProductType::Component,
        'uom' => 'EA',
    ]);

    $bom = BillOfMaterial::create([
        'product_id' => $product->id,
        'version' => '1',
        'status' => BOMStatus::Draft,
    ]);

    $bomItem = BOMItem::create([
        'bill_of_material_id' => $bom->id,
        'component_product_id' => $component->id,
        'line_number' => 10,
        'quantity' => 10,
        'uom' => 'EA',
        'scrap_allowance_percentage' => 10, // 10% scrap
    ]);

    // 10 units + 10% scrap = 11 units total needed
    expect($bomItem->getTotalQuantityNeeded())->toBe(11.0);
});

test('product type rules work correctly', function () {
    $rawMaterial = Product::create([
        'code' => 'RAW-001',
        'name' => 'Raw Material',
        'type' => ProductType::RawMaterial,
        'uom' => 'KG',
    ]);

    $finishedGood = Product::create([
        'code' => 'FG-001',
        'name' => 'Finished Good',
        'type' => ProductType::FinishedGood,
        'uom' => 'EA',
    ]);

    expect($rawMaterial->canHaveBOM())->toBeFalse()
        ->and($rawMaterial->canBeProduced())->toBeFalse()
        ->and($rawMaterial->canBePurchased())->toBeTrue()
        ->and($finishedGood->canHaveBOM())->toBeTrue()
        ->and($finishedGood->canBeProduced())->toBeTrue()
        ->and($finishedGood->canBePurchased())->toBeFalse();
});

test('BOM status transitions work correctly', function () {
    $status = BOMStatus::Draft;
    expect($status->canEdit())->toBeTrue()
        ->and($status->canActivate())->toBeTrue();

    $status = BOMStatus::Active;
    expect($status->canEdit())->toBeFalse()
        ->and($status->canObsolete())->toBeTrue();

    $status = BOMStatus::Obsolete;
    expect($status->canEdit())->toBeFalse()
        ->and($status->canActivate())->toBeFalse();
});
