<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Policies;

use Nexus\Backoffice\Models\Company;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Company Policy
 * 
 * Authorization policy for Company model operations.
 */
class CompanyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any companies.
     */
    public function viewAny($user): bool
    {
        // Implement your authorization logic
        return true;
    }

    /**
     * Determine whether the user can view the company.
     */
    public function view($user, Company $company): bool
    {
        // Implement your authorization logic
        return true;
    }

    /**
     * Determine whether the user can create companies.
     */
    public function create($user): bool
    {
        // Implement your authorization logic
        return true;
    }

    /**
     * Determine whether the user can update the company.
     */
    public function update($user, Company $company): bool
    {
        // Implement your authorization logic
        return true;
    }

    /**
     * Determine whether the user can delete the company.
     */
    public function delete($user, Company $company): bool
    {
        // Prevent deletion if company has child companies
        if ($company->childCompanies()->exists()) {
            return false;
        }

        // Prevent deletion if company has offices
        if ($company->offices()->exists()) {
            return false;
        }

        // Prevent deletion if company has departments
        if ($company->departments()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the company.
     */
    public function restore($user, Company $company): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the company.
     */
    public function forceDelete($user, Company $company): bool
    {
        // Only allow force delete if no related entities exist
        return $company->childCompanies()->withTrashed()->count() === 0 &&
               $company->offices()->withTrashed()->count() === 0 &&
               $company->departments()->withTrashed()->count() === 0;
    }
}