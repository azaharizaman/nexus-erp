<?php

namespace Nexus\Atomy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\ProjectManagement\Contracts\MilestoneInterface;

class Milestone extends Model implements MilestoneInterface
{
    protected $fillable = [
        'project_id', 'name', 'description', 'due_date', 'status',
        'deliverables', 'rejection_reason', 'approved_by', 'approved_at', 'tenant_id'
    ];

    protected $casts = [
        'due_date' => 'date',
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDueDate(): \DateTime
    {
        return new \DateTime($this->due_date);
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getDeliverables(): ?string
    {
        return $this->deliverables;
    }
}