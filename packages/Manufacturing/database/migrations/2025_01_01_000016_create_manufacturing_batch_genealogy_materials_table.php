<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_batch_genealogy_materials', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('batch_genealogy_id')->constrained('manufacturing_batch_genealogy')->cascadeOnDelete();
            $table->string('lot_number')->index();
            $table->foreignUlid('product_id')->constrained('manufacturing_products');
            $table->decimal('quantity_consumed', 15, 4);
            $table->timestamps();
            
            $table->index(['batch_genealogy_id', 'lot_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_batch_genealogy_materials');
    }
};
