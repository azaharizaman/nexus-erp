<?php

declare(strict_types=1);

namespace Nexus\Atomy\Http\Controllers\Api\Backoffice;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nexus\Atomy\Actions\Backoffice\CreateOfficeAction;
use Nexus\Backoffice\Models\Office;
use Nexus\Backoffice\Models\Company;

/**
 * Office API Controller
 * 
 * Provides RESTful API endpoints for office management operations.
 * Integrates with the Action orchestration layer to handle business logic.
 */
class OfficeController extends Controller
{
    /**
     * Display a listing of offices.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Office::query();
        
        // Apply filters
        if ($request->has('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }
        
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $search = $request->input('search');
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }
        
        // Include relationships
        $query->with(['company']);
        
        if ($request->has('with_departments')) {
            $query->with('departments');
        }
        
        if ($request->has('with_staff_count')) {
            $query->withCount('staff');
        }
        
        // Ordering
        if ($request->has('sort')) {
            $sortField = $request->input('sort');
            $sortDirection = $request->input('direction', 'asc');
            
            if (in_array($sortField, ['name', 'code', 'created_at', 'status'])) {
                $query->orderBy($sortField, $sortDirection);
            }
        } else {
            $query->orderBy('name');
        }
        
        // Pagination
        $perPage = min($request->integer('per_page', 15), 100);
        $offices = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $offices->items(),
            'meta' => [
                'current_page' => $offices->currentPage(),
                'last_page' => $offices->lastPage(),
                'per_page' => $offices->perPage(),
                'total' => $offices->total(),
            ],
            'links' => [
                'first' => $offices->url(1),
                'last' => $offices->url($offices->lastPage()),
                'prev' => $offices->previousPageUrl(),
                'next' => $offices->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Store a newly created office.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:offices,code',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'company_id' => 'required|exists:companies,id',
            'is_active' => 'boolean',
        ]);
        
        try {
            $action = new CreateOfficeAction();
            $result = $action->execute($validatedData);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['data'],
                ], 201);
            }
            
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'errors' => $result['errors'] ?? [],
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the office.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Display the specified office.
     */
    public function show(Request $request, Office $office): JsonResponse
    {
        $office->load(['company']);
        
        if ($request->has('with_departments')) {
            $office->load('departments');
        }
        
        if ($request->has('with_staff')) {
            $office->load(['staff' => function ($query) {
                $query->where('is_active', true);
            }]);
        }
        
        if ($request->has('with_staff_count')) {
            $office->loadCount('staff');
        }
        
        return response()->json([
            'success' => true,
            'data' => $office,
        ]);
    }

    /**
     * Update the specified office.
     */
    public function update(Request $request, Office $office): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:offices,code,' . $office->id,
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);
        
        try {
            $office->update($validatedData);
            
            return response()->json([
                'success' => true,
                'message' => 'Office updated successfully',
                'data' => $office->fresh(['company']),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the office.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Remove the specified office.
     */
    public function destroy(Office $office): JsonResponse
    {
        try {
            // Check if office has active staff
            if ($office->staff()->where('is_active', true)->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete office with active staff members.',
                ], 422);
            }
            
            // Check if office has departments
            if ($office->departments()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete office with existing departments.',
                ], 422);
            }
            
            $office->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Office deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the office.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get office statistics.
     */
    public function statistics(Request $request, Office $office): JsonResponse
    {
        try {
            $stats = [
                'total_departments' => $office->departments()->count(),
                'total_staff' => $office->staff()->count(),
                'active_staff' => $office->staff()->where('is_active', true)->count(),
                'staff_by_department' => $office->departments()
                    ->withCount('staff')
                    ->get(['name', 'staff_count'])
                    ->toArray(),
            ];
            
            // Add time-based filters if requested
            if ($request->has('date_from') && $request->has('date_to')) {
                $dateFrom = \Carbon\Carbon::parse($request->input('date_from'));
                $dateTo = \Carbon\Carbon::parse($request->input('date_to'));
                
                $stats['new_hires'] = $office->staff()
                    ->whereBetween('hire_date', [$dateFrom, $dateTo])
                    ->count();
                
                $stats['resignations'] = $office->staff()
                    ->whereBetween('resignation_date', [$dateFrom, $dateTo])
                    ->count();
            }
            
            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get office departments with staff counts.
     */
    public function departments(Request $request, Office $office): JsonResponse
    {
        $departments = $office->departments()
            ->withCount(['staff', 'activeStaff' => function ($query) {
                $query->where('is_active', true);
            }])
            ->orderBy('name')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $departments,
        ]);
    }

    /**
     * Get office staff.
     */
    public function staff(Request $request, Office $office): JsonResponse
    {
        $query = $office->staff();
        
        // Apply filters
        if ($request->has('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }
        
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }
        
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $search = $request->input('search');
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }
        
        // Include relationships
        $query->with(['department', 'position', 'supervisor']);
        
        // Pagination
        $perPage = min($request->integer('per_page', 15), 100);
        $staff = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $staff->items(),
            'meta' => [
                'current_page' => $staff->currentPage(),
                'last_page' => $staff->lastPage(),
                'per_page' => $staff->perPage(),
                'total' => $staff->total(),
            ],
        ]);
    }
}