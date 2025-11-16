<?php

use Illuminate\Support\Facades\Route;
use Nexus\Erp\Http\Controllers\Api\Backoffice\CompanyController;
use Nexus\Erp\Http\Controllers\Api\Backoffice\OfficeController;
use Nexus\Erp\Http\Controllers\Api\Backoffice\DepartmentController;
use Nexus\Erp\Http\Controllers\Api\Backoffice\StaffController;

/*
|--------------------------------------------------------------------------
| Backoffice API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the Backoffice module. These routes provide
| RESTful endpoints for managing companies, offices, departments, and staff.
|
*/

Route::prefix('api/v1/backoffice')->group(function () {
    
    // Company Management Routes
    Route::apiResource('companies', CompanyController::class);
    Route::prefix('companies/{company}')->group(function () {
        Route::put('hierarchy', [CompanyController::class, 'updateHierarchy'])
            ->name('companies.update-hierarchy');
        Route::get('organizational-chart', [CompanyController::class, 'organizationalChart'])
            ->name('companies.organizational-chart');
        Route::get('statistics', [CompanyController::class, 'statistics'])
            ->name('companies.statistics');
        Route::get('offices', [CompanyController::class, 'offices'])
            ->name('companies.offices');
        Route::get('staff', [CompanyController::class, 'staff'])
            ->name('companies.staff');
    });
    
    // Office Management Routes
    Route::apiResource('offices', OfficeController::class);
    Route::prefix('offices/{office}')->group(function () {
        Route::get('statistics', [OfficeController::class, 'statistics'])
            ->name('offices.statistics');
        Route::get('departments', [OfficeController::class, 'departments'])
            ->name('offices.departments');
        Route::get('staff', [OfficeController::class, 'staff'])
            ->name('offices.staff');
    });
    
    // Department Management Routes
    Route::apiResource('departments', DepartmentController::class);
    Route::prefix('departments/{department}')->group(function () {
        Route::get('hierarchy', [DepartmentController::class, 'hierarchy'])
            ->name('departments.hierarchy');
        Route::get('statistics', [DepartmentController::class, 'statistics'])
            ->name('departments.statistics');
        Route::get('staff', [DepartmentController::class, 'staff'])
            ->name('departments.staff');
    });
    
    // Staff Management Routes
    Route::apiResource('staff', StaffController::class);
    Route::prefix('staff')->group(function () {
        Route::post('transfers/process', [StaffController::class, 'processTransfers'])
            ->name('staff.process-transfers');
        Route::post('resignations/process', [StaffController::class, 'processResignations'])
            ->name('staff.process-resignations');
    });
    Route::prefix('staff/{staff}')->group(function () {
        Route::post('transfers', [StaffController::class, 'createTransfer'])
            ->name('staff.create-transfer');
        Route::get('transfers', [StaffController::class, 'transferHistory'])
            ->name('staff.transfer-history');
    });
    
});