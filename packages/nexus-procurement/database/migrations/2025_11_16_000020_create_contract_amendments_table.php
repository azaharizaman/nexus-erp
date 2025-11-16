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
        Schema::create('contract_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('procurement_contracts')->onDelete('cascade');
            $table->string('amendment_number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('changes');
            $table->date('effective_date');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['contract_id', 'amendment_number']);
            $table->index('effective_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_amendments');
    }
};