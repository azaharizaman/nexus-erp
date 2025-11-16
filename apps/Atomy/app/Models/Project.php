<?php

namespace Nexus\Atomy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\ProjectManagement\Contracts\ProjectInterface;

class Project extends Model implements ProjectInterface
{
    protected $fillable = [
        'name', 'description', 'client_id', 'project_manager_id',
        'start_date', 'end_date', 'status', 'budget', 'tenant_id'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ResourceAllocation::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // Implement interface
    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getClientId(): ?int
    {
        return $this->client_id;
    }

    public function getProjectManagerId(): int
    {
        return $this->project_manager_id;
    }

    public function getStartDate(): \DateTime
    {
        return new \DateTime($this->start_date);
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->end_date ? new \DateTime($this->end_date) : null;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getBudget(): ?float
    {
        return $this->budget;
    }

    public function getTenantId(): int
    {
        return $this->tenant_id;
    }
}