<?php

namespace Nexus\Atomy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\ProjectManagement\Contracts\InvoiceInterface;

class Invoice extends Model implements InvoiceInterface
{
    protected $fillable = [
        'project_id', 'invoice_number', 'amount', 'status', 'due_date', 'items', 'tenant_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'items' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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

    public function getInvoiceNumber(): string
    {
        return $this->invoice_number;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getDueDate(): \DateTime
    {
        return new \DateTime($this->due_date);
    }

    public function getItems(): array
    {
        return $this->items;
    }
}