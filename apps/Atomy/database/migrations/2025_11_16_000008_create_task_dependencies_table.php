<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('depends_on_task_id');
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('tasks');
            $table->foreign('depends_on_task_id')->references('id')->on('tasks');
            $table->unique(['task_id', 'depends_on_task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
    }
};