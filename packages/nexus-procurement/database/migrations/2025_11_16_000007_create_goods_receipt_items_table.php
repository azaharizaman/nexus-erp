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
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('grn_id')->constrained('goods_receipt_notes')->onDelete('cascade');
            $table->foreignUuid('po_item_id')->constrained('purchase_order_items')->onDelete('cascade');
            $table->decimal('quantity_received', 12, 4);
            $table->decimal('quantity_accepted', 12, 4);
            $table->decimal('quantity_rejected', 12, 4)->default(0);
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['grn_id', 'po_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};