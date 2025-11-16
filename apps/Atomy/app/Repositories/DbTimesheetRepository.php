<?php

namespace Nexus\Atomy\Repositories;

use Nexus\ProjectManagement\Contracts\TimesheetRepositoryInterface;
use Nexus\ProjectManagement\Contracts\TimesheetInterface;
use Nexus\Atomy\Models\Timesheet;

class DbTimesheetRepository implements TimesheetRepositoryInterface
{
    public function create(array $data): TimesheetInterface
    {
        return Timesheet::create($data);
    }

    public function findById(int $id): ?TimesheetInterface
    {
        return Timesheet::find($id);
    }

    public function findByUser(int $userId, \DateTime $start, \DateTime $end): array
    {
        return Timesheet::where('user_id', $userId)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get()->all();
    }

    public function findByTask(int $taskId): array
    {
        return Timesheet::where('task_id', $taskId)->get()->all();
    }

    public function findByProject(int $projectId): array
    {
        return Timesheet::whereHas('task', fn($q) => $q->where('project_id', $projectId))->get()->all();
    }

    public function update(TimesheetInterface $timesheet, array $data): bool
    {
        return Timesheet::where('id', $timesheet->getId())->update($data) > 0;
    }

    public function delete(TimesheetInterface $timesheet): bool
    {
        return Timesheet::where('id', $timesheet->getId())->delete() > 0;
    }

    public function approve(TimesheetInterface $timesheet): bool
    {
        return $this->update($timesheet, ['status' => 'approved', 'approved_at' => now()]);
    }

    public function reject(TimesheetInterface $timesheet, string $reason): bool
    {
        return $this->update($timesheet, ['status' => 'rejected', 'rejection_reason' => $reason]);
    }
}