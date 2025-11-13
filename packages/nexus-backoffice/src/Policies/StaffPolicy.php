<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Policies;

use Nexus\Backoffice\Models\Staff;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Staff Policy
 * 
 * Authorization policy for Staff model operations.
 */
class StaffPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any staff.
     */
    public function viewAny($user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the staff.
     */
    public function view($user, Staff $staff): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create staff.
     */
    public function create($user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the staff.
     */
    public function update($user, Staff $staff): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the staff.
     */
    public function delete($user, Staff $staff): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the staff.
     */
    public function restore($user, Staff $staff): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the staff.
     */
    public function forceDelete($user, Staff $staff): bool
    {
        return true;
    }
}