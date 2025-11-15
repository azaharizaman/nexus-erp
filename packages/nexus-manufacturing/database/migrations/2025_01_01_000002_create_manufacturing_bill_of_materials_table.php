<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_bill_of_materials', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained('manufacturing_products')->cascadeOnDelete();
            $table->string('version')->default('1');
            $table->string('status'); // BOMStatus enum
            $table->date('effective_date')->nullable();
            $table->date('obsolete_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['product_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_bill_of_materials');
    }
};
