<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_operation_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('work_order_id')->constrained('manufacturing_work_orders')->cascadeOnDelete();
            $table->foreignUlid('operation_id')->nullable()->constrained('manufacturing_routing_operations')->nullOnDelete();
            $table->foreignUlid('work_center_id')->nullable()->constrained('manufacturing_work_centers')->nullOnDelete();
            $table->string('operator_id')->nullable();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->decimal('quantity_processed', 15, 4)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('work_order_id');
            $table->index('start_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_operation_logs');
    }
};
