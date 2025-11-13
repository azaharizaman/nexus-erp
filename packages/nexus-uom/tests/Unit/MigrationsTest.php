<?php

namespace Nexus\Uom\Tests\Unit;

use Nexus\Uom\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class MigrationsTest extends TestCase
{
    public function test_core_tables_are_created(): void
    {
        $expected = [
            'uom_types',
            'uom_units',
            'uom_conversions',
            'uom_aliases',
            'uom_unit_groups',
            'uom_unit_group_unit',
            'uom_compound_units',
            'uom_compound_components',
            'uom_packagings',
            'uom_items',
            'uom_item_packagings',
            'uom_conversion_logs',
            'uom_custom_units',
            'uom_custom_conversions',
        ];

        foreach ($expected as $table) {
            $this->assertTrue(Schema::hasTable($table), "Failed asserting that table '{$table}' exists.");
        }
    }

    public function test_units_table_has_expected_columns(): void
    {
        $this->assertTrue(
            Schema::hasColumns('uom_units', ['code', 'name', 'conversion_factor', 'is_base', 'precision']),
            'Units table does not expose the expected structural columns.'
        );
    }
}
