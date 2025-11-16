<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_quality_inspections', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('work_order_id')->constrained('manufacturing_work_orders')->cascadeOnDelete();
            $table->foreignUlid('inspection_plan_id')->nullable()->constrained('manufacturing_inspection_plans')->nullOnDelete();
            $table->string('lot_number');
            $table->string('inspector_id')->nullable();
            $table->dateTime('inspection_date');
            $table->string('result'); // InspectionResult enum
            $table->string('disposition')->nullable(); // DispositionType enum
            $table->dateTime('disposition_date')->nullable();
            $table->text('disposition_notes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('work_order_id');
            $table->index('lot_number');
            $table->index(['result', 'disposition']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_quality_inspections');
    }
};
