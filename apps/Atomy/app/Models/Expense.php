<?php

namespace Nexus\Atomy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\ProjectManagement\Contracts\ExpenseInterface;

class Expense extends Model implements ExpenseInterface
{
    protected $fillable = [
        'project_id', 'description', 'amount', 'date', 'billable',
        'status', 'receipt_path', 'rejection_reason', 'approved_by', 'approved_at', 'tenant_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'billable' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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

    public function getProjectId(): int
    {
        return $this->project_id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDate(): \DateTime
    {
        return new \DateTime($this->date);
    }

    public function isBillable(): bool
    {
        return $this->billable;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getReceiptPath(): ?string
    {
        return $this->receipt_path;
    }
}