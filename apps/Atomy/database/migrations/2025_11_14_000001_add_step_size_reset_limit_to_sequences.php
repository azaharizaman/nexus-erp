<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Step Size and Reset Limit Support to Sequences
 *
 * This migration adds Phase 2.2 functionality:
 * - step_size: Custom increment amount (default: 1)
 * - reset_limit: Count-based reset trigger (optional)
 *
 * Features:
 * - Backward compatibility: step_size defaults to 1
 * - Count-based resets: reset_limit = null means no count limit
 * - Validation: step_size must be > 0
 * - Index on reset_limit for automated reset queries
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('serial_number_sequences', function (Blueprint $table) {
            // Step size: How much to increment counter by (default: 1)
            // Examples: step_size=1 gives 1,2,3... step_size=5 gives 1,6,11...
            $table->unsignedInteger('step_size')->default(1)->after('padding');
            
            // Reset limit: Reset counter after reaching this value (optional)
            // NULL means no count-based reset, only time-based
            // Examples: reset_limit=1000 resets after counter reaches 1000
            $table->unsignedBigInteger('reset_limit')->nullable()->after('step_size');
            
            // Index for automated reset queries by count
            $table->index('reset_limit', 'idx_sequences_reset_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('serial_number_sequences', function (Blueprint $table) {
            $table->dropIndex('idx_sequences_reset_limit');
            $table->dropColumn(['step_size', 'reset_limit']);
        });
    }
};