<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_routings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('bill_of_material_id')->nullable()->constrained('manufacturing_bill_of_materials')->nullOnDelete();
            $table->string('version')->default('1');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('bill_of_material_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_routings');
    }
};
