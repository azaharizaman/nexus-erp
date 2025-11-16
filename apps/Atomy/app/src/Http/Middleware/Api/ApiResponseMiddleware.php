<?php

declare(strict_types=1);

namespace Nexus\Atomy\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Response Middleware
 * 
 * Standardizes API response format and handles errors consistently.
 */
class ApiResponseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Set default headers for API responses
        $request->headers->set('Accept', 'application/json');
        
        $response = $next($request);
        
        // Add standard API headers
        if ($response instanceof JsonResponse) {
            $response->header('Content-Type', 'application/json');
            $response->header('X-API-Version', 'v1');
            $response->header('X-Powered-By', 'Nexus ERP');
        }
        
        return $response;
    }
}