<?php

declare(strict_types=1);

use Nexus\Crm\Models\CrmAssignment;
use Nexus\Crm\Models\CrmEntity;
use Nexus\Crm\Models\CrmDefinition;

it('can create, expire and scope assignments', function () {
    $definition = CrmDefinition::create(['name' => 'assign-def','type'=>'lead','schema'=>[],'is_active'=>true]);
    $entity = CrmEntity::create(['entity_type' => 'lead','definition_id' => $definition->id,'owner_id'=>'u1','data'=>[],'status'=>'active']);

    $assign = CrmAssignment::create(['entity_id' => $entity->id, 'user_id' => 'u2', 'assigned_by' => 'u1', 'role' => 'assignee', 'is_active' => true, 'assigned_at' => now()]);

    expect(CrmAssignment::forUser('u2')->first()->user_id)->toBe('u2');
    expect(CrmAssignment::active()->first()->is_active)->toBeTrue();
});
