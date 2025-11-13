<?php

namespace Nexus\Uom\Database\Seeders;

use Nexus\Uom\Models\UomAlias;
use Nexus\Uom\Models\UomPackaging;
use Nexus\Uom\Models\UomType;
use Nexus\Uom\Models\UomUnit;
use Nexus\Uom\Models\UomUnitGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UomDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $unitCache = [];

        foreach ($this->defaultTypeDefinitions() as $slug => $definition) {
            $type = UomType::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'] ?? null,
                ]
            );

            foreach ($definition['units'] as $unitDefinition) {
                $unit = UomUnit::query()->updateOrCreate(
                    ['code' => Str::upper($unitDefinition['code'])],
                    [
                        'name' => $unitDefinition['name'],
                        'symbol' => $unitDefinition['symbol'] ?? null,
                        'uom_type_id' => $type->id,
                        'conversion_factor' => (string) ($unitDefinition['conversion_factor'] ?? '1'),
                        'offset' => (string) ($unitDefinition['offset'] ?? '0'),
                        'precision' => $unitDefinition['precision'] ?? 3,
                        'is_base' => $unitDefinition['is_base'] ?? false,
                        'is_active' => $unitDefinition['is_active'] ?? true,
                        'metadata' => $unitDefinition['metadata'] ?? null,
                    ]
                );

                $unitCache[$unit->code] = $unit;

                foreach ($unitDefinition['aliases'] ?? [] as $alias => $isPreferred) {
                    UomAlias::query()->updateOrCreate(
                        [
                            'unit_id' => $unit->id,
                            'alias' => $alias,
                        ],
                        ['is_preferred' => (bool) $isPreferred]
                    );
                }
            }
        }

        foreach ($this->defaultGroupDefinitions() as $slug => $definition) {
            $group = UomUnitGroup::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'] ?? null,
                ]
            );

            $unitIds = collect($definition['unit_codes'] ?? [])
                ->map(function (string $code) use ($unitCache) {
                    $key = Str::upper($code);

                    return isset($unitCache[$key]) ? $unitCache[$key]->id : null;
                })
                ->filter()
                ->all();

            if ($unitIds !== []) {
                $group->units()->syncWithoutDetaching($unitIds);
            }
        }

        foreach ($this->defaultPackagingDefinitions() as $definition) {
            $baseCode = Str::upper($definition['base_unit_code'] ?? '');
            $packageCode = Str::upper($definition['package_unit_code'] ?? '');

            if ($baseCode === '' || $packageCode === '') {
                continue;
            }

            $base = Arr::get($unitCache, $baseCode);
            $package = Arr::get($unitCache, $packageCode);

            if (! $base || ! $package) {
                continue;
            }

            UomPackaging::query()->updateOrCreate(
                [
                    'base_unit_id' => $base->id,
                    'package_unit_id' => $package->id,
                ],
                [
                    'quantity' => $definition['quantity'],
                    'label' => $definition['label'] ?? null,
                    'metadata' => $definition['metadata'] ?? null,
                ]
            );
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function defaultTypeDefinitions(): array
    {
        return [
            'mass' => [
                'name' => 'Mass',
                'description' => 'Mass and weight measurement units.',
                'units' => [
                    [
                        'code' => 'KG',
                        'name' => 'Kilogram',
                        'symbol' => 'kg',
                        'conversion_factor' => '1',
                        'precision' => 3,
                        'is_base' => true,
                        'aliases' => ['kilo' => true],
                    ],
                    [
                        'code' => 'G',
                        'name' => 'Gram',
                        'symbol' => 'g',
                        'conversion_factor' => '0.001',
                        'precision' => 3,
                        'aliases' => ['grams' => false],
                    ],
                    [
                        'code' => 'MG',
                        'name' => 'Milligram',
                        'symbol' => 'mg',
                        'conversion_factor' => '0.000001',
                        'precision' => 6,
                    ],
                    [
                        'code' => 'LB',
                        'name' => 'Pound',
                        'symbol' => 'lb',
                        'conversion_factor' => '0.45359237',
                        'precision' => 4,
                        'aliases' => ['lbs' => true],
                    ],
                    [
                        'code' => 'OZ',
                        'name' => 'Ounce',
                        'symbol' => 'oz',
                        'conversion_factor' => '0.028349523125',
                        'precision' => 5,
                    ],
                ],
            ],
            'length' => [
                'name' => 'Length',
                'description' => 'Linear distance measurement units.',
                'units' => [
                    [
                        'code' => 'M',
                        'name' => 'Metre',
                        'symbol' => 'm',
                        'conversion_factor' => '1',
                        'precision' => 4,
                        'is_base' => true,
                    ],
                    [
                        'code' => 'CM',
                        'name' => 'Centimetre',
                        'symbol' => 'cm',
                        'conversion_factor' => '0.01',
                        'precision' => 4,
                        'aliases' => ['centimeter' => false],
                    ],
                    [
                        'code' => 'MM',
                        'name' => 'Millimetre',
                        'symbol' => 'mm',
                        'conversion_factor' => '0.001',
                        'precision' => 4,
                    ],
                    [
                        'code' => 'KM',
                        'name' => 'Kilometre',
                        'symbol' => 'km',
                        'conversion_factor' => '1000',
                        'precision' => 6,
                    ],
                    [
                        'code' => 'FT',
                        'name' => 'Foot',
                        'symbol' => 'ft',
                        'conversion_factor' => '0.3048',
                        'precision' => 5,
                        'aliases' => ['feet' => true],
                    ],
                    [
                        'code' => 'IN',
                        'name' => 'Inch',
                        'symbol' => 'in',
                        'conversion_factor' => '0.0254',
                        'precision' => 5,
                        'aliases' => ['inch' => true],
                    ],
                ],
            ],
            'volume' => [
                'name' => 'Volume',
                'description' => 'Liquid and dry volume units.',
                'units' => [
                    [
                        'code' => 'L',
                        'name' => 'Litre',
                        'symbol' => 'L',
                        'conversion_factor' => '1',
                        'precision' => 4,
                        'is_base' => true,
                    ],
                    [
                        'code' => 'ML',
                        'name' => 'Millilitre',
                        'symbol' => 'mL',
                        'conversion_factor' => '0.001',
                        'precision' => 4,
                        'aliases' => ['cc' => false],
                    ],
                    [
                        'code' => 'GAL',
                        'name' => 'Gallon (US)',
                        'symbol' => 'gal',
                        'conversion_factor' => '3.785411784',
                        'precision' => 6,
                    ],
                    [
                        'code' => 'PT',
                        'name' => 'Pint (US)',
                        'symbol' => 'pt',
                        'conversion_factor' => '0.473176473',
                        'precision' => 6,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function defaultGroupDefinitions(): array
    {
        return [
            'metric-system' => [
                'name' => 'Metric System',
                'description' => 'International System of Units examples.',
                'unit_codes' => ['KG', 'G', 'MG', 'M', 'CM', 'MM', 'KM', 'L', 'ML'],
            ],
            'imperial-system' => [
                'name' => 'Imperial System',
                'description' => 'Imperial and US customary units.',
                'unit_codes' => ['LB', 'OZ', 'FT', 'IN', 'GAL', 'PT'],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function defaultPackagingDefinitions(): array
    {
        return [
            [
                'base_unit_code' => 'G',
                'package_unit_code' => 'KG',
                'quantity' => 1000,
                'label' => '1 kilogram pack',
            ],
            [
                'base_unit_code' => 'ML',
                'package_unit_code' => 'L',
                'quantity' => 1000,
                'label' => '1 litre bottle',
            ],
        ];
    }
}
