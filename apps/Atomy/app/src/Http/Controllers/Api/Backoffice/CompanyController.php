<?php

declare(strict_types=1);

namespace Nexus\Atomy\Http\Controllers\Api\Backoffice;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nexus\Atomy\Actions\Backoffice\CreateCompanyAction;
use Nexus\Atomy\Actions\Backoffice\UpdateCompanyHierarchyAction;
use Nexus\Atomy\Actions\Backoffice\GenerateOrganizationalChartAction;
use Nexus\Atomy\Http\Requests\Api\Backoffice\StoreCompanyRequest;
use Nexus\Atomy\Http\Requests\Api\Backoffice\UpdateCompanyRequest;
use Nexus\Atomy\Http\Resources\Api\Backoffice\CompanyResource;
use Nexus\Backoffice\Models\Company;

/**
 * Company API Controller
 * 
 * Provides RESTful API endpoints for company management operations.
 * Integrates with the Action orchestration layer to handle business logic.
 */
class CompanyController extends Controller
{
    /**
     * Display a listing of companies.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Company::query();
        
        // Apply filters
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }
        
        if ($request->has('parent_id')) {
            $query->where('parent_company_id', $request->input('parent_id'));
        }
        
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $search = $request->input('search');
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('registration_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Include relationships
        $query->with(['parent', 'children']);
        
        // Pagination
        $perPage = min($request->integer('per_page', 15), 100);
        $companies = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $companies->items(),
            'meta' => [
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'per_page' => $companies->perPage(),
                'total' => $companies->total(),
            ],
            'links' => [
                'first' => $companies->url(1),
                'last' => $companies->url($companies->lastPage()),
                'prev' => $companies->previousPageUrl(),
                'next' => $companies->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Store a newly created company.
     */
    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        
        try {
            $action = new CreateCompanyAction();
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
                'message' => 'An error occurred while creating the company.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Display the specified company.
     */
    public function show(Company $company): JsonResponse
    {
        $company->load(['parent', 'children', 'offices', 'departments']);
        
        return response()->json([
            'success' => true,
            'data' => $company,
        ]);
    }

    /**
     * Update the specified company.
     */
    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'registration_number' => 'sometimes|string|max:50|unique:companies,registration_number,' . $company->id,
            'description' => 'nullable|string',
            'parent_company_id' => 'nullable|exists:companies,id',
            'is_active' => 'boolean',
        ]);
        
        try {
            $company->update($validatedData);
            
            return response()->json([
                'success' => true,
                'message' => 'Company updated successfully',
                'data' => $company->fresh(['parent', 'children']),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the company.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Remove the specified company.
     */
    public function destroy(Company $company): JsonResponse
    {
        try {
            // Check if company has children
            if ($company->children()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete company that has child companies.',
                ], 422);
            }
            
            // Check if company has staff
            if ($company->staff()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete company that has staff members.',
                ], 422);
            }
            
            $company->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Company deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the company.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update company hierarchy.
     */
    public function updateHierarchy(Request $request, Company $company): JsonResponse
    {
        $validatedData = $request->validate([
            'parent_company_id' => 'nullable|exists:companies,id',
        ]);
        
        try {
            $action = new UpdateCompanyHierarchyAction();
            $result = $action->execute($company, $validatedData['parent_company_id'] ?? null);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['data'],
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'errors' => $result['errors'] ?? [],
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating company hierarchy.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Generate organizational chart for the company.
     */
    public function organizationalChart(Request $request, Company $company): JsonResponse
    {
        $options = $request->validate([
            'depth' => 'nullable|integer|min:1|max:10',
            'include_positions' => 'boolean',
            'include_staff' => 'boolean',
            'format' => 'nullable|string|in:tree,flat',
        ]);
        
        try {
            $action = new GenerateOrganizationalChartAction();
            $result = $action->execute($company, $options);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data'],
                    'meta' => $result['meta'] ?? [],
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating organizational chart.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}