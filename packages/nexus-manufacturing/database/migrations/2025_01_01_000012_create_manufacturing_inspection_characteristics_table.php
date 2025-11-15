<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_inspection_characteristics', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('inspection_plan_id')->constrained('manufacturing_inspection_plans')->cascadeOnDelete();
            $table->string('characteristic_name');
            $table->text('description')->nullable();
            $table->string('measurement_method')->nullable();
            $table->decimal('target_value', 15, 4)->nullable();
            $table->decimal('lower_limit', 15, 4)->nullable();
            $table->decimal('upper_limit', 15, 4)->nullable();
            $table->string('uom')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            $table->index('inspection_plan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_inspection_characteristics');
    }
};
