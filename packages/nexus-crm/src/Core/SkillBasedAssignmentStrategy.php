<?php

declare(strict_types=1);

namespace Nexus\Crm\Core;

use Nexus\Crm\Models\CrmEntity;
use Nexus\Crm\Contracts\AssignmentStrategyContract;

/**
 * Skill-Based Assignment Strategy
 *
 * Assigns users based on required skills and user capabilities.
 */
class SkillBasedAssignmentStrategy implements AssignmentStrategyContract
{
    /**
     * Resolve users using skill-based assignment.
     */
    public function resolve(CrmEntity $entity, array $config = []): array
    {
        $requiredSkills = $config['required_skills'] ?? [];
        $role = $config['role'] ?? 'assignee';

        if (empty($requiredSkills)) {
            return [];
        }

        // For now, return first available user
        // In a real implementation, this would check user skills
        // This is a placeholder for the skill-based logic
        $availableUsers = $config['users'] ?? [];

        return !empty($availableUsers) ? [$availableUsers[0] => $role] : [];
    }
}