<?php

declare(strict_types=1);

namespace Nexus\Atomy\Actions\Backoffice;

use Nexus\Atomy\Actions\Action;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Helpers\OrganizationalChart;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

/**
 * Create Company Action
 * 
 * Orchestrates company creation with proper validation and hierarchy management.
 */
class CreateCompanyAction extends Action
{
    /**
     * Create a new company with validation and hierarchy setup.
     * 
     * @param array $companyData
     * @return Company
     * @throws ValidationException
     */
    public function handle(...$parameters): Company
    {
        $companyData = $parameters[0] ?? [];
        
        if (!is_array($companyData)) {
            throw new \InvalidArgumentException('Company data must be an array');
        }
        // Validate input data
        $this->validateCompanyData($companyData);
        
        // Create the company
        $company = Company::create([
            'name' => $companyData['name'],
            'code' => $companyData['code'] ?? null,
            'description' => $companyData['description'] ?? null,
            'parent_company_id' => $companyData['parent_company_id'] ?? null,
            'is_active' => $companyData['is_active'] ?? true,
        ]);
        
        // If this is a subsidiary, validate hierarchy
        if (isset($companyData['parent_company_id'])) {
            $this->validateHierarchy($company, $companyData['parent_company_id']);
        }
        
        return $company->fresh();
    }

    /**
     * Validate company creation data.
     * 
     * @param array $data
     * @throws ValidationException
     */
    protected function validateCompanyData(array $data): void
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:companies,name',
            'code' => 'nullable|string|max:50|unique:companies,code',
            'description' => 'nullable|string|max:1000',
            'parent_company_id' => 'nullable|exists:companies,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate company hierarchy to prevent circular references.
     * 
     * @param Company $company
     * @param int $parentId
     * @throws \InvalidArgumentException
     */
    protected function validateHierarchy(Company $company, int $parentId): void
    {
        $parent = Company::find($parentId);
        
        if (!$parent) {
            throw new \InvalidArgumentException('Parent company not found');
        }
        
        // Check for circular reference (this would be the parent trying to be its own child)
        $ancestors = $parent->getAncestors();
        foreach ($ancestors as $ancestor) {
            if ($ancestor->id === $company->id) {
                throw new \InvalidArgumentException('Circular reference detected in company hierarchy');
            }
        }
    }
}