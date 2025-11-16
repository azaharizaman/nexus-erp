<?php

namespace Nexus\Atomy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\ProjectManagement\Contracts\TaskDependencyInterface;

class TaskDependency extends Model implements TaskDependencyInterface
{
    protected $fillable = ['task_id', 'depends_on_task_id'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'depends_on_task_id');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTaskId(): int
    {
        return $this->task_id;
    }

    public function getDependsOnTaskId(): int
    {
        return $this->depends_on_task_id;
    }
}