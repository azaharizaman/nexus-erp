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
        $locationsTable = Config::get('inventory-management.table_names.locations', 'locations');

        Schema::create($locationsTable, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $locationsTable = Config::get('inventory-management.table_names.locations', 'locations');

        Schema::dropIfExists($locationsTable);
    }
};
