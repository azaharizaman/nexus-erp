<?php

declare(strict_types=1);

namespace Nexus\Crm\Services;

use Nexus\Crm\Models\CrmEntity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * CRM Dashboard Service
 *
 * Provides dashboard data and metrics for CRM entities.
 */
class CrmDashboard
{
    /**
     * Get dashboard data for a specific user.
     */
    public function forUser(string $userId): array
    {
        return Cache::remember("crm_dashboard_user_{$userId}", 300, function () use ($userId) {
            return [
                'pending_leads' => $this->getPendingLeads($userId),
                'active_opportunities' => $this->getActiveOpportunities($userId),
                'overdue_items' => $this->getOverdueItems($userId),
                'recent_activity' => $this->getRecentActivity($userId),
                'pipeline_metrics' => $this->getPipelineMetrics($userId),
            ];
        });
    }

    /**
     * Get pending leads assigned to a user.
     */
    private function getPendingLeads(string $userId): Collection
    {
        return CrmEntity::where('entity_type', 'lead')
            ->where('status', 'pending')
            ->assignedTo($userId)
            ->with(['definition', 'currentStage'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get active opportunities assigned to a user.
     */
    private function getActiveOpportunities(string $userId): Collection
    {
        return CrmEntity::where('entity_type', 'opportunity')
            ->whereIn('status', ['active', 'qualified'])
            ->assignedTo($userId)
            ->with(['definition', 'currentStage'])
            ->orderBy('score', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get overdue items assigned to a user.
     */
    private function getOverdueItems(string $userId): Collection
    {
        return CrmEntity::assignedTo($userId)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->where('status', '!=', 'completed')
            ->with(['definition', 'currentStage'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Get recent activity for a user.
     */
    private function getRecentActivity(string $userId): Collection
    {
        // This would typically query an activity/audit log table
        // For now, return recent entity updates
        return CrmEntity::assignedTo($userId)
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($entity) {
                return [
                    'entity' => $entity,
                    'action' => 'updated',
                    'timestamp' => $entity->updated_at,
                ];
            });
    }

    /**
     * Get pipeline metrics for a user.
     */
    private function getPipelineMetrics(string $userId): array
    {
        $entities = CrmEntity::assignedTo($userId)->get();

        $metrics = [
            'total_entities' => $entities->count(),
            'by_status' => $entities->groupBy('status')->map->count(),
            'by_stage' => $entities->groupBy('current_stage_id')->map->count(),
            'average_score' => $entities->avg('score'),
            'high_priority' => $entities->where('priority', '>=', 8)->count(),
        ];

        return $metrics;
    }

    /**
     * Get team dashboard data (for managers).
     */
    public function forTeam(array $userIds): array
    {
        sort($userIds);
        $cacheKey = 'crm_dashboard_team_' . md5(implode(',', $userIds));

        return Cache::remember($cacheKey, 300, function () use ($userIds) {
            $allEntities = CrmEntity::whereHas('assignments', function ($query) use ($userIds) {
                $query->whereIn('user_id', $userIds)->where('is_active', true);
            })->get();

            return [
                'team_entities' => $allEntities,
                'team_metrics' => [
                    'total_entities' => $allEntities->count(),
                    'by_status' => $allEntities->groupBy('status')->map->count(),
                    'by_assignee' => $allEntities->groupBy(function ($entity) {
                        return $entity->assignments->where('is_active', true)->pluck('user_id')->first();
                    })->map->count(),
                ],
            ];
        });
    }
}