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
        Schema::create('rfq_vendor_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('request_for_quotations')->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->timestamp('invited_at');
            $table->timestamp('responded_at')->nullable();
            $table->enum('response_status', ['pending', 'accepted', 'declined', 'no_response'])->default('pending');
            $table->text('response_notes')->nullable();
            $table->timestamps();

            $table->unique(['rfq_id', 'vendor_id']);
            $table->index(['response_status']);
            $table->index(['invited_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfq_vendor_invitations');
    }
};