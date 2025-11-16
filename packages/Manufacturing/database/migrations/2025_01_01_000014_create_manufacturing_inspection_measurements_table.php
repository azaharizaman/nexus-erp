<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_inspection_measurements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('quality_inspection_id')->constrained('manufacturing_quality_inspections')->cascadeOnDelete();
            $table->foreignUlid('inspection_characteristic_id')->constrained('manufacturing_inspection_characteristics')->cascadeOnDelete();
            $table->decimal('measured_value', 15, 4);
            $table->boolean('passes')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('quality_inspection_id');
            $table->index('inspection_characteristic_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_inspection_measurements');
    }
};
