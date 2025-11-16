<?php

namespace Nexus\Atomy\Repositories;

use Nexus\ProjectManagement\Contracts\ProjectRepositoryInterface;
use Nexus\ProjectManagement\Contracts\ProjectInterface;
use Nexus\Atomy\Models\Project;

class DbProjectRepository implements ProjectRepositoryInterface
{
    public function create(array $data): ProjectInterface
    {
        return Project::create($data);
    }

    public function findById(int $id): ?ProjectInterface
    {
        return Project::find($id);
    }

    public function findByTenant(int $tenantId): array
    {
        return Project::where('tenant_id', $tenantId)->get()->all();
    }

    public function update(ProjectInterface $project, array $data): bool
    {
        return Project::where('id', $project->getId())->update($data) > 0;
    }

    public function delete(ProjectInterface $project): bool
    {
        return Project::where('id', $project->getId())->delete() > 0;
    }

    public function getActiveProjects(int $tenantId): array
    {
        return Project::where('tenant_id', $tenantId)->where('status', 'active')->get()->all();
    }
}