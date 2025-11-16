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
        Schema::create('request_for_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('rfq_number')->unique();
            $table->foreignId('requisition_id')->nullable()->constrained('purchase_requisitions')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('quote_deadline');
            $table->enum('status', ['draft', 'sent', 'closed', 'cancelled'])->default('draft');
            $table->json('evaluation_criteria')->nullable();
            $table->text('evaluation_notes')->nullable();
            $table->foreignId('selected_vendor_id')->nullable()->constrained('vendors')->onDelete('set null');
            $table->foreignId('selected_quote_id')->nullable()->constrained('vendor_quotes')->onDelete('set null');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['quote_deadline']);
            $table->index(['requisition_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_for_quotations');
    }
};