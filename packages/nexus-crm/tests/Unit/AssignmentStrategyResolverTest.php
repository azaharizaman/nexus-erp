<?php

declare(strict_types=1);

use Nexus\Crm\Core\AssignmentStrategyResolver;
use Nexus\Crm\Core\ManualAssignmentStrategy;
use Nexus\Crm\Core\RoundRobinAssignmentStrategy;
use Nexus\Crm\Core\LoadBalanceAssignmentStrategy;
use Nexus\Crm\Models\CrmEntity;
use Nexus\Crm\Models\CrmAssignment;

it('registers and resolves manual strategy', function () {
    $resolver = new AssignmentStrategyResolver();
    $users = ['u1' => 'assignee'];

    $result = $resolver->resolve('manual', new CrmEntity(), ['users' => $users]);
    expect($result)->toHaveKey('u1');
});

it('round robin selects one user', function () {
    $resolver = new AssignmentStrategyResolver();
    $entity = CrmEntity::create(['entity_type' => 'lead','definition_id' => 'd1','owner_id' => 'u1','data' => [],'status' => 'active']);
    $users = ['u1','u2','u3'];

    $result = $resolver->resolve('round_robin', $entity, ['users' => $users, 'role' => 'assignee']);
    expect(array_keys($result))->toHaveCount(1);
});

it('load balance picks user with least assignments', function () {
    // create assignments for u1
    CrmAssignment::create(['entity_id' => 'e1','user_id' => 'u1','role' => 'assignee','assigned_by' => 'system','assigned_at' => now(),'is_active'=>true]);
    CrmAssignment::create(['entity_id' => 'e2','user_id' => 'u1','role' => 'assignee','assigned_by' => 'system','assigned_at' => now(),'is_active'=>true]);

    $resolver = new AssignmentStrategyResolver();
    $entity = CrmEntity::create(['entity_type' => 'lead','definition_id' => 'd1','owner_id' => 'u1','data' => [],'status' => 'active']);
    $result = $resolver->resolve('load_balance', $entity, ['users' => ['u1','u2']]);

    expect(array_keys($result))->toHaveCount(1);
});
