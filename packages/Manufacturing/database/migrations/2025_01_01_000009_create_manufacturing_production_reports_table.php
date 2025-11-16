<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_production_reports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('work_order_id')->constrained('manufacturing_work_orders')->cascadeOnDelete();
            $table->foreignUlid('operation_id')->nullable()->constrained('manufacturing_routing_operations')->nullOnDelete();
            $table->string('report_number')->unique();
            $table->decimal('quantity_completed', 15, 4);
            $table->decimal('quantity_scrapped', 15, 4)->default(0);
            $table->decimal('labor_hours', 10, 2)->default(0);
            $table->string('shift')->nullable();
            $table->dateTime('report_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('work_order_id');
            $table->index('report_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_production_reports');
    }
};
