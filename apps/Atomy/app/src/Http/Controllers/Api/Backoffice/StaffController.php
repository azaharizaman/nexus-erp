<?php

declare(strict_types=1);

namespace Nexus\Atomy\Http\Controllers\Api\Backoffice;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nexus\Atomy\Actions\Backoffice\CreateStaffAction;
use Nexus\Atomy\Actions\Backoffice\TransferStaffAction;
use Nexus\Atomy\Actions\Backoffice\ProcessStaffTransfersAction;
use Nexus\Atomy\Actions\Backoffice\ProcessResignationsAction;
use Nexus\Atomy\Http\Requests\Api\Backoffice\StoreStaffRequest;
use Nexus\Atomy\Http\Requests\Api\Backoffice\StaffTransferRequest;
use Nexus\Atomy\Http\Resources\Api\Backoffice\StaffResource;
use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Models\StaffTransfer;

/**
 * Staff API Controller
 * 
 * Provides RESTful API endpoints for staff management operations.
 * Integrates with the Action orchestration layer to handle business logic.
 */
class StaffController extends Controller
{
    /**
     * Display a listing of staff.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Staff::query();
        
        // Apply filters
        if ($request->has('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }
        
        if ($request->has('office_id')) {
            $query->where('office_id', $request->input('office_id'));
        }
        
        if ($request->has('department_id')) {
            $query->where('department_id', $request->input('department_id'));
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
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Include relationships
        $query->with(['company', 'office', 'department', 'position', 'supervisor']);
        
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
            'links' => [
                'first' => $staff->url(1),
                'last' => $staff->url($staff->lastPage()),
                'prev' => $staff->previousPageUrl(),
                'next' => $staff->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Store a newly created staff member.
     */
    public function store(StoreStaffRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        
        try {
            $action = new CreateStaffAction();
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
                'message' => 'An error occurred while creating the staff member.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Display the specified staff member.
     */
    public function show(Staff $staff): JsonResponse
    {
        $staff->load([
            'company', 
            'office', 
            'department', 
            'position', 
            'supervisor', 
            'subordinates',
            'transfers' => function ($query) {
                $query->latest()->limit(10);
            }
        ]);
        
        return response()->json([
            'success' => true,
            'data' => $staff,
        ]);
    }

    /**
     * Update the specified staff member.
     */
    public function update(Request $request, Staff $staff): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:staff,email,' . $staff->id,
            'phone' => 'nullable|string|max:20',
            'hire_date' => 'sometimes|date',
            'resignation_date' => 'nullable|date|after:hire_date',
            'office_id' => 'nullable|exists:offices,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'supervisor_id' => 'nullable|exists:staff,id',
            'is_active' => 'boolean',
        ]);
        
        try {
            $staff->update($validatedData);
            
            return response()->json([
                'success' => true,
                'message' => 'Staff member updated successfully',
                'data' => $staff->fresh(['company', 'office', 'department', 'position', 'supervisor']),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the staff member.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Remove the specified staff member.
     */
    public function destroy(Staff $staff): JsonResponse
    {
        try {
            // Check if staff has subordinates
            if ($staff->subordinates()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete staff member who has subordinates.',
                ], 422);
            }
            
            $staff->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Staff member deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the staff member.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Create a staff transfer.
     */
    public function createTransfer(StaffTransferRequest $request, Staff $staff): JsonResponse
    {
        $validatedData = $request->validated();
        
        try {
            $action = new TransferStaffAction();
            $result = $action->execute($staff, $validatedData);
            
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
                'message' => 'An error occurred while creating the transfer.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get staff transfer history.
     */
    public function transferHistory(Request $request, Staff $staff): JsonResponse
    {
        $perPage = min($request->integer('per_page', 10), 50);
        $transfers = $staff->transfers()
            ->with(['fromOffice', 'toOffice', 'fromDepartment', 'toDepartment'])
            ->latest()
            ->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $transfers->items(),
            'meta' => [
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
                'per_page' => $transfers->perPage(),
                'total' => $transfers->total(),
            ],
        ]);
    }

    /**
     * Process staff transfers (batch operation).
     */
    public function processTransfers(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'effective_date' => 'nullable|date',
            'dry_run' => 'boolean',
        ]);
        
        try {
            $company = $validatedData['company_id'] 
                ? Company::find($validatedData['company_id']) 
                : null;
            
            $effectiveDate = $validatedData['effective_date'] 
                ? \Carbon\Carbon::parse($validatedData['effective_date'])
                : now();
            
            $dryRun = $validatedData['dry_run'] ?? false;
            
            $action = new ProcessStaffTransfersAction();
            $result = $action->execute($company, $effectiveDate, $dryRun);
            
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing transfers.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Process staff resignations (batch operation).
     */
    public function processResignations(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'as_of_date' => 'nullable|date',
            'dry_run' => 'boolean',
        ]);
        
        try {
            $company = $validatedData['company_id'] 
                ? Company::find($validatedData['company_id']) 
                : null;
            
            $asOfDate = $validatedData['as_of_date'] 
                ? \Carbon\Carbon::parse($validatedData['as_of_date'])
                : now();
            
            $dryRun = $validatedData['dry_run'] ?? false;
            
            $action = new ProcessResignationsAction();
            $result = $action->execute($company, $asOfDate, $dryRun);
            
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing resignations.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}