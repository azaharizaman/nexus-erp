<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_inspection_plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained('manufacturing_products')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('inspection_type')->default('final'); // receiving, in-process, final
            $table->integer('sample_size')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['product_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_inspection_plans');
    }
};
