<?php

declare(strict_types=1);

use Nexus\Crm\Models\CrmDefinition;
use Nexus\Crm\Models\CrmEntity;

it('supports simple delegation chain', function () {
    $definition = CrmDefinition::create([
        'name' => 'delegation-test',
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

    // Simple delegation is recorded by EscalationService for now
    $escalation = app(Nexus\Crm\Core\EscalationService::class)->escalate($entity, 'Delegation test');

    expect($escalation)->toBeInstanceOf(Nexus\Crm\Models\CrmEscalation::class);
});
