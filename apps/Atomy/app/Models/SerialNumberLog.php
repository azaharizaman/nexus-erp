<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Sequencing\Contracts\SerialNumberLogInterface;

/**
 * Serial Number Log Model (Atomy Implementation)
 *
 * Eloquent model implementing the SerialNumberLogInterface from nexus/sequencing package.
 * This is the concrete persistence implementation for the application layer.
 *
 * @property int $id
 * @property int $sequence_id
 * @property string $generated_number
 * @property int $counter_value
 * @property array|null $context
 * @property string $action_type
 * @property string|null $reason
 * @property int|null $causer_id
 * @property \Carbon\Carbon $created_at
 */
class SerialNumberLog extends Model implements SerialNumberLogInterface
{
    /**
     * Disable updated_at timestamp since logs are immutable.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'serial_number_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'sequence_id',
        'generated_number',
        'counter_value',
        'context',
        'action_type',
        'reason',
        'causer_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sequence_id' => 'integer',
        'counter_value' => 'integer',
        'context' => 'array',
        'causer_id' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        // Automatically set created_at on creation
        static::creating(function (self $log) {
            $log->created_at = $log->freshTimestamp();
        });
    }

    /**
     * Get the sequence this log belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class, 'sequence_id');
    }

    // SerialNumberLogInterface implementation

    public function getId()
    {
        return $this->id;
    }

    public function getSequenceId()
    {
        return $this->sequence_id;
    }

    public function getGeneratedNumber(): string
    {
        return $this->generated_number;
    }

    public function getCounterValue(): int
    {
        return $this->counter_value;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function getActionType(): string
    {
        return $this->action_type;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getCauserId()
    {
        return $this->causer_id;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }
}
