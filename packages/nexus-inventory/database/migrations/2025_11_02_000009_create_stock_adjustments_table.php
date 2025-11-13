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
        $tableName = Config::get('inventory-management.table_names.stock_adjustments', 'transaction_stock_adjustments');
        $stockModel = Config::get('inventory-management.models.stock');
        $stocksTable = Config::get('inventory-management.table_names.stocks', 'stocks');

        Schema::create($tableName, function (Blueprint $table) use ($stockModel, $stocksTable) {
            $table->id();
            $table->foreignIdFor($stockModel, 'stock_id')->constrained($stocksTable)->cascadeOnDelete();
            $table->string('reason_code')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('adjusted_at')->nullable();
            $table->nullableMorphs('adjusted_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = Config::get('inventory-management.table_names.stock_adjustments', 'transaction_stock_adjustments');

        Schema::dropIfExists($tableName);
    }
};
