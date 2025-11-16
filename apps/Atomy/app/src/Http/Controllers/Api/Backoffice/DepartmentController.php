<?php

declare(strict_types=1);

namespace Nexus\Atomy\Http\Controllers\Api\Backoffice;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nexus\Atomy\Actions\Backoffice\CreateDepartmentAction;
use Nexus\Backoffice\Models\Department;
use Nexus\Backoffice\Models\Office;

/**
 * Department API Controller
 * 
 * Provides RESTful API endpoints for department management operations.
 * Integrates with the Action orchestration layer to handle business logic.
 */
class DepartmentController extends Controller
{
    /**
     * Display a listing of departments.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Department::query();
        
        // Apply filters
        if ($request->has('office_id')) {
            $query->where('office_id', $request->input('office_id'));
        }
        
        if ($request->has('company_id')) {
            $query->whereHas('office', function ($q) use ($request) {
                $q->where('company_id', $request->input('company_id'));
            });
        }
        
        if ($request->has('parent_id')) {
            $query->where('parent_department_id', $request->input('parent_id'));
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
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Include relationships
        $query->with(['office.company']);
        
        if ($request->has('with_parent')) {
            $query->with('parentDepartment');
        }
        
        if ($request->has('with_subdepartments')) {
            $query->with('subDepartments');
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
        $departments = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $departments->items(),
            'meta' => [
                'current_page' => $departments->currentPage(),
                'last_page' => $departments->lastPage(),
                'per_page' => $departments->perPage(),
                'total' => $departments->total(),
            ],
            'links' => [
                'first' => $departments->url(1),
                'last' => $departments->url($departments->lastPage()),
                'prev' => $departments->previousPageUrl(),
                'next' => $departments->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Store a newly created department.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments,code',
            'description' => 'nullable|string',
            'office_id' => 'required|exists:offices,id',
            'parent_department_id' => 'nullable|exists:departments,id',
            'is_active' => 'boolean',
        ]);
        
        try {
            $action = new CreateDepartmentAction();
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
                'message' => 'An error occurred while creating the department.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Display the specified department.
     */
    public function show(Request $request, Department $department): JsonResponse
    {
        $department->load(['office.company']);
        
        if ($request->has('with_parent')) {
            $department->load('parentDepartment');
        }
        
        if ($request->has('with_subdepartments')) {
            $department->load('subDepartments');
        }
        
        if ($request->has('with_staff')) {
            $department->load(['staff' => function ($query) {
                $query->where('is_active', true);
            }]);
        }
        
        if ($request->has('with_staff_count')) {
            $department->loadCount('staff');
        }
        
        if ($request->has('with_hierarchy')) {
            $department->load(['parentDepartment', 'subDepartments.subDepartments']);
        }
        
        return response()->json([
            'success' => true,
            'data' => $department,
        ]);
    }

    /**
     * Update the specified department.
     */
    public function update(Request $request, Department $department): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:departments,code,' . $department->id,
            'description' => 'nullable|string',
            'parent_department_id' => 'nullable|exists:departments,id',
            'is_active' => 'boolean',
        ]);
        
        try {
            // Validate hierarchy if parent is being changed
            if (isset($validatedData['parent_department_id']) && 
                $validatedData['parent_department_id'] !== $department->parent_department_id) {
                
                if ($this->wouldCreateCircularReference($department, $validatedData['parent_department_id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot create circular reference in department hierarchy.',
                    ], 422);
                }
            }
            
            $department->update($validatedData);
            
            return response()->json([
                'success' => true,
                'message' => 'Department updated successfully',
                'data' => $department->fresh(['office.company', 'parentDepartment']),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the department.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Remove the specified department.
     */
    public function destroy(Department $department): JsonResponse
    {
        try {
            // Check if department has active staff
            if ($department->staff()->where('is_active', true)->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete department with active staff members.',
                ], 422);
            }
            
            // Check if department has subdepartments
            if ($department->subDepartments()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete department with existing subdepartments.',
                ], 422);
            }
            
            $department->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Department deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the department.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get department hierarchy tree.
     */
    public function hierarchy(Request $request, Department $department): JsonResponse
    {
        try {
            $hierarchy = $this->buildDepartmentHierarchy($department);
            
            return response()->json([
                'success' => true,
                'data' => $hierarchy,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while building hierarchy.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get department statistics.
     */
    public function statistics(Request $request, Department $department): JsonResponse
    {
        try {
            $stats = [
                'total_staff' => $department->staff()->count(),
                'active_staff' => $department->staff()->where('is_active', true)->count(),
                'total_subdepartments' => $department->subDepartments()->count(),
                'direct_reports' => $department->staff()->whereNotNull('supervisor_id')->count(),
            ];
            
            // Add time-based filters if requested
            if ($request->has('date_from') && $request->has('date_to')) {
                $dateFrom = \Carbon\Carbon::parse($request->input('date_from'));
                $dateTo = \Carbon\Carbon::parse($request->input('date_to'));
                
                $stats['new_hires'] = $department->staff()
                    ->whereBetween('hire_date', [$dateFrom, $dateTo])
                    ->count();
                
                $stats['resignations'] = $department->staff()
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
     * Get department staff.
     */
    public function staff(Request $request, Department $department): JsonResponse
    {
        $query = $department->staff();
        
        // Apply filters
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
        $query->with(['position', 'supervisor']);
        
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

    /**
     * Check if updating parent would create circular reference.
     */
    protected function wouldCreateCircularReference(Department $department, ?int $newParentId): bool
    {
        if (!$newParentId) {
            return false;
        }
        
        // Check if new parent is this department itself
        if ($newParentId === $department->id) {
            return true;
        }
        
        // Check if new parent is a descendant of this department
        $descendants = $this->getAllDescendants($department);
        return in_array($newParentId, $descendants);
    }

    /**
     * Get all descendant department IDs.
     */
    protected function getAllDescendants(Department $department): array
    {
        $descendants = [];
        
        foreach ($department->subDepartments as $child) {
            $descendants[] = $child->id;
            $descendants = array_merge($descendants, $this->getAllDescendants($child));
        }
        
        return $descendants;
    }

    /**
     * Build department hierarchy tree.
     */
    protected function buildDepartmentHierarchy(Department $department): array
    {
        $data = $department->toArray();
        $data['subdepartments'] = [];
        
        foreach ($department->subDepartments as $subdepartment) {
            $data['subdepartments'][] = $this->buildDepartmentHierarchy($subdepartment);
        }
        
        return $data;
    }
}