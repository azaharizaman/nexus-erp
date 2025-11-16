<?php

declare(strict_types=1);

namespace Nexus\Crm\Core;

use Nexus\Crm\Models\CrmEntity;
use Nexus\Crm\Models\CrmEscalation;

class EscalationService
{
    public function escalate(CrmEntity $entity, string $reason = ''): CrmEscalation
    {
        // Determine level: latest level + 1
        $last = CrmEscalation::where('entity_id', $entity->id)->orderBy('level', 'desc')->first();
        $nextLevel = ($last?->level ?? 0) + 1;

        // Determine the user to escalate to (placeholder: system)
        $toUserId = config('crm.escalation.default_user_id', 'system');

        $escalation = CrmEscalation::create([
            'entity_id' => $entity->id,
            'level' => $nextLevel,
            'from_user_id' => auth()->id() ?? null,
            'to_user_id' => $toUserId,
            'reason' => $reason,
            'escalated_at' => now(),
        ]);

        // Optionally perform notifications or reassignments here

        return $escalation;
    }
}
