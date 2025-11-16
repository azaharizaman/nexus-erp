<?php

declare(strict_types=1);

namespace Nexus\Atomy\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Nexus\Backoffice\Models\Company;
use Symfony\Component\HttpFoundation\Response;

/**
 * Company Context Middleware
 * 
 * Validates and sets company context for API requests.
 */
class CompanyContextMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if company context is required and available
        $companyId = $this->getCompanyIdFromRequest($request);
        
        if ($companyId) {
            // Validate company exists and is active
            $company = Company::find($companyId);
            
            if (!$company) {
                return $this->errorResponse('Company not found.', 404);
            }
            
            if (!$company->is_active) {
                return $this->errorResponse('Company is not active.', 403);
            }
            
            // Set company in request for controllers to use
            $request->merge(['_company' => $company]);
            $request->attributes->set('company', $company);
        }
        
        return $next($request);
    }
    
    /**
     * Extract company ID from request.
     */
    protected function getCompanyIdFromRequest(Request $request): ?int
    {
        // Check route parameters first
        if ($request->route('company')) {
            return is_object($request->route('company')) 
                ? $request->route('company')->id 
                : (int) $request->route('company');
        }
        
        // Check query parameters
        if ($request->has('company_id')) {
            return (int) $request->input('company_id');
        }
        
        // Check request body
        if ($request->has('company_id')) {
            return (int) $request->input('company_id');
        }
        
        // Check headers
        if ($request->hasHeader('X-Company-ID')) {
            return (int) $request->header('X-Company-ID');
        }
        
        return null;
    }
    
    /**
     * Return error response.
     */
    protected function errorResponse(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}