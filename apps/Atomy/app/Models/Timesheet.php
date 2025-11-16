<?php

namespace Nexus\Atomy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\ProjectManagement\Contracts\TimesheetInterface;

class Timesheet extends Model implements TimesheetInterface
{
    protected $fillable = [
        'task_id', 'user_id', 'date', 'hours', 'description',
        'billable', 'status', 'rejection_reason', 'approved_by', 'approved_at', 'tenant_id'
    ];

    protected $casts = [
        'date' => 'date',
        'hours' => 'decimal:2',
        'billable' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Implement interface
    public function getId(): int
    {
        return $this->id;
    }

    public function getTaskId(): int
    {
        return $this->task_id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getDate(): \DateTime
    {
        return new \DateTime($this->date);
    }

    public function getHours(): float
    {
        return $this->hours;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isBillable(): bool
    {
        return $this->billable;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}