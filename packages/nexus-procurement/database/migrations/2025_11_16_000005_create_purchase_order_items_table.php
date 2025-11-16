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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('po_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->integer('line_number');
            $table->string('item_description');
            $table->decimal('quantity', 12, 4);
            $table->string('unit_of_measure')->default('EA');
            $table->decimal('unit_price', 12, 4);
            $table->string('tax_code')->nullable();
            $table->string('gl_account_code')->nullable();
            $table->decimal('received_quantity', 12, 4)->default(0);
            $table->decimal('invoiced_quantity', 12, 4)->default(0);
            $table->timestamps();

            $table->index(['po_id', 'line_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};