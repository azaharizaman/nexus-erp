<?php

namespace Nexus\Atomy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\ProjectManagement\Contracts\TaskInterface;
use Nexus\Atomy\Models\Timesheet;

class Task extends Model implements TaskInterface
{
    protected $fillable = [
        'project_id', 'title', 'description', 'assignee_id',
        'due_date', 'priority', 'status', 'parent_task_id', 'tenant_id'
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getAssigneeId(): ?int
    {
        return $this->assignee_id;
    }

    public function getDueDate(): ?\DateTime
    {
        return $this->due_date ? new \DateTime($this->due_date) : null;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getParentTaskId(): ?int
    {
        return $this->parent_task_id;
    }
}