<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_escalations', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('entity_id');
            $table->foreign('entity_id')->references('id')->on('crm_entities')->onDelete('cascade');

            $table->integer('level')->default(1);
            $table->ulid('from_user_id')->nullable();
            $table->ulid('to_user_id')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('escalated_at')->nullable();

            $table->timestamps();

            $table->index(['entity_id', 'level']);
            $table->index('escalated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_escalations');
    }
};
