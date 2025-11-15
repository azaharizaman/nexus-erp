<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_batch_genealogy', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('work_order_id')->constrained('manufacturing_work_orders')->cascadeOnDelete();
            $table->string('finished_good_lot')->index();
            $table->timestamps();
            
            $table->unique(['work_order_id', 'finished_good_lot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_batch_genealogy');
    }
};
