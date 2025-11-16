<?php

namespace Nexus\Uom\Services;

use Nexus\Uom\Contracts\AliasResolver;
use Nexus\Uom\Exceptions\ConversionException;
use Nexus\Uom\Models\UomAlias;
use Nexus\Uom\Models\UomUnit;
use Illuminate\Support\Str;

class DefaultAliasResolver implements AliasResolver
{
    /**
     * @var array<string, UomUnit|null>
     */
    private array $cache = [];

    public function resolve(string $identifier): ?UomUnit
    {
        $normalized = trim($identifier);

        if ($normalized === '') {
            return null;
        }

        $key = Str::lower($normalized);

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $unit = $this->findUnitByCode($normalized) ?? $this->findUnitByAlias($normalized);

        return $this->cache[$key] = $unit;
    }

    public function resolveOrFail(string $identifier): UomUnit
    {
        $unit = $this->resolve($identifier);

        if (! $unit) {
            throw ConversionException::unitNotFound($identifier);
        }

        return $unit;
    }

    public function aliasesFor(UomUnit|string $unit, bool $includeCode = true): array
    {
        if (! $unit instanceof UomUnit) {
            $unit = $this->resolveOrFail($unit);
        }

        $aliases = UomAlias::query()
            ->where('unit_id', $unit->id)
            ->orderByDesc('is_preferred')
            ->orderBy('alias')
            ->pluck('alias')
            ->all();

        if ($includeCode) {
            array_unshift($aliases, $unit->code);
        }

        return array_values(array_unique($aliases));
    }

    private function findUnitByCode(string $identifier): ?UomUnit
    {
        if (is_numeric($identifier)) {
            $candidate = UomUnit::query()->find((int) $identifier);
            if ($candidate) {
                return $candidate;
            }
        }

        return UomUnit::query()
            ->whereRaw('LOWER(code) = ?', [Str::lower($identifier)])
            ->first();
    }

    private function findUnitByAlias(string $identifier): ?UomUnit
    {
        $lower = Str::lower($identifier);

        $alias = UomAlias::query()
            ->whereRaw('LOWER(alias) = ?', [$lower])
            ->first();

        return $alias?->unit;
    }
}
