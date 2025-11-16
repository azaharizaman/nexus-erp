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
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->string('requisition_number')->unique();
            $table->foreignUuid('requester_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'converted', 'cancelled'])->default('draft');
            $table->text('justification')->nullable();
            $table->decimal('total_estimate', 15, 2);
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['requester_id', 'status']);
            $table->index('requisition_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requisitions');
    }
};