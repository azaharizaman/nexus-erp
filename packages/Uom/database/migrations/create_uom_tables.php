<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uom_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('uom_units', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('symbol', 32)->nullable();
            $table->foreignId('uom_type_id')->constrained('uom_types')->cascadeOnDelete();
            $table->decimal('conversion_factor', 24, 12)->default(1);
            $table->decimal('offset', 24, 12)->default(0);
            $table->unsignedTinyInteger('precision')->default(2);
            $table->boolean('is_base')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['uom_type_id', 'is_active']);
        });

        Schema::create('uom_conversions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_unit_id')->constrained('uom_units')->cascadeOnDelete();
            $table->foreignId('target_unit_id')->constrained('uom_units')->cascadeOnDelete();
            $table->decimal('factor', 24, 12)->default(1);
            $table->decimal('offset', 24, 12)->default(0);
            $table->string('direction', 24)->default('both');
            $table->boolean('is_linear')->default(true);
            $table->string('formula')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['source_unit_id', 'target_unit_id']);
        });

        Schema::create('uom_aliases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('unit_id')->constrained('uom_units')->cascadeOnDelete();
            $table->string('alias');
            $table->boolean('is_preferred')->default(false);
            $table->timestamps();
            $table->unique(['unit_id', 'alias']);
        });

        Schema::create('uom_unit_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->nullable()->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('uom_unit_group_unit', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('unit_group_id')->constrained('uom_unit_groups')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('uom_units')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['unit_group_id', 'unit_id']);
        });

        Schema::create('uom_compound_units', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('symbol')->nullable();
            $table->foreignId('uom_type_id')->nullable()->constrained('uom_types')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('uom_compound_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compound_unit_id')->constrained('uom_compound_units')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('uom_units')->cascadeOnDelete();
            $table->integer('exponent')->default(1);
            $table->timestamps();
            $table->unique(['compound_unit_id', 'unit_id']);
        });

        Schema::create('uom_packagings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('base_unit_id')->constrained('uom_units')->cascadeOnDelete();
            $table->foreignId('package_unit_id')->constrained('uom_units')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('label')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['base_unit_id', 'package_unit_id']);
        });

        Schema::create('uom_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignId('default_unit_id')->nullable()->constrained('uom_units')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('uom_item_packagings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('uom_items')->cascadeOnDelete();
            $table->foreignId('packaging_id')->constrained('uom_packagings')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['item_id', 'packaging_id']);
        });

        Schema::create('uom_conversion_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_unit_id')->nullable()->constrained('uom_units')->nullOnDelete();
            $table->foreignId('target_unit_id')->nullable()->constrained('uom_units')->nullOnDelete();
            $table->decimal('factor_used', 24, 12)->nullable();
            $table->decimal('value', 36, 18);
            $table->decimal('result', 36, 18);
            $table->json('metadata')->nullable();
            $table->nullableMorphs('performed_by');
            $table->timestamp('performed_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('uom_custom_units', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->string('symbol', 32)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('uom_type_id')->nullable()->constrained('uom_types')->nullOnDelete();
            $table->decimal('conversion_factor', 24, 12)->default(1);
            $table->json('metadata')->nullable();
            $table->nullableMorphs('owner');
            $table->timestamps();
            $table->unique(['code', 'owner_type', 'owner_id']);
        });

        Schema::create('uom_custom_conversions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_custom_unit_id')->constrained('uom_custom_units')->cascadeOnDelete();
            $table->foreignId('target_custom_unit_id')->constrained('uom_custom_units')->cascadeOnDelete();
            $table->string('formula')->nullable();
            $table->decimal('factor', 24, 12)->default(1);
            $table->decimal('offset', 24, 12)->default(0);
            $table->boolean('is_linear')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['source_custom_unit_id', 'target_custom_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_custom_conversions');
        Schema::dropIfExists('uom_custom_units');
        Schema::dropIfExists('uom_conversion_logs');
        Schema::dropIfExists('uom_item_packagings');
        Schema::dropIfExists('uom_items');
        Schema::dropIfExists('uom_packagings');
        Schema::dropIfExists('uom_compound_components');
        Schema::dropIfExists('uom_compound_units');
        Schema::dropIfExists('uom_unit_group_unit');
        Schema::dropIfExists('uom_unit_groups');
        Schema::dropIfExists('uom_aliases');
        Schema::dropIfExists('uom_conversions');
        Schema::dropIfExists('uom_units');
        Schema::dropIfExists('uom_types');
    }
};
