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
        Schema::create('workflow_transitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_instance_id');
            $table->string('transition');
            $table->string('from_state');
            $table->string('to_state');
            $table->jsonb('metadata')->nullable();
            $table->uuid('performed_by')->nullable();
            $table->timestamp('performed_at');
            $table->timestamp('created_at');

            $table->foreign('workflow_instance_id')
                ->references('id')
                ->on('workflow_instances')
                ->onDelete('cascade');

            $table->index('workflow_instance_id');
            $table->index('performed_by');
            $table->index('performed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_transitions');
    }
};
