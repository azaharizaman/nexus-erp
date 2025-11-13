<?php

namespace Nexus\Uom\Services;

use Nexus\Uom\Contracts\CustomUnitRegistrar;
use Nexus\Uom\Exceptions\ConversionException;
use Nexus\Uom\Models\UomCustomConversion;
use Nexus\Uom\Models\UomCustomUnit;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DefaultCustomUnitRegistrar implements CustomUnitRegistrar
{
    private bool $allowFormulas;

    private int $scale;

    public function __construct(
        private readonly DatabaseManager $database,
        Repository $config
    ) {
        $this->allowFormulas = (bool) $config->get('uom.conversion.allow_custom_formulas', false);
        $mathScale = (int) $config->get('uom.conversion.math_scale', 12);
        $this->scale = max($mathScale, 12);
    }

    public function register(array $attributes, Model|array|null $owner = null, array $customConversions = []): UomCustomUnit
    {
        $payload = $this->prepareAttributes($attributes, $owner);

        return $this->database->connection()->transaction(function () use ($payload, $customConversions): UomCustomUnit {
            $this->assertCodeIsAvailable($payload['code'], $payload['owner_type'], $payload['owner_id']);

            $unit = UomCustomUnit::query()->create($payload);

            $this->persistConversions($unit, $customConversions);

            return $unit->refresh();
        });
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function prepareAttributes(array $attributes, Model|array|null $owner): array
    {
        $code = strtoupper(trim((string) ($attributes['code'] ?? '')));

        if ($code === '') {
            throw new \InvalidArgumentException('Custom units require a non-empty code.');
        }

        $name = trim((string) ($attributes['name'] ?? ''));

        if ($name === '') {
            throw new \InvalidArgumentException('Custom units require a non-empty name.');
        }

        $payload = [
            'code' => $code,
            'name' => $name,
            'symbol' => $attributes['symbol'] ?? null,
            'description' => $attributes['description'] ?? null,
            'uom_type_id' => $attributes['uom_type_id'] ?? null,
            'metadata' => $this->normaliseMetadata($attributes['metadata'] ?? null),
        ];

        if (array_key_exists('conversion_factor', $attributes)) {
            $payload['conversion_factor'] = $this->decimalString($attributes['conversion_factor'], $code, true);
        }

        $ownerContext = $this->resolveOwner($owner, $attributes);

        $payload['owner_type'] = $ownerContext['owner_type'];
        $payload['owner_id'] = $ownerContext['owner_id'];

        return $payload;
    }

    /**
     * @param array<string, mixed>|null $attributes
     * @return array{owner_type: ?string, owner_id: ?int}
     */
    private function resolveOwner(Model|array|null $owner, ?array $attributes): array
    {
        if ($owner instanceof Model) {
            return [
                'owner_type' => $owner->getMorphClass(),
                'owner_id' => $this->normalizeOwnerId($owner->getKey()),
            ];
        }

        if (is_array($owner)) {
            return [
                'owner_type' => $owner['owner_type'] ?? $owner['type'] ?? null,
                'owner_id' => $this->normalizeOwnerId($owner['owner_id'] ?? $owner['id'] ?? null),
            ];
        }

        if (is_array($attributes)) {
            return [
                'owner_type' => $attributes['owner_type'] ?? null,
                'owner_id' => $this->normalizeOwnerId($attributes['owner_id'] ?? null),
            ];
        }

        return ['owner_type' => null, 'owner_id' => null];
    }

    private function assertCodeIsAvailable(string $code, ?string $ownerType, ?int $ownerId): void
    {
        $query = UomCustomUnit::query()->whereRaw('LOWER(code) = ?', [Str::lower($code)]);

        if ($ownerType === null) {
            $query->whereNull('owner_type')->whereNull('owner_id');
        } else {
            $query->where('owner_type', $ownerType)->where('owner_id', $ownerId);
        }

        if ($query->exists()) {
            throw ConversionException::customUnitConflict($code);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $definitions
     */
    private function persistConversions(UomCustomUnit $unit, array $definitions): void
    {
        if ($definitions === []) {
            return;
        }

        foreach ($definitions as $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $target = $this->resolveTargetUnit($definition['target'] ?? $definition['target_custom_unit_id'] ?? $definition['target_unit'] ?? null, $unit);

            if ($target->is($unit)) {
                continue;
            }

            $formula = isset($definition['formula']) ? trim((string) $definition['formula']) : null;

            if ($formula !== null && $formula !== '' && ! $this->allowFormulas) {
                throw ConversionException::customFormulaNotAllowed();
            }

            $isLinear = (bool) ($definition['is_linear'] ?? true);
            $factorDecimal = $this->decimalValue($definition['factor'] ?? '1');
            $factorString = $factorDecimal->toScale($this->scale, RoundingMode::HALF_UP)->__toString();

            if ($isLinear && $factorDecimal->isZero()) {
                throw ConversionException::customConversionHasZeroFactor($unit->code, $target->code);
            }

            $offsetString = $this->decimalValue($definition['offset'] ?? '0')->toScale($this->scale, RoundingMode::HALF_UP)->__toString();

            $conversion = new UomCustomConversion([
                'source_custom_unit_id' => $unit->getKey(),
                'target_custom_unit_id' => $target->getKey(),
                'formula' => $formula ?: null,
                'factor' => $factorString,
                'offset' => $offsetString,
                'is_linear' => $isLinear,
                'metadata' => $this->normaliseMetadata($definition['metadata'] ?? null),
            ]);

            $conversion->save();
        }
    }

    private function resolveTargetUnit(mixed $target, UomCustomUnit $source): UomCustomUnit
    {
        if ($target instanceof UomCustomUnit) {
            return $target;
        }

        if (is_int($target) || (is_string($target) && ctype_digit($target))) {
            $model = UomCustomUnit::query()->find((int) $target);
        } elseif (is_string($target)) {
            $model = UomCustomUnit::query()
                ->whereRaw('LOWER(code) = ?', [Str::lower($target)])
                ->when($source->owner_type, function ($query) use ($source): void {
                    $query->where('owner_type', $source->owner_type)->where('owner_id', $source->owner_id);
                }, function ($query): void {
                    $query->whereNull('owner_type')->whereNull('owner_id');
                })
                ->first();
        } else {
            $model = null;
        }

        if (! $model) {
            throw ConversionException::customUnitNotFound((string) ($target ?? ''));
        }

        return $model;
    }

    private function decimalString(mixed $value, string $code, bool $enforceNonZero): string
    {
        $decimal = $this->decimalValue($value);

        if ($enforceNonZero && $decimal->isZero()) {
            throw ConversionException::customUnitHasZeroFactor($code);
        }

        return $decimal->toScale($this->scale, RoundingMode::HALF_UP)->__toString();
    }

    private function decimalValue(mixed $value): BigDecimal
    {
        try {
            return BigDecimal::of($value ?? '0');
        } catch (MathException $exception) {
            throw ConversionException::invalidInput($value, $exception);
        }
    }

    private function normalizeOwnerId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function normaliseMetadata(mixed $metadata): ?array
    {
        if ($metadata === null) {
            return null;
        }

        if (is_array($metadata)) {
            return $metadata;
        }

        if (is_object($metadata)) {
            return (array) $metadata;
        }

        return null;
    }
}
