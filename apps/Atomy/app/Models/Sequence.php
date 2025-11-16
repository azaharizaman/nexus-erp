<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Sequencing\Contracts\SequenceInterface;
use Nexus\Sequencing\Enums\ResetPeriod;

/**
 * Sequence Model (Atomy Implementation)
 *
 * Eloquent model implementing the SequenceInterface from nexus/sequencing package.
 * This is the concrete persistence implementation for the application layer.
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
class Sequence extends Model implements SequenceInterface
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

    // SequenceInterface implementation

    public function getId()
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getSequenceName(): string
    {
        return $this->sequence_name;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getResetPeriod(): string
    {
        return $this->reset_period instanceof ResetPeriod 
            ? $this->reset_period->value 
            : $this->reset_period;
    }

    public function getPadding(): int
    {
        return $this->padding;
    }

    public function getStepSize(): int
    {
        return $this->step_size;
    }

    public function getResetLimit(): ?int
    {
        return $this->reset_limit;
    }

    public function getCurrentValue(): int
    {
        return $this->current_value;
    }

    public function getLastResetAt(): ?\DateTimeInterface
    {
        return $this->last_reset_at;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updated_at;
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
            ResetPeriod::DAILY => $this->last_reset_at->diffInDays($now) >= 1,
            ResetPeriod::MONTHLY => $this->last_reset_at->month !== $now->month 
                || $this->last_reset_at->year !== $now->year,
            ResetPeriod::YEARLY => $this->last_reset_at->year !== $now->year,
            default => false,
        };
    }
}
