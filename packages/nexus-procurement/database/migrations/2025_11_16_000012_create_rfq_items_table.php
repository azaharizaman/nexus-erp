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
        Schema::create('rfq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('rfq_id')->constrained('request_for_quotations')->onDelete('cascade');
            $table->foreignId('requisition_item_id')->nullable()->constrained('purchase_requisition_items')->onDelete('set null');
            $table->integer('line_number');
            $table->text('item_description');
            $table->decimal('quantity', 15, 2);
            $table->string('unit_of_measure', 10);
            $table->text('specifications')->nullable();
            $table->decimal('estimated_unit_price', 15, 2)->nullable();
            $table->date('required_delivery_date')->nullable();
            $table->timestamps();

            $table->index(['rfq_id', 'line_number']);
            $table->index(['tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfq_items');
    }
};