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
        $tableName = Config::get('inventory-management.table_names.opening_balances', 'transaction_opening_balances');
        $stockModel = Config::get('inventory-management.models.stock');
        $stocksTable = Config::get('inventory-management.table_names.stocks', 'stocks');
        $quantityScale = Config::get('inventory-management.quantity_precision', 4);

        Schema::create($tableName, function (Blueprint $table) use ($stockModel, $stocksTable, $quantityScale) {
            $table->id();
            $table->foreignIdFor($stockModel, 'stock_id')->constrained($stocksTable)->cascadeOnDelete();
            $table->decimal('initial_quantity', 24, $quantityScale);
            $table->timestamp('recorded_at')->nullable();
            $table->text('note')->nullable();
            $table->nullableMorphs('reference');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = Config::get('inventory-management.table_names.opening_balances', 'transaction_opening_balances');

        Schema::dropIfExists($tableName);
    }
};
