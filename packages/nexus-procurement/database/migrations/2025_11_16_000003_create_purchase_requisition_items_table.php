<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_requisition_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('requisition_id')->constrained('purchase_requisitions')->onDelete('cascade');
            $table->integer('line_number');
            $table->string('item_description');
            $table->decimal('quantity', 12, 4);
            $table->string('unit_of_measure')->default('EA');
            $table->decimal('unit_price_estimate', 12, 4);
            $table->string('gl_account_code')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['requisition_id', 'line_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requisition_items');
    }
};