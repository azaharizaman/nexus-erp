<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Nexus\Atomy\Models\Timesheet;

class TimesheetFactory extends Factory
{
    protected $model = Timesheet::class;

    public function definition()
    {
        return [
            'task_id' => 1,
            'user_id' => 1,
            'date' => now()->toDateString(),
            'hours' => 8.00,
            'description' => 'Work done',
            'billable' => true,
            'status' => 'pending',
            'tenant_id' => 1,
        ];
    }
}