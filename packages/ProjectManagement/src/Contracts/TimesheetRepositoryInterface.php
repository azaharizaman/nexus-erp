<?php

namespace Nexus\ProjectManagement\Contracts;

interface TimesheetRepositoryInterface
{
    public function create(array $data): TimesheetInterface;
    public function findById(int $id): ?TimesheetInterface;
    public function findByUser(int $userId, \DateTime $start, \DateTime $end): array;
    public function findByTask(int $taskId): array;
    public function findByProject(int $projectId): array;
    public function update(TimesheetInterface $timesheet, array $data): bool;
    public function delete(TimesheetInterface $timesheet): bool;
    public function approve(TimesheetInterface $timesheet): bool;
    public function reject(TimesheetInterface $timesheet, string $reason): bool;
}