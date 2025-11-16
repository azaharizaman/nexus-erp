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
        Schema::create('approver_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_definition_id');
            $table->string('transition')->comment('Which transition uses this approver group');
            $table->string('name');
            $table->enum('strategy', ['sequential', 'parallel', 'quorum', 'any', 'weighted'])->default('parallel');
            $table->integer('quorum_count')->nullable()->comment('Required for quorum strategy');
            $table->timestamps();

            $table->foreign('workflow_definition_id')
                ->references('id')
                ->on('workflow_definitions')
                ->onDelete('cascade');

            $table->index('workflow_definition_id');
            $table->index(['workflow_definition_id', 'transition']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approver_groups');
    }
};
