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
        Schema::create('approver_group_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('approver_group_id');
            $table->uuid('user_id');
            $table->integer('sequence')->nullable()->comment('For sequential strategy');
            $table->integer('weight')->nullable()->comment('For weighted strategy');
            $table->timestamps();

            $table->foreign('approver_group_id')
                ->references('id')
                ->on('approver_groups')
                ->onDelete('cascade');

            $table->index('approver_group_id');
            $table->index('user_id');
            $table->unique(['approver_group_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approver_group_members');
    }
};
