<?php

namespace Nexus\Atomy\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Nexus\Atomy\Http\Controllers\Controller;
use Nexus\ProjectManagement\Services\TimesheetManager;
use Nexus\Atomy\Models\Timesheet;

class TimesheetController extends Controller
{
    private TimesheetManager $manager;

    public function __construct(TimesheetManager $manager)
    {
        $this->manager = $manager;
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'task_id' => 'required|integer',
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'billable' => 'boolean',
        ]);

        $data['user_id'] = $request->user()->id;
        $data['tenant_id'] = $request->user()->tenant_id;

        $ts = $this->manager->logTime($data);
        return response()->json($ts, 201);
    }

    public function approve(int $id): JsonResponse
    {
        $ts = Timesheet::findOrFail($id);
        $this->manager->approveTimesheet($ts);
        return response()->json($ts);
    }

    public function reject(int $id, Request $request): JsonResponse
    {
        $ts = Timesheet::findOrFail($id);
        $this->manager->rejectTimesheet($ts, $request->input('reason', ''));
        return response()->json($ts);
    }
}