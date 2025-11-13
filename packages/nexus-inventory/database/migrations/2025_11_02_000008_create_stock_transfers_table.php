<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = Config::get('inventory-management.table_names.stock_transfers', 'transaction_stock_transfers');
        $locationsTable = Config::get('inventory-management.table_names.locations', 'locations');
        $locationModel = Config::get('inventory-management.models.location');

        Schema::create($tableName, function (Blueprint $table) use ($locationsTable, $locationModel) {
            $table->id();
            $table->foreignIdFor($locationModel, 'source_location_id')->constrained($locationsTable)->cascadeOnDelete();
            $table->foreignIdFor($locationModel, 'destination_location_id')->constrained($locationsTable)->cascadeOnDelete();
            $table->timestamp('initiated_at')->nullable();
            $table->text('note')->nullable();
            $table->nullableMorphs('initiated_by');
            $table->nullableMorphs('reference');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = Config::get('inventory-management.table_names.stock_transfers', 'transaction_stock_transfers');

        Schema::dropIfExists($tableName);
    }
};
