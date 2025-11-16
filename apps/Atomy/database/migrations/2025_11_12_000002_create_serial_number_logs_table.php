<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Serial Number Logs Table Migration
 *
 * This migration creates the serial_number_logs table for comprehensive
 * audit trail of all generated serial numbers. This table is append-only
 * to maintain immutable audit records.
 *
 * Key Features:
 * - Complete audit trail of all generated numbers
 * - Tenant isolation for multi-tenant systems
 * - Causer tracking (who generated the number)
 * - Request context for debugging
 * - Metadata for additional context
 *
 * Performance Indexes:
 * - Index on (tenant_id, created_at) for tenant-scoped queries
 * - Index on (tenant_id, sequence_name) for sequence-specific logs
 * - Index on generated_number for uniqueness checks
 * - Index on causer_id for user audit queries
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('serial_number_logs', function (Blueprint $table) {
            $table->id();

            // Foreign key to sequence
            $table->unsignedBigInteger('sequence_id')->index();

            // Generated number
            $table->string('generated_number', 255)->index();

            // Counter value at time of generation
            $table->unsignedBigInteger('counter_value');

            // Context data used during generation
            $table->json('context')->nullable();

            // Action type (generated, overridden, reset)
            $table->string('action_type', 50)->index();

            // Reason for manual override or reset
            $table->text('reason')->nullable();

            // Causer (who performed this action)
            $table->unsignedBigInteger('causer_id')->nullable()->index();

            // Timestamp (append-only table)
            $table->timestamp('created_at')->index();

            // Foreign key constraint
            $table->foreign('sequence_id')
                ->references('id')
                ->on('serial_number_sequences')
                ->onDelete('cascade');

            // Composite index for performance
            $table->index(['sequence_id', 'created_at'], 'idx_logs_sequence_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_number_logs');
    }
};
