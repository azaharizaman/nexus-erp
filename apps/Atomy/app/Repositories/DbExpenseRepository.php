<?php

namespace Nexus\Atomy\Repositories;

use Nexus\ProjectManagement\Contracts\ExpenseRepositoryInterface;
use Nexus\ProjectManagement\Contracts\ExpenseInterface;
use Nexus\Atomy\Models\Expense;

class DbExpenseRepository implements ExpenseRepositoryInterface
{
    public function create(array $data): ExpenseInterface
    {
        return Expense::create($data);
    }

    public function findById(int $id): ?ExpenseInterface
    {
        return Expense::find($id);
    }

    public function findByProject(int $projectId): array
    {
        return Expense::where('project_id', $projectId)->get()->all();
    }

    public function update(ExpenseInterface $expense, array $data): bool
    {
        return Expense::where('id', $expense->getId())->update($data) > 0;
    }

    public function delete(ExpenseInterface $expense): bool
    {
        return Expense::where('id', $expense->getId())->delete() > 0;
    }

    public function approve(ExpenseInterface $expense): bool
    {
        return $this->update($expense, ['status' => 'approved', 'approved_at' => now()]);
    }

    public function reject(ExpenseInterface $expense, string $reason): bool
    {
        return $this->update($expense, ['status' => 'rejected', 'rejection_reason' => $reason]);
    }
}