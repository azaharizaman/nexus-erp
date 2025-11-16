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
        Schema::create('vendors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->string('vendor_code')->unique();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('address')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('bank_account')->nullable(); // Encrypted
            $table->string('payment_terms')->default('Net 30');
            $table->string('currency_code')->default('USD');
            $table->enum('vendor_category', ['goods', 'services', 'both'])->default('goods');
            $table->enum('status', ['active', 'suspended', 'inactive'])->default('active');
            $table->json('performance_metrics')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('vendor_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};