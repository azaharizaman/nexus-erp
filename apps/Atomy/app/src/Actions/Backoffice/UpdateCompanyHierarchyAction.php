<?php

declare(strict_types=1);

namespace Nexus\Atomy\Actions\Backoffice;

use Nexus\Atomy\Actions\Action;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Exceptions\InvalidTransferException;

/**
 * Update Company Hierarchy Action
 * 
 * Orchestrates company hierarchy changes with validation.
 */
class UpdateCompanyHierarchyAction extends Action
{
    /**
     * Update company hierarchy with validation.
     * 
     * @param Company $company
     * @param int|null $parentId
     * @return Company
     * @throws InvalidTransferException
     */
    public function handle(...$parameters): Company
    {
        $company = $parameters[0] ?? null;
        $parentId = $parameters[1] ?? null;
        
        if (!$company instanceof Company) {
            throw new \InvalidArgumentException('First parameter must be a Company instance');
        }
        
        // Validate hierarchy change
        $this->validateHierarchyChange($company, $parentId);
        
        // Update the company
        $company->update(['parent_company_id' => $parentId]);
        
        return $company->fresh();
    }

    /**
     * Validate hierarchy change to prevent circular references.
     * 
     * @param Company $company
     * @param int|null $parentId
     * @throws InvalidTransferException
     */
    protected function validateHierarchyChange(Company $company, ?int $parentId): void
    {
        if ($parentId === null) {
            // Moving to root level is always valid
            return;
        }
        
        if ($parentId === $company->id) {
            throw new InvalidTransferException('Company cannot be its own parent');
        }
        
        $parent = Company::find($parentId);
        if (!$parent) {
            throw new InvalidTransferException('Parent company not found');
        }
        
        // Check if the parent is actually a descendant of this company
        $descendants = $company->getAllDescendants();
        foreach ($descendants as $descendant) {
            if ($descendant->id === $parentId) {
                throw new InvalidTransferException(
                    'Cannot make a descendant company the parent (circular reference)'
                );
            }
        }
        
        // Check if this would create a hierarchy that's too deep
        $parentDepth = $parent->getHierarchyDepth();
        if ($parentDepth >= 5) { // Maximum depth limit
            throw new InvalidTransferException(
                'Company hierarchy cannot exceed 5 levels deep'
            );
        }
    }
}