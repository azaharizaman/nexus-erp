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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->string('po_number')->unique();
            $table->foreignUuid('requisition_id')->nullable()->constrained('purchase_requisitions')->onDelete('set null');
            $table->foreignUuid('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignUuid('created_by')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'sent', 'partially_received', 'fully_received', 'closed', 'cancelled'])->default('draft');
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->string('payment_terms')->default('Net 30');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->string('currency_code')->default('USD');
            $table->decimal('exchange_rate', 10, 6)->default(1);
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['vendor_id', 'status']);
            $table->index('po_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};