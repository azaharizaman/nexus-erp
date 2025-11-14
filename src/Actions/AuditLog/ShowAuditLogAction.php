<?php

declare(strict_types=1);

namespace Nexus\Erp\Actions\AuditLog;

use Lorisleiva\Actions\Concerns\AsAction;
use Nexus\AuditLog\Contracts\AuditLogRepositoryContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

/**
 * Show Audit Log Action
 *
 * Retrieves a specific audit log entry.
 */
class ShowAuditLogAction
{
    use AsAction;

    public function __construct(
        protected AuditLogRepositoryContract $repository
    ) {}

    /**
     * Handle the audit log retrieval
     *
     * @param int $id Audit log ID
     * @return \Spatie\Activitylog\Models\Activity|null
     */
    public function handle(int $id): ?Activity
    {
        return $this->repository->find($id);
    }

    /**
     * Handle HTTP request with authorization
     */
    public function asController(int $id): JsonResponse
    {
        $log = $this->handle($id);

        if (! $log) {
            return response()->json([
                'message' => 'Audit log not found',
            ], 404);
        }

        Gate::authorize('view', $log);

        return response()->json([
            'data' => [
                'id' => $log->id,
                'log_name' => $log->log_name,
                'description' => $log->description,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'causer_type' => $log->causer_type,
                'causer_id' => $log->causer_id,
                'event' => $log->event,
                'properties' => $log->properties,
                'created_at' => $log->created_at,
            ],
        ]);
    }
}