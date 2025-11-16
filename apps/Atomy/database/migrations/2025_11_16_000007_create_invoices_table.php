<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['draft', 'sent', 'paid'])->default('draft');
            $table->date('due_date');
            $table->json('items'); // array of items
            $table->unsignedBigInteger('tenant_id');
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects');
            $table->foreign('tenant_id')->references('id')->on('tenants');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};