<?php

namespace Nexus\Atomy\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Nexus\Atomy\Http\Controllers\Controller;
use Nexus\ProjectManagement\Services\TaskManager;

class TaskController extends Controller
{
    private TaskManager $taskManager;

    public function __construct(TaskManager $taskManager)
    {
        $this->taskManager = $taskManager;
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => 'required|integer',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|integer',
            'due_date' => 'nullable|date',
        ]);
        $task = $this->taskManager->createTask($data);
        return response()->json($task, 201);
    }

    public function start(int $id): JsonResponse
    {
        $task = $this->taskManager->getTask($id);
        if (!$this->taskManager->startTask($task)) {
            return response()->json(['error' => 'Cannot start task - dependencies incomplete'], 422);
        }
        return response()->json($task);
    }

    public function complete(int $id): JsonResponse
    {
        $task = $this->taskManager->getTask($id);
        $this->taskManager->completeTask($task);
        return response()->json($task);
    }
}