<?php

namespace Nexus\Atomy\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Nexus\Atomy\Http\Controllers\Controller;
use Nexus\ProjectManagement\Services\ProjectManager;

class ProjectController extends Controller
{
    private ProjectManager $projectManager;

    public function __construct(ProjectManager $projectManager)
    {
        $this->projectManager = $projectManager;
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $projects = $this->projectManager->getActiveProjects($tenantId);
        return response()->json($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'client_id' => 'nullable|integer',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'budget' => 'nullable|numeric',
        ]);
        $data['project_manager_id'] = $request->user()->id;
        $data['tenant_id'] = $request->user()->tenant_id;
        $data['status'] = 'draft';

        $project = $this->projectManager->createProject($data);
        return response()->json($project, 201);
    }

    public function show(int $id): JsonResponse
    {
        $project = $this->projectManager->getProject($id);
        return response()->json($project);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $project = $this->projectManager->getProject($id);
        $data = $request->validate([
            'name' => 'string',
            'description' => 'nullable|string',
            'status' => 'in:draft,active,on_hold,completed,cancelled',
            'budget' => 'numeric',
        ]);
        $this->projectManager->updateProject($project, $data);
        return response()->json($project);
    }

    public function destroy(int $id): JsonResponse
    {
        $project = $this->projectManager->getProject($id);
        $this->projectManager->deleteProject($project);
        return response()->json(['message' => 'Project deleted']);
    }
}