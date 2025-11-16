<?php

declare(strict_types=1);

namespace Nexus\Crm\Core;

use Nexus\Crm\Models\CrmEntity;
use Nexus\Crm\Contracts\AssignmentStrategyContract;

/**
 * Manual Assignment Strategy
 *
 * Assigns users manually specified in the configuration.
 */
class ManualAssignmentStrategy implements AssignmentStrategyContract
{
    /**
     * Resolve users for manual assignment.
     */
    public function resolve(CrmEntity $entity, array $config = []): array
    {
        $users = $config['users'] ?? [];

        $assignments = [];
        foreach ($users as $userId => $role) {
            $assignments[$userId] = $role;
        }

        return $assignments;
    }
}