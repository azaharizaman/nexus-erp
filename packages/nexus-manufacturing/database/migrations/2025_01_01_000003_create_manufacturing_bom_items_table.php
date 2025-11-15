<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_bom_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('bill_of_material_id')->constrained('manufacturing_bill_of_materials')->cascadeOnDelete();
            $table->foreignUlid('component_product_id')->constrained('manufacturing_products');
            $table->integer('line_number');
            $table->decimal('quantity', 15, 4);
            $table->string('uom');
            $table->decimal('scrap_allowance_percentage', 5, 2)->default(0);
            $table->string('component_type')->default('regular'); // regular, phantom, reference
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('bill_of_material_id');
            $table->index('component_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_bom_items');
    }
};
