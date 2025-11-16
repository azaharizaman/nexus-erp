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
        Schema::create('vendor_invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vendor_invoice_id')->constrained('vendor_invoices')->onDelete('cascade');
            $table->foreignUuid('po_item_id')->nullable()->constrained('purchase_order_items')->onDelete('set null');
            $table->integer('line_number');
            $table->string('description');
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_price', 12, 4);
            $table->decimal('tax_amount', 12, 4)->default(0);
            $table->decimal('line_total', 12, 4);
            $table->timestamps();

            $table->index(['vendor_invoice_id', 'line_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_invoice_items');
    }
};