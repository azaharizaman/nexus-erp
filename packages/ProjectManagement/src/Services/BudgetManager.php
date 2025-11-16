<?php

namespace Nexus\ProjectManagement\Services;

use Nexus\ProjectManagement\Contracts\ProjectInterface;
use Nexus\ProjectManagement\Contracts\TimesheetRepositoryInterface;
use Nexus\ProjectManagement\Contracts\ExpenseRepositoryInterface;

class BudgetManager
{
    private TimesheetRepositoryInterface $timesheetRepository;
    private ExpenseRepositoryInterface $expenseRepository;
    private \Nexus\ProjectManagement\Contracts\BillingRateProviderInterface $billingProvider;

    public function __construct(
        TimesheetRepositoryInterface $timesheetRepository,
        ExpenseRepositoryInterface $expenseRepository,
        \Nexus\ProjectManagement\Contracts\BillingRateProviderInterface $billingProvider
    ) {
        $this->timesheetRepository = $timesheetRepository;
        $this->expenseRepository = $expenseRepository;
        $this->billingProvider = $billingProvider;
    }

    public function calculateActualCost(ProjectInterface $project): float
    {
        $timesheets = $this->timesheetRepository->findByProject($project->getId());
        $laborCost = array_reduce($timesheets, function ($carry, $t) {
            $rate = $this->billingProvider->getHourlyRateForUser($t->getUserId());
            return $carry + ($t->getHours() * $rate);
        }, 0.0);
        $expenses = $this->expenseRepository->findByProject($project->getId());
        $expenseCost = array_sum(array_map(fn($e) => $e->getAmount(), $expenses));
        return $laborCost + $expenseCost;
    }

    public function getBudgetVariance(ProjectInterface $project): float
    {
        $actual = $this->calculateActualCost($project);
        return $project->getBudget() - $actual;
    }
}