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
        Schema::create('vendor_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->foreignUuid('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignUuid('po_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignUuid('grn_id')->nullable()->constrained('goods_receipt_notes')->onDelete('set null');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->string('currency_code')->default('USD');
            $table->enum('match_status', ['pending', 'matched', 'variance', 'rejected'])->default('pending');
            $table->enum('payment_status', ['pending', 'authorized', 'paid'])->default('pending');
            $table->foreignUuid('payment_authorized_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('payment_authorized_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'match_status']);
            $table->index(['po_id', 'match_status']);
            $table->index('invoice_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_invoices');
    }
};