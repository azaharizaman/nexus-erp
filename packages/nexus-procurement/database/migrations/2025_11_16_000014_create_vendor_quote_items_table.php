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
        Schema::create('vendor_quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_quote_id')->constrained('vendor_quotes')->onDelete('cascade');
            $table->foreignId('rfq_item_id')->constrained('rfq_items')->onDelete('cascade');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->integer('delivery_days')->nullable();
            $table->text('alternate_offer')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('specifications_met')->default(true);
            $table->timestamps();

            $table->index(['vendor_quote_id', 'rfq_item_id']);
            $table->index(['tenant_id']);
            $table->index(['unit_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_quote_items');
    }
};