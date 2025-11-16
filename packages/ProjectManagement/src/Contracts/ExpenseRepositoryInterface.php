<?php

namespace Nexus\ProjectManagement\Contracts;

interface ExpenseRepositoryInterface
{
    public function create(array $data): ExpenseInterface;
    public function findById(int $id): ?ExpenseInterface;
    public function findByProject(int $projectId): array;
    public function update(ExpenseInterface $expense, array $data): bool;
    public function delete(ExpenseInterface $expense): bool;
    public function approve(ExpenseInterface $expense): bool;
    public function reject(ExpenseInterface $expense, string $reason): bool;
}