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
        $stocksTable = Config::get('inventory-management.table_names.stocks', 'stocks');
        $locationsTable = Config::get('inventory-management.table_names.locations', 'locations');
        $locationModel = Config::get('inventory-management.models.location');
        $quantityScale = Config::get('inventory-management.quantity_precision', 4);

        Schema::create($stocksTable, function (Blueprint $table) use ($locationModel, $locationsTable, $quantityScale) {
            $table->id();
            $table->morphs('itemable');
            $table->foreignIdFor($locationModel, 'location_id')->constrained($locationsTable)->cascadeOnDelete();
            $table->decimal('quantity', 24, $quantityScale)->default(0);
            $table->timestamps();

            $table->unique(['itemable_type', 'itemable_id', 'location_id'], 'stocks_itemable_location_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $stocksTable = Config::get('inventory-management.table_names.stocks', 'stocks');

        Schema::dropIfExists($stocksTable);
    }
};
