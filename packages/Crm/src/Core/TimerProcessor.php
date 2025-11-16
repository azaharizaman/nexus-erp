<?php

declare(strict_types=1);

namespace Nexus\Crm\Core;

use Illuminate\Support\Facades\DB;
use Nexus\Crm\Models\CrmTimer;
use Nexus\Crm\Models\CrmSla;

class TimerProcessor
{
    public function processDueTimers(int $limit = 50): int
    {
        $now = now();
        $timers = CrmTimer::pending()->scheduledBefore($now)->limit($limit)->get();

        $processed = 0;

        foreach ($timers as $timer) {
            DB::transaction(function () use ($timer) {
                // Determine action
                $action = $timer->action_config ?? [];
                $type = $action['type'] ?? null;

                match ($type) {
                    'sla_check' => $this->processSlaCheck($timer, $action),
                    'escalate' => $this->processEscalation($timer, $action),
                    default => $this->defaultAction($timer),
                };

                $timer->markExecuted();
            });

            $processed++;
        }

        return $processed;
    }

    protected function processSlaCheck(CrmTimer $timer, array $action): void
    {
        $slaId = $action['sla_id'] ?? null;
        if (! $slaId) {
            return;
        }

        $sla = CrmSla::find($slaId);
        if ($sla) {
            app(SlaService::class)->checkBreach($sla);
        }
    }

    protected function processEscalation(CrmTimer $timer, array $action): void
    {
        $entity = $timer->entity;
        $reason = $action['reason'] ?? 'Timer escalation';
        app(EscalationService::class)->escalate($entity, $reason);
    }

    protected function defaultAction(CrmTimer $timer): void
    {
        // By default we log and notify; for now just a no-op
    }
}
