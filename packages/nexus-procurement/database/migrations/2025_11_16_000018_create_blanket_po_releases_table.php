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
        Schema::create('blanket_po_releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('blanket_po_id')->constrained('blanket_purchase_orders')->onDelete('cascade');
            $table->string('release_number')->unique();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('total_release_value', 15, 2);
            $table->date('required_delivery_date')->nullable();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'converted_to_po', 'cancelled'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['blanket_po_id', 'status']);
            $table->index(['tenant_id', 'approved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blanket_po_releases');
    }
};