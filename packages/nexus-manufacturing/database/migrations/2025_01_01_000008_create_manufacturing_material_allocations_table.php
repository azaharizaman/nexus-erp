<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_material_allocations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('work_order_id')->constrained('manufacturing_work_orders')->cascadeOnDelete();
            $table->foreignUlid('component_product_id')->constrained('manufacturing_products');
            $table->decimal('quantity_required', 15, 4);
            $table->decimal('quantity_issued', 15, 4)->default(0);
            $table->decimal('quantity_consumed', 15, 4)->default(0);
            $table->string('lot_number')->nullable();
            $table->timestamps();
            
            $table->index('work_order_id');
            $table->index('component_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_material_allocations');
    }
};
