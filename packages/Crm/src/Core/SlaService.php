<?php

declare(strict_types=1);

namespace Nexus\Crm\Core;

use Nexus\Crm\Models\CrmEntity;
use Nexus\Crm\Models\CrmSla;
use Nexus\Crm\Models\CrmTimer;

class SlaService
{
    public function startSla(CrmEntity $entity, int $durationMinutes): CrmSla
    {
        $sla = CrmSla::create([
            'entity_id' => $entity->id,
            'duration_minutes' => $durationMinutes,
            'started_at' => now(),
            'breach_at' => now()->addMinutes($durationMinutes),
            'status' => 'on_track',
        ]);

        // Create a timer to check SLA breach
        CrmTimer::create([
            'entity_id' => $entity->id,
            'name' => 'SLA:' . $sla->id,
            'type' => 'sla_check',
            'description' => 'SLA breach check',
            'scheduled_at' => $sla->breach_at,
            'action_config' => [
                'type' => 'sla_check',
                'sla_id' => $sla->id,
            ],
            'status' => 'pending',
        ]);

        return $sla;
    }

    /**
     * Check SLA for breach and perform actions if breached
     */
    public function checkBreach(CrmSla $sla): bool
    {
        if ($sla->status === 'breached') {
            return false;
        }

        if ($sla->breach_at && $sla->breach_at->isPast()) {
            $sla->update(['status' => 'breached']);

            // escalate
            app(EscalationService::class)->escalate($sla->entity, 'SLA breach');

            return true;
        }

        return false;
    }
}
