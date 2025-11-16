<?php

declare(strict_types=1);

namespace Nexus\Crm\Contracts;

use Nexus\Crm\Models\CrmEntity;

/**
 * Assignment Strategy Contract
 *
 * Defines the interface for user assignment strategies.
 */
interface AssignmentStrategyContract
{
    /**
     * Resolve users to assign to an entity.
     *
     * @param CrmEntity $entity The entity being assigned
     * @param array $config Strategy-specific configuration
     * @return array Array of user_id => role mappings
     */
    public function resolve(CrmEntity $entity, array $config = []): array;
}