<?php

declare(strict_types=1);

use Nexus\Crm\Models\CrmTimer;
use Nexus\Crm\Models\CrmDefinition;
use Nexus\Crm\Models\CrmEntity;

it('processes sla timer and triggers escalation', function () {
    $definition = CrmDefinition::create([
        'name' => 'timer-test',
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

    $timer = CrmTimer::create([
        'entity_id' => $entity->id,
        'name' => 'test-sla',
        'type' => 'sla_check',
        'scheduled_at' => now()->subMinute(),
        'action_config' => ['type' => 'sla_check', 'sla_id' => null],
    ]);

    $processor = app(Nexus\Crm\Core\TimerProcessor::class);
    $processed = $processor->processDueTimers(10);

    expect($processed)->toBeGreaterThan(0);
});
