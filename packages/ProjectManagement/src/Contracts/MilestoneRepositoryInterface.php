<?php

namespace Nexus\ProjectManagement\Contracts;

interface MilestoneRepositoryInterface
{
    public function create(array $data): MilestoneInterface;
    public function findById(int $id): ?MilestoneInterface;
    public function findByProject(int $projectId): array;
    public function update(MilestoneInterface $milestone, array $data): bool;
    public function delete(MilestoneInterface $milestone): bool;
    public function approve(MilestoneInterface $milestone): bool;
    public function reject(MilestoneInterface $milestone, string $reason): bool;
}