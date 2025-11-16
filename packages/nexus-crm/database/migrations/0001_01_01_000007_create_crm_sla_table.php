<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_sla', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('entity_id');
            $table->foreign('entity_id')->references('id')->on('crm_entities')->onDelete('cascade');

            $table->integer('duration_minutes')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('breach_at')->nullable();
            $table->string('status')->default('on_track'); // on_track|at_risk|breached

            $table->timestamps();

            $table->index(['entity_id', 'status']);
            $table->index('breach_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_sla');
    }
};
