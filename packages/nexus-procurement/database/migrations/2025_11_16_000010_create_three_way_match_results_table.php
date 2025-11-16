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
        Schema::create('three_way_match_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('po_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignUuid('grn_id')->constrained('goods_receipt_notes')->onDelete('cascade');
            $table->foreignUuid('vendor_invoice_id')->constrained('vendor_invoices')->onDelete('cascade');
            $table->timestamp('match_date');
            $table->enum('match_status', ['success', 'price_variance', 'quantity_variance', 'total_variance', 'rejected'])->default('success');
            $table->decimal('price_variance_pct', 5, 2)->default(0);
            $table->decimal('quantity_variance_pct', 5, 2)->default(0);
            $table->decimal('total_variance_amount', 12, 2)->default(0);
            $table->json('tolerance_applied')->nullable();
            $table->boolean('approved_override')->default(false);
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('variance_details')->nullable();
            $table->timestamps();

            $table->unique(['po_id', 'grn_id', 'vendor_invoice_id']);
            $table->index(['match_status', 'match_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('three_way_match_results');
    }
};