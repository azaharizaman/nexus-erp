<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_production_costing', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('work_order_id')->unique()->constrained('manufacturing_work_orders')->cascadeOnDelete();
            $table->decimal('standard_material_cost', 15, 2)->default(0);
            $table->decimal('standard_labor_cost', 15, 2)->default(0);
            $table->decimal('standard_overhead_cost', 15, 2)->default(0);
            $table->decimal('actual_material_cost', 15, 2)->default(0);
            $table->decimal('actual_labor_cost', 15, 2)->default(0);
            $table->decimal('actual_overhead_cost', 15, 2)->default(0);
            $table->timestamps();
            
            $table->index('work_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_production_costing');
    }
};
