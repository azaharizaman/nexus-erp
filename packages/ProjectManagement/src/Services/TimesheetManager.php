<?php

namespace Nexus\ProjectManagement\Services;

use Nexus\ProjectManagement\Contracts\TimesheetRepositoryInterface;
use Nexus\ProjectManagement\Contracts\TimesheetInterface;
use Nexus\ProjectManagement\Exceptions\TimesheetException;

class TimesheetManager
{
    private TimesheetRepositoryInterface $timesheetRepository;

    public function __construct(TimesheetRepositoryInterface $timesheetRepository)
    {
        $this->timesheetRepository = $timesheetRepository;
    }

    public function logTime(array $data): TimesheetInterface
    {
        // Validate hours, date, etc.
        if ($data['hours'] <= 0) {
            throw new TimesheetException("Hours must be positive");
        }
        return $this->timesheetRepository->create($data);
    }

    public function approveTimesheet(TimesheetInterface $timesheet): bool
    {
        return $this->timesheetRepository->approve($timesheet);
    }

    public function rejectTimesheet(TimesheetInterface $timesheet, string $reason): bool
    {
        return $this->timesheetRepository->reject($timesheet, $reason);
    }

    public function getTimesheetsByUser(int $userId, \DateTime $start, \DateTime $end): array
    {
        return $this->timesheetRepository->findByUser($userId, $start, $end);
    }

    public function getTimesheetsByTask(int $taskId): array
    {
        return $this->timesheetRepository->findByTask($taskId);
    }
}