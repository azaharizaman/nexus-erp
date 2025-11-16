<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Models;

use Nexus\Sequencing\Enums\ResetPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sequence Model
 *
 * Represents a serial number sequence configuration.
 *
 * @property int $id
 * @property string $tenant_id
 * @property string $sequence_name
 * @property string $pattern
 * @property ResetPeriod $reset_period
 * @property int $padding
 * @property int $step_size
 * @property int|null $reset_limit
 * @property int $current_value
 * @property \Carbon\Carbon|null $last_reset_at
 * @property array|null $metadata
 * @property int $version
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Sequence extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'serial_number_sequences';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'sequence_name',
        'pattern',
        'reset_period',
        'padding',
        'step_size',
        'reset_limit',
        'current_value',
        'last_reset_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'reset_period' => ResetPeriod::class,
        'padding' => 'integer',
        'step_size' => 'integer',
        'reset_limit' => 'integer',
        'current_value' => 'integer',
        'last_reset_at' => 'datetime',
        'metadata' => 'array',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the logs for this sequence.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(SerialNumberLog::class, 'sequence_name', 'sequence_name')
            ->where('tenant_id', $this->tenant_id);
    }

    /**
     * Determine if sequence should reset based on reset period.
     *
     * @return bool
     */
    public function shouldReset(): bool
    {
        if ($this->reset_period === ResetPeriod::NEVER || $this->last_reset_at === null) {
            return false;
        }

        $now = now();

        return match ($this->reset_period) {
            ResetPeriod::DAILY => ! $this->last_reset_at->isToday(),
            ResetPeriod::MONTHLY => ! $this->last_reset_at->isSameMonth($now),
            ResetPeriod::YEARLY => ! $this->last_reset_at->isSameYear($now),
            ResetPeriod::NEVER => false,
        };
    }

    /**
     * Determine if sequence should reset based on reset limit (count).
     *
     * @return bool
     */
    public function shouldResetByLimit(): bool
    {
        return $this->reset_limit !== null && $this->current_value >= $this->reset_limit;
    }

    /**
     * Determine if sequence should reset (time-based OR count-based).
     *
     * @return bool
     */
    public function shouldResetAny(): bool
    {
        return $this->shouldReset() || $this->shouldResetByLimit();
    }

    /**
     * Calculate remaining count until next reset.
     *
     * @return int|null Returns null if no reset limit configured
     */
    public function getRemainingUntilReset(): ?int
    {
        if ($this->reset_limit === null) {
            return null;
        }

        return max(0, $this->reset_limit - $this->current_value);
    }

    /**
     * Get the next counter value that will be assigned.
     *
     * @return int
     */
    public function getNextValue(): int
    {
        return $this->current_value + $this->step_size;
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $sequence) {
            // Validate step_size
            if ($sequence->step_size <= 0) {
                throw new \InvalidArgumentException('Step size must be greater than 0');
            }

            // Validate reset_limit
            if ($sequence->reset_limit !== null && $sequence->reset_limit <= 0) {
                throw new \InvalidArgumentException('Reset limit must be greater than 0 or null');
            }
        });

        static::updating(function (self $sequence) {
            // Validate step_size
            if ($sequence->step_size <= 0) {
                throw new \InvalidArgumentException('Step size must be greater than 0');
            }

            // Validate reset_limit
            if ($sequence->reset_limit !== null && $sequence->reset_limit <= 0) {
                throw new \InvalidArgumentException('Reset limit must be greater than 0 or null');
            }
        });
    }
}
