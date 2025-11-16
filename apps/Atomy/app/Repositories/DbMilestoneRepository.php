<?php

namespace Nexus\Atomy\Repositories;

use Nexus\ProjectManagement\Contracts\MilestoneRepositoryInterface;
use Nexus\ProjectManagement\Contracts\MilestoneInterface;
use Nexus\Atomy\Models\Milestone;

class DbMilestoneRepository implements MilestoneRepositoryInterface
{
    public function create(array $data): MilestoneInterface
    {
        return Milestone::create($data);
    }

    public function findById(int $id): ?MilestoneInterface
    {
        return Milestone::find($id);
    }

    public function findByProject(int $projectId): array
    {
        return Milestone::where('project_id', $projectId)->get()->all();
    }

    public function update(MilestoneInterface $milestone, array $data): bool
    {
        return Milestone::where('id', $milestone->getId())->update($data) > 0;
    }

    public function delete(MilestoneInterface $milestone): bool
    {
        return Milestone::where('id', $milestone->getId())->delete() > 0;
    }

    public function approve(MilestoneInterface $milestone): bool
    {
        return $this->update($milestone, ['status' => 'approved', 'approved_at' => now()]);
    }

    public function reject(MilestoneInterface $milestone, string $reason): bool
    {
        return $this->update($milestone, ['status' => 'rejected', 'rejection_reason' => $reason]);
    }
}