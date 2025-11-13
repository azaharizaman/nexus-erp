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
        $itemsTable = Config::get('inventory-management.table_names.items', 'items');
        $unitModel = Config::get('inventory-management.models.unit');
        $unitTable = (new $unitModel())->getTable();

        Schema::create($itemsTable, function (Blueprint $table) use ($unitTable) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->foreignId('uom_id')->constrained($unitTable);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $itemsTable = Config::get('inventory-management.table_names.items', 'items');

        Schema::dropIfExists($itemsTable);
    }
};
