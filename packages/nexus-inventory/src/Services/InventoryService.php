<?php

namespace Nexus\Inventory\Services;

use Brick\Math\BigDecimal;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Brick\Math\RoundingMode;
use InvalidArgumentException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Config as ConfigFacade;
use Nexus\Inventory\Contracts\Item as ItemContract;
use Nexus\Inventory\Exceptions\InsufficientStockException;
use Nexus\Inventory\Contracts\Location as LocationContract;

class InventoryService
{
    public function __construct(protected Container $container)
    {
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getStockLevel(ItemContract $item, ?LocationContract $location = null): string
    {
        $stockModel = $this->makeStockModel();

        $query = $stockModel->newQuery()
            ->where('itemable_type', $this->getMorphClass($item))
            ->where('itemable_id', $this->getModelKey($item));

        if ($location) {
            $query->where('location_id', $this->getModelKey($location));

            $stock = $query->first();

            if (! $stock) {
                return $this->formatQuantity(BigDecimal::of('0'));
            }

            return $this->formatQuantity(BigDecimal::of($stock->quantity));
        }

        $sum = $query->pluck('quantity')->reduce(function (BigDecimal $carry, $quantity) {
            return $carry->plus(BigDecimal::of($quantity));
        }, BigDecimal::of('0'));

        return $this->formatQuantity($sum);
    }

    public function addStock(ItemContract $item, LocationContract $location, string|int|float $quantity, array $context = []): Model
    {
        $delta = $this->toDecimal($quantity);

        if ($delta->isNegative() || $delta->isZero()) {
            throw new InvalidArgumentException('Quantity for addStock must be a positive value.');
        }

        return $this->recordMovement($item, $location, $delta, 'stock_in', $context, $context['reason'] ?? null);
    }

    public function removeStock(ItemContract $item, LocationContract $location, string|int|float $quantity, array $context = []): Model
    {
        $delta = $this->toDecimal($quantity);

        if ($delta->isNegative() || $delta->isZero()) {
            throw new InvalidArgumentException('Quantity for removeStock must be a positive value.');
        }

        return $this->recordMovement($item, $location, $delta->negated(), 'stock_out', $context, $context['reason'] ?? null);
    }

    public function moveStock(ItemContract $item, LocationContract $location, string|int|float $quantity, array $context = []): Model
    {
        $delta = $this->toDecimal($quantity);

        if ($delta->isZero()) {
            throw new InvalidArgumentException('Quantity for moveStock must not be zero.');
        }

        $transactionType = $delta->isNegative() ? 'stock_out' : 'stock_in';

        return $this->recordMovement($item, $location, $delta, $transactionType, $context, $context['reason'] ?? null);
    }

    public function adjustStock(ItemContract $item, LocationContract $location, string|int|float $quantity, array $context = []): Model
    {
        $delta = $this->toDecimal($quantity);
        $absolute = Arr::get($context, 'absolute', false);

        return DB::transaction(function () use ($item, $location, $delta, $absolute, $context) {
            $stock = $this->lockStock($item, $location);
            $current = BigDecimal::of($stock->quantity);

            $change = $absolute
                ? $delta->minus($current)
                : $delta;

            if ($change->isZero()) {
                return $stock->movements()->latest()->first() ?? $stock;
            }

            $basePayload = $this->buildBaseTransactionData($stock, $context);

            $movement = $this->applyMovementWithTransaction(
                $item,
                $location,
                $stock,
                $change,
                $this->createTransaction('stock_adjustment', $this->buildAdjustmentPayload($stock, $context, $basePayload)),
                $context['reason'] ?? 'Manual adjustment',
                $context['movement'] ?? []
            );

            return $movement;
        });
    }

    public function transferStock(ItemContract $item, LocationContract $from, LocationContract $to, string|int|float $quantity, array $context = []): array
    {
        $delta = $this->toDecimal($quantity);

        if ($delta->isNegative() || $delta->isZero()) {
            throw new InvalidArgumentException('Quantity for transferStock must be a positive value.');
        }

        return DB::transaction(function () use ($item, $from, $to, $delta, $context) {
            $transfer = $this->createTransaction('stock_transfer', $this->buildTransferPayload($from, $to, $context));

            $outgoing = $this->applyMovementWithTransaction(
                $item,
                $from,
                $this->lockStock($item, $from),
                $delta->negated(),
                $transfer,
                $context['reason'] ?? 'Transfer out',
                $context['movement_out'] ?? []
            );

            $incoming = $this->applyMovementWithTransaction(
                $item,
                $to,
                $this->lockStock($item, $to),
                $delta,
                $transfer,
                $context['reason'] ?? 'Transfer in',
                $context['movement_in'] ?? []
            );

            return [
                'transfer' => $transfer->fresh(),
                'outgoing' => $outgoing->fresh(),
                'incoming' => $incoming->fresh(),
            ];
        });
    }

    public function openingBalance(ItemContract $item, LocationContract $location, string|int|float $quantity, array $context = []): Model
    {
        $delta = $this->toDecimal($quantity);

        return DB::transaction(function () use ($item, $location, $delta, $context) {
            $stock = $this->lockStock($item, $location);
            $current = BigDecimal::of($stock->quantity);

            if (! $current->isZero()) {
                throw new InvalidArgumentException('Opening balance can only be recorded when current quantity is zero.');
            }

            $basePayload = $this->buildBaseTransactionData($stock, $context);

            $movement = $this->applyMovementWithTransaction(
                $item,
                $location,
                $stock,
                $delta,
                $this->createTransaction('opening_balance', $this->buildOpeningBalancePayload($stock, $context, $basePayload)),
                $context['reason'] ?? 'Opening balance',
                $context['movement'] ?? []
            );

            return $movement;
        });
    }

    protected function recordMovement(ItemContract $item, LocationContract $location, BigDecimal $change, string $transactionType, array $context, ?string $reason): Model
    {
        return DB::transaction(function () use ($item, $location, $change, $transactionType, $context, $reason) {
            $stock = $this->lockStock($item, $location);

            $transaction = $this->createTransaction(
                $transactionType,
                $this->buildTransactionPayload($transactionType, $stock, $change, $context)
            );

            return $this->applyMovementWithTransaction(
                $item,
                $location,
                $stock,
                $change,
                $transaction,
                $reason,
                $context['movement'] ?? []
            );
        });
    }

    protected function applyMovementWithTransaction(ItemContract $item, LocationContract $location, Model $stock, BigDecimal $change, Model $transaction, ?string $reason, array $movementOverrides = []): Model
    {
        $before = BigDecimal::of($stock->quantity);
        $after = $before->plus($change);

        if ($after->isNegative()) {
            throw new InsufficientStockException(
                sprintf(
                    'Insufficient stock for item %s at location %s. Available: %s, Required change: %s.',
                    $this->describeModel($item),
                    $this->describeModel($location),
                    $this->formatQuantity($before),
                    $this->formatQuantity($change->abs())
                ),
                $this->describeModel($item),
                $this->describeModel($location)
            );
        }

        $stock->quantity = $this->formatQuantity($after);
        $stock->save();

        $movement = $this->makeStockMovementModel();
        $movement->fill(array_merge([
            'quantity_before' => $this->formatQuantity($before),
            'quantity_change' => $this->formatQuantity($change),
            'quantity_after' => $this->formatQuantity($after),
            'reason' => $reason,
            'serial_number' => $movementOverrides['serial_number'] ?? $this->generateSerialNumber(),
        ], Arr::except($movementOverrides, ['serial_number', 'transactionable_id', 'transactionable_type', 'stock_id'])));

        $movement->stock()->associate($stock);
        $movement->transactionable()->associate($transaction);
        $movement->save();

        return $movement->fresh();
    }

    protected function buildTransactionPayload(string $type, Model $stock, BigDecimal $change, array $context): array
    {
        $base = $this->buildBaseTransactionData($stock, $context);

        return match ($type) {
            'stock_in' => $this->buildStockInPayload($base, $change, $context),
            'stock_out' => $this->buildStockOutPayload($base, $change, $context),
            'stock_adjustment' => $this->buildAdjustmentPayload($stock, $context, $base),
            'opening_balance' => $this->buildOpeningBalancePayload($stock, $context, $base),
            default => $base,
        };
    }

    protected function buildBaseTransactionData(Model $stock, array $context): array
    {
        $base = array_merge(['stock_id' => $stock->getKey()], Arr::get($context, 'transaction', []));

        $reference = Arr::get($context, 'reference');

        if ($reference instanceof Model) {
            $base['reference_type'] = $reference->getMorphClass();
            $base['reference_id'] = $reference->getKey();
        }

        return $base;
    }

    protected function buildStockInPayload(array $base, BigDecimal $change, array $context): array
    {
        $base['expected_quantity'] = $this->formatQuantity($change->abs());
        $base['received_at'] = Arr::get($context, 'transaction.received_at', Carbon::now());

        return $base;
    }

    protected function buildStockOutPayload(array $base, BigDecimal $change, array $context): array
    {
        $base['expected_quantity'] = $this->formatQuantity($change->abs());
        $base['dispatched_at'] = Arr::get($context, 'transaction.dispatched_at', Carbon::now());

        return $base;
    }

    protected function buildAdjustmentPayload(Model $stock, array $context, array $base = []): array
    {
        $base = array_merge($base, Arr::get($context, 'transaction', []));
        $base['stock_id'] = $stock->getKey();
        $base['adjusted_at'] = Arr::get($context, 'transaction.adjusted_at', Carbon::now());

        if ($actor = Arr::get($context, 'adjusted_by')) {
            if ($actor instanceof Model) {
                $base['adjusted_by_type'] = $actor->getMorphClass();
                $base['adjusted_by_id'] = $actor->getKey();
            }
        }

        return $base;
    }

    protected function buildOpeningBalancePayload(Model $stock, array $context, array $base = []): array
    {
        $base = array_merge($base, Arr::get($context, 'transaction', []));
        $base['stock_id'] = $stock->getKey();
        $base['recorded_at'] = Arr::get($context, 'transaction.recorded_at', Carbon::now());

        return $base;
    }

    protected function buildTransferPayload(LocationContract $from, LocationContract $to, array $context): array
    {
        $base = Arr::get($context, 'transaction', []);
        $base['source_location_id'] = $this->getModelKey($from);
        $base['destination_location_id'] = $this->getModelKey($to);
        $base['initiated_at'] = Arr::get($context, 'transaction.initiated_at', Carbon::now());

        if ($initiator = Arr::get($context, 'initiated_by')) {
            if ($initiator instanceof Model) {
                $base['initiated_by_type'] = $initiator->getMorphClass();
                $base['initiated_by_id'] = $initiator->getKey();
            }
        }

        if ($reference = Arr::get($context, 'reference')) {
            if ($reference instanceof Model) {
                $base['reference_type'] = $reference->getMorphClass();
                $base['reference_id'] = $reference->getKey();
            }
        }

        return $base;
    }

    protected function lockStock(ItemContract $item, LocationContract $location): Model
    {
        $stockModel = $this->makeStockModel();

        $query = $stockModel->newQuery()
            ->where('itemable_type', $this->getMorphClass($item))
            ->where('itemable_id', $this->getModelKey($item))
            ->where('location_id', $this->getModelKey($location));

        $stock = $query->lockForUpdate()->first();

        if (! $stock) {
            $stock = $this->makeStockModel();
            $stock->fill([
                'itemable_type' => $this->getMorphClass($item),
                'itemable_id' => $this->getModelKey($item),
                'location_id' => $this->getModelKey($location),
                'quantity' => $this->formatQuantity(BigDecimal::of('0')),
            ]);
            $stock->save();

            $stock = $query->lockForUpdate()->firstOrFail();
        }

        return $stock;
    }

    protected function makeStockModel(): Model
    {
        $class = ConfigFacade::get('inventory-management.models.stock');

        return $this->container->make($class);
    }

    protected function makeStockMovementModel(): Model
    {
        $class = ConfigFacade::get('inventory-management.models.stock_movement');

        return $this->container->make($class);
    }

    protected function createTransaction(string $type, array $attributes): Model
    {
        $class = $this->resolveTransactionClass($type);
        /** @var Model $transaction */
        $transaction = $this->container->make($class);
        $transaction->fill($attributes);
        $transaction->save();

        return $transaction;
    }

    protected function resolveTransactionClass(string $type): string
    {
        $class = ConfigFacade::get("inventory-management.models.transactions.{$type}");

        if (! $class) {
            throw new InvalidArgumentException("Unknown inventory transaction type [{$type}].");
        }

        return $class;
    }

    protected function generateSerialNumber(): string
    {
        $configBinding = ConfigFacade::get('inventory-management.serial_number_generator_binding');
        $binding = is_array($configBinding)
            ? Arr::get($configBinding, 'binding')
            : $configBinding;

        if (is_string($binding) && $this->container->bound($binding)) {
            $generator = $this->container->make($binding);

            if (method_exists($generator, 'generate')) {
                return (string) $generator->generate(ConfigFacade::get('inventory-management.serial_numbering_key'));
            }
        }

        if ($this->container->bound('serial-number.generator')) {
            $generator = $this->container->make('serial-number.generator');

            if (method_exists($generator, 'generate')) {
                return (string) $generator->generate(ConfigFacade::get('inventory-management.serial_numbering_key'));
            }
        }

        return Str::uuid()->toString();
    }

    protected function toDecimal(string|int|float $value): BigDecimal
    {
        return BigDecimal::of((string) $value);
    }

    protected function formatQuantity(BigDecimal $value): string
    {
        return $value->toScale($this->quantityPrecision(), RoundingMode::HALF_UP)->__toString();
    }

    protected function quantityPrecision(): int
    {
        return (int) ConfigFacade::get('inventory-management.quantity_precision', 4);
    }

    protected function getMorphClass(object $model): string
    {
        if ($model instanceof Model) {
            return $model->getMorphClass();
        }

        return Relation::getMorphedModel(get_class($model)) ?? get_class($model);
    }

    protected function getModelKey(object $model): mixed
    {
        if ($model instanceof Model) {
            return $model->getKey();
        }

        if (method_exists($model, 'getKey')) {
            return $model->getKey();
        }

        throw new InvalidArgumentException('Provided object does not expose a primary key.');
    }

    protected function describeModel(object $model): string
    {
        $class = get_class($model);
        $identifier = method_exists($model, 'getKey') ? $model->getKey() : spl_object_hash($model);

        if (method_exists($model, 'getSku')) {
            return sprintf('%s (SKU: %s)', $class, $model->getSku());
        }

        if (method_exists($model, 'getLocationName')) {
            return sprintf('%s (%s)', $class, $model->getLocationName());
        }

        return sprintf('%s#%s', $class, $identifier);
    }
}
