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
        $movementsTable = Config::get('inventory-management.table_names.stock_movements', 'stock_movements');
        $stocksTable = Config::get('inventory-management.table_names.stocks', 'stocks');
        $stockModel = Config::get('inventory-management.models.stock');
        $quantityScale = Config::get('inventory-management.quantity_precision', 4);

        Schema::create($movementsTable, function (Blueprint $table) use ($stockModel, $stocksTable, $quantityScale) {
            $table->id();
            $table->foreignIdFor($stockModel, 'stock_id')->constrained($stocksTable)->cascadeOnDelete();
            $table->string('serial_number')->unique();
            $table->decimal('quantity_before', 24, $quantityScale);
            $table->decimal('quantity_change', 24, $quantityScale);
            $table->decimal('quantity_after', 24, $quantityScale);
            $table->morphs('transactionable');
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $movementsTable = Config::get('inventory-management.table_names.stock_movements', 'stock_movements');

        Schema::dropIfExists($movementsTable);
    }
};
