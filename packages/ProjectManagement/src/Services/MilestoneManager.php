<?php

namespace Nexus\ProjectManagement\Services;

use Nexus\ProjectManagement\Contracts\MilestoneRepositoryInterface;
use Nexus\ProjectManagement\Contracts\MilestoneInterface;

class MilestoneManager
{
    private MilestoneRepositoryInterface $milestoneRepository;

    public function __construct(MilestoneRepositoryInterface $milestoneRepository)
    {
        $this->milestoneRepository = $milestoneRepository;
    }

    public function createMilestone(array $data): MilestoneInterface
    {
        return $this->milestoneRepository->create($data);
    }

    public function approveMilestone(MilestoneInterface $milestone): bool
    {
        return $this->milestoneRepository->approve($milestone);
    }

    public function rejectMilestone(MilestoneInterface $milestone, string $reason): bool
    {
        return $this->milestoneRepository->reject($milestone, $reason);
    }

    public function getMilestonesByProject(int $projectId): array
    {
        return $this->milestoneRepository->findByProject($projectId);
    }
}