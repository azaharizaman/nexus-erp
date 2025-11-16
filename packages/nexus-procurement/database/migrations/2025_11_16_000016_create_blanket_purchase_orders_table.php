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
        Schema::create('blanket_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('blanket_po_number')->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('total_committed_value', 15, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->date('valid_from');
            $table->date('valid_until');
            $table->string('payment_terms')->nullable();
            $table->enum('status', ['draft', 'active', 'suspended', 'expired', 'cancelled'])->default('draft');
            $table->decimal('auto_approval_limit', 15, 2)->default(0);
            $table->decimal('utilization_alert_threshold', 5, 2)->default(0.80); // 80%
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'vendor_id']);
            $table->index(['tenant_id', 'valid_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blanket_purchase_orders');
    }
};