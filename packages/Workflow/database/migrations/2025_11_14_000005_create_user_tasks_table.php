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
        Schema::create('user_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_instance_id');
            $table->string('transition')->comment('Which transition this task is for');
            $table->uuid('assigned_to');
            $table->uuid('assigned_by')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->timestamp('due_at')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->jsonb('result')->nullable()->comment('Approval/rejection data');
            $table->timestamp('completed_at')->nullable();
            $table->uuid('completed_by')->nullable();
            $table->timestamps();

            $table->foreign('workflow_instance_id')
                ->references('id')
                ->on('workflow_instances')
                ->onDelete('cascade');

            $table->index('workflow_instance_id');
            $table->index('assigned_to');
            $table->index('status');
            $table->index('due_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_tasks');
    }
};
