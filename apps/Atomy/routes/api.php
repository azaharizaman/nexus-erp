<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Nexus\Atomy\Http\Controllers\Api\ProjectController;
use Nexus\Atomy\Http\Controllers\Api\TaskController;
use Nexus\Atomy\Http\Controllers\Api\TimesheetController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('tasks', TaskController::class)->only(['store']);
    Route::post('tasks/{id}/start', [TaskController::class, 'start']);
    Route::post('tasks/{id}/complete', [TaskController::class, 'complete']);

    Route::post('timesheets', [TimesheetController::class, 'store']);
    Route::post('timesheets/{id}/approve', [TimesheetController::class, 'approve']);
    Route::post('timesheets/{id}/reject', [TimesheetController::class, 'reject']);
});