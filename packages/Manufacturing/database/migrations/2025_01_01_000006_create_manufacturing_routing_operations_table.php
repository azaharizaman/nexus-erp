<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_routing_operations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('routing_id')->constrained('manufacturing_routings')->cascadeOnDelete();
            $table->foreignUlid('work_center_id')->constrained('manufacturing_work_centers');
            $table->integer('operation_number');
            $table->string('operation_name');
            $table->text('description')->nullable();
            $table->integer('setup_time_minutes')->default(0);
            $table->integer('run_time_minutes_per_unit')->default(0);
            $table->integer('queue_time_minutes')->default(0);
            $table->integer('wait_time_minutes')->default(0);
            $table->integer('move_time_minutes')->default(0);
            $table->decimal('labor_hours_per_unit', 10, 4)->default(0);
            $table->timestamps();
            
            $table->index('routing_id');
            $table->index('work_center_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_routing_operations');
    }
};
