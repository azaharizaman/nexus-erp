<?php

declare(strict_types=1);

namespace App\Domains\Core\Middleware;

use App\Domains\Core\Contracts\TenantManagerContract;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identify Tenant Middleware
 *
 * Resolves the current tenant from the authenticated user and injects it
 * into the request lifecycle. Sets tenant context in TenantManager for
 * use throughout the application.
 */
class IdentifyTenant
{
    /**
     * Create a new middleware instance
     */
    public function __construct(
        protected readonly TenantManagerContract $tenantManager
    ) {}

    /**
     * Handle an incoming request
     *
     * Resolves tenant from authenticated user and sets it in the TenantManager.
     * If no authenticated user or user has no tenant, responds with 401/403.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (! auth()->check()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user = auth()->user();

        // Check if user has a tenant
        if (! $user->tenant_id) {
            return response()->json([
                'message' => 'User does not belong to any tenant.',
            ], 403);
        }

        // Resolve tenant from user
        $tenant = $user->tenant;

        // Handle missing tenant gracefully
        if (! $tenant) {
            return response()->json([
                'message' => 'Tenant not found.',
            ], 404);
        }

        // Set tenant context in TenantManager
        $this->tenantManager->setActive($tenant);

        // Continue with request
        return $next($request);
    }
}
