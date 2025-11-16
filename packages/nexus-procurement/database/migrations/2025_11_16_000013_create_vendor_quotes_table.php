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
        Schema::create('vendor_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('rfq_id')->constrained('request_for_quotations')->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->timestamp('submitted_at')->nullable();
            $table->enum('status', ['pending', 'submitted', 'selected', 'rejected', 'withdrawn'])->default('pending');
            $table->decimal('total_quoted_price', 15, 2);
            $table->integer('delivery_days')->nullable();
            $table->string('payment_terms')->nullable();
            $table->integer('validity_days')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('evaluation_score', 3, 1)->nullable();
            $table->text('evaluation_notes')->nullable();
            $table->integer('rank')->nullable();
            $table->timestamps();

            $table->unique(['rfq_id', 'vendor_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['rfq_id', 'status']);
            $table->index(['total_quoted_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_quotes');
    }
};