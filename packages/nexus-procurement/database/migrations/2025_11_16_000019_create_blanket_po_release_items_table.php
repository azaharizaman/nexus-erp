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
        Schema::create('blanket_po_release_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('blanket_po_release_id')->constrained('blanket_po_releases')->onDelete('cascade');
            $table->foreignId('blanket_po_item_id')->constrained('blanket_purchase_order_items')->onDelete('cascade');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 4);
            $table->decimal('line_total', 15, 2);
            $table->date('delivery_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['blanket_po_release_id']);
            $table->index(['blanket_po_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blanket_po_release_items');
    }
};