<?php

declare(strict_types=1);

use Nexus\Crm\Core\SlaService;
use Nexus\Crm\Models\CrmEntity;
use Nexus\Crm\Models\CrmDefinition;

it('can create and breach sla', function () {
    $definition = CrmDefinition::create([
        'name' => 'sla-test',
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

    $service = app(SlaService::class);
    $sla = $service->startSla($entity, 1); // 1 minute

    // Move time forward to force breach
    $this->travel(2)->minutes();

    $service->checkBreach($sla->refresh());

    expect($sla->refresh()->status)->toBe('breached');
});
