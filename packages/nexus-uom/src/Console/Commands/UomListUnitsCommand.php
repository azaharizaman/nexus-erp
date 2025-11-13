<?php

namespace Nexus\Uom\Console\Commands;

use Nexus\Uom\Models\UomAlias;
use Nexus\Uom\Models\UomType;
use Nexus\Uom\Models\UomUnit;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UomListUnitsCommand extends Command
{
    protected $signature = 'uom:units
        {type? : Optional unit type code, name, or ID to filter by}
        {--aliases : Include aliases in the output table}';

    protected $description = 'Display registered UOM units with optional type filtering and alias information.';

    public function handle(): int
    {
        $typeArgument = $this->argument('type');
        $type = $typeArgument ? $this->resolveType((string) $typeArgument) : null;

        if ($typeArgument && ! $type) {
            $this->error("Unable to resolve unit type '{$typeArgument}'.");

            return self::FAILURE;
        }

        $query = UomUnit::query()->with('type')->orderBy('uom_type_id')->orderBy('code');

        if ($type) {
            $query->where('uom_type_id', $type->getKey());
        }

        $units = $query->get();

        if ($units->isEmpty()) {
            $this->warn('No units found matching the provided filters.');

            return self::SUCCESS;
        }

        $includeAliases = (bool) $this->option('aliases');
    $headers = ['Code', 'Name', 'Type', 'Base', 'Factor', 'Offset'];

        if ($includeAliases) {
            $headers[] = 'Aliases';
        }

        $rows = $units->map(function (UomUnit $unit) use ($includeAliases): array {
            $row = [
                $unit->code,
                $unit->name,
                $unit->type?->code ?? 'N/A',
                $unit->is_base ? 'yes' : 'no',
                $unit->conversion_factor ?? '1',
                $unit->offset ?? '0',
            ];

            if ($includeAliases) {
                $row[] = $this->aliasesForUnit($unit)->implode(', ');
            }

            return $row;
        });

        $this->table($headers, $rows->toArray());

        return self::SUCCESS;
    }

    private function resolveType(string $value): ?UomType
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (ctype_digit($trimmed)) {
            $type = UomType::query()->find((int) $trimmed);

            if ($type) {
                return $type;
            }
        }

        $lower = Str::lower($trimmed);

        return UomType::query()
            ->whereRaw('LOWER(code) = ?', [$lower])
            ->orWhereRaw('LOWER(name) = ?', [$lower])
            ->first();
    }

    private function aliasesForUnit(UomUnit $unit): Collection
    {
        $aliases = UomAlias::query()
            ->where('unit_id', $unit->getKey())
            ->orderByDesc('is_preferred')
            ->orderBy('alias')
            ->pluck('alias');

        return $aliases->map(static fn (string $alias): string => $alias);
    }
}
