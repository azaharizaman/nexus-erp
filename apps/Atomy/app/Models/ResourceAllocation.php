<?php

namespace Nexus\Atomy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\ProjectManagement\Contracts\ResourceAllocationInterface;

class ResourceAllocation extends Model implements ResourceAllocationInterface
{
    protected $fillable = [
        'project_id', 'user_id', 'allocation_percentage', 'start_date', 'end_date', 'tenant_id'
    ];

    protected $casts = [
        'allocation_percentage' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getAllocationPercentage(): float
    {
        return $this->allocation_percentage;
    }

    public function getStartDate(): \DateTime
    {
        return new \DateTime($this->start_date);
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->end_date ? new \DateTime($this->end_date) : null;
    }
}