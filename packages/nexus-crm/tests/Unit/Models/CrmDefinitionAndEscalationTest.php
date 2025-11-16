<?php

declare(strict_types=1);

use Nexus\Crm\Models\CrmDefinition;
use Nexus\Crm\Models\CrmEntity;
use Nexus\Crm\Models\CrmEscalation;

it('creates and scopes definitions', function () {
    $active = CrmDefinition::create([
        'name' => 'active-def',
        'type' => 'lead',
        'schema' => [],
        'is_active' => true,
    ]);

    $inactive = CrmDefinition::create([
        'name' => 'inactive-def',
        'type' => 'lead',
        'schema' => [],
        'is_active' => false,
    ]);

    expect(CrmDefinition::active()->get())->toHaveCount(1);
    expect(CrmDefinition::ofType('lead')->get())->toHaveCount(2);
});

it('soft deletes a definition', function () {
    $def = CrmDefinition::create([
        'name' => 'delete-me',
        'type' => 'lead',
        'schema' => [],
        'is_active' => true,
    ]);

    $def->delete();

    expect($def->trashed())->toBeTrue();
    expect(CrmDefinition::withTrashed()->find($def->id))->not->toBeNull();
});

it('can create escalation entry for entity', function () {
    $definition = CrmDefinition::create([
        'name' => 'escalation-test',
        'type' => 'lead',
        'schema' => [],
        'is_active' => true,
    ]);

    $entity = CrmEntity::create([
        'entity_type' => 'lead',
        'definition_id' => $definition->id,
        'owner_id' => 'user-1',
        'data' => [],
        'status' => 'active',
    ]);

    $escalation = CrmEscalation::create([
        'entity_id' => $entity->id,
        'level' => 1,
        'from_user_id' => 'user-1',
        'to_user_id' => 'manager-1',
        'reason' => 'SLA breach',
        'escalated_at' => now(),
    ]);

    expect($escalation->entity->id)->toBe($entity->id);
});
