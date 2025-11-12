<?php

declare(strict_types=1);

use App\Domains\Core\Middleware\IdentifyTenant;
use App\Exceptions\UnitOfMeasure\IncompatibleUomException;
use App\Exceptions\UnitOfMeasure\InvalidQuantityException;
use App\Exceptions\UnitOfMeasure\UomConversionException;
use App\Exceptions\UnitOfMeasure\UomNotFoundException;
use App\Http\Middleware\EnsureAccountNotLocked;
use App\Http\Middleware\ValidateSanctumToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register middleware aliases for convenient use in routes
        $middleware->alias([
            'auth.locked' => EnsureAccountNotLocked::class,
            'sanctum.validate' => ValidateSanctumToken::class,
        ]);

        // Register middleware for API routes
        // This runs after auth:sanctum middleware to ensure user is authenticated first
        $middleware->api(append: [
            IdentifyTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Register UOM exception handlers
        $exceptions->renderable(function (UomConversionException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => class_basename($e),
                    'message' => $e->getMessage(),
                    'code' => $e->getHttpStatusCode(),
                ], $e->getHttpStatusCode());
            }
        });

        $exceptions->renderable(function (IncompatibleUomException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'IncompatibleUomException',
                    'message' => $e->getMessage(),
                    'from_category' => $e->getFromCategory(),
                    'to_category' => $e->getToCategory(),
                    'code' => $e->getHttpStatusCode(),
                ], $e->getHttpStatusCode());
            }
        });

        $exceptions->renderable(function (UomNotFoundException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'UomNotFoundException',
                    'message' => $e->getMessage(),
                    'uom_code' => $e->getUomCode(),
                    'code' => $e->getHttpStatusCode(),
                ], $e->getHttpStatusCode());
            }
        });

        $exceptions->renderable(function (InvalidQuantityException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'InvalidQuantityException',
                    'message' => $e->getMessage(),
                    'quantity' => $e->getQuantity(),
                    'reason' => $e->getReason(),
                    'code' => $e->getHttpStatusCode(),
                ], $e->getHttpStatusCode());
            }
        });
    })->create();
