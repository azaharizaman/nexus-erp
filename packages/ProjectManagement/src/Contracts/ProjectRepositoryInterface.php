<?php

namespace Nexus\ProjectManagement\Contracts;

interface ProjectRepositoryInterface
{
    public function create(array $data): ProjectInterface;
    public function findById(int $id): ?ProjectInterface;
    public function findByTenant(int $tenantId): array;
    public function update(ProjectInterface $project, array $data): bool;
    public function delete(ProjectInterface $project): bool;
    public function getActiveProjects(int $tenantId): array;
}