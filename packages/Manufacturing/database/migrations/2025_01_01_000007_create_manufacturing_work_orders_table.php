<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_work_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('work_order_number')->unique();
            $table->foreignUlid('product_id')->constrained('manufacturing_products');
            $table->foreignUlid('bill_of_material_id')->nullable()->constrained('manufacturing_bill_of_materials')->nullOnDelete();
            $table->foreignUlid('routing_id')->nullable()->constrained('manufacturing_routings')->nullOnDelete();
            $table->string('status'); // WorkOrderStatus enum
            $table->decimal('quantity_ordered', 15, 4);
            $table->decimal('quantity_completed', 15, 4)->default(0);
            $table->decimal('quantity_scrapped', 15, 4)->default(0);
            $table->string('lot_number')->nullable();
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'planned_end_date']);
            $table->index('product_id');
            $table->index('work_order_number');
            $table->index('lot_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_work_orders');
    }
};
