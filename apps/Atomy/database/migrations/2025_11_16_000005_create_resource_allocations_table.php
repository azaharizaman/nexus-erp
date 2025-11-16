<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('allocation_percentage', 5, 2);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedBigInteger('tenant_id');
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->unique(['project_id', 'user_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_allocations');
    }
};