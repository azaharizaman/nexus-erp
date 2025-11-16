<?php

namespace Nexus\ProjectManagement\Services;

use Nexus\ProjectManagement\Contracts\ProjectRepositoryInterface;
use Nexus\ProjectManagement\Contracts\ProjectInterface;
use Nexus\ProjectManagement\Exceptions\ProjectNotFoundException;

class ProjectManager
{
    private ProjectRepositoryInterface $projectRepository;

    public function __construct(ProjectRepositoryInterface $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }

    public function createProject(array $data): ProjectInterface
    {
        // Business logic for creating a project
        // Validate data, set defaults, etc.
        return $this->projectRepository->create($data);
    }

    public function getProject(int $id): ProjectInterface
    {
        $project = $this->projectRepository->findById($id);
        if (!$project) {
            throw new ProjectNotFoundException("Project with ID $id not found");
        }
        return $project;
    }

    public function updateProject(ProjectInterface $project, array $data): bool
    {
        // Business logic for updates
        return $this->projectRepository->update($project, $data);
    }

    public function deleteProject(ProjectInterface $project): bool
    {
        // Check if project can be deleted (no active tasks, etc.)
        return $this->projectRepository->delete($project);
    }

    public function getActiveProjects(int $tenantId): array
    {
        return $this->projectRepository->getActiveProjects($tenantId);
    }
}