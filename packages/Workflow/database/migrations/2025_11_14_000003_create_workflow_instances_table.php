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
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_definition_id')->nullable();
            $table->string('subject_type')->comment('Polymorphic relation');
            $table->uuid('subject_id')->comment('Polymorphic relation');
            $table->string('current_state');
            $table->jsonb('data')->nullable()->comment('Workflow context data');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('workflow_definition_id')
                ->references('id')
                ->on('workflow_definitions')
                ->onDelete('set null');

            $table->index(['subject_type', 'subject_id']);
            $table->index('workflow_definition_id');
            $table->index('current_state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_instances');
    }
};
