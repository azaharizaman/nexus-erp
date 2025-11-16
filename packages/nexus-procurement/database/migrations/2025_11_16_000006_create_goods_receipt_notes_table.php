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
        Schema::create('goods_receipt_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->string('grn_number')->unique();
            $table->foreignUuid('po_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignUuid('received_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('received_at');
            $table->string('delivery_note_number')->nullable();
            $table->enum('status', ['draft', 'completed', 'cancelled'])->default('draft');
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['po_id', 'status']);
            $table->index('grn_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_notes');
    }
};