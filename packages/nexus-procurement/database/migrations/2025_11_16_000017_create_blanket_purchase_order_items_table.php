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
        Schema::create('blanket_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('blanket_po_id')->constrained('blanket_purchase_orders')->onDelete('cascade');
            $table->integer('line_number');
            $table->string('item_description');
            $table->text('specifications')->nullable();
            $table->string('unit_of_measure')->nullable();
            $table->decimal('max_quantity', 12, 2);
            $table->decimal('unit_price', 12, 4);
            $table->decimal('total_line_value', 15, 2);
            $table->string('category_code')->nullable();
            $table->string('gl_account_code')->nullable();
            $table->timestamps();

            $table->index(['blanket_po_id', 'line_number']);
            $table->index(['tenant_id', 'category_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blanket_purchase_order_items');
    }
};