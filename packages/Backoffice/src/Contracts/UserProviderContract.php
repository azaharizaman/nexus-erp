<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * User Provider Contract
 * 
 * Abstracts user management functionality to remove direct dependencies
 * on Laravel's Auth facade and user management systems.
 */
interface UserProviderContract
{
    /**
     * Find a user by ID.
     */
    public function findUser(int $userId): ?object;
    
    /**
     * Get the role of a user.
     */
    public function getUserRole(int $userId): ?string;
    
    /**
     * Get all permissions for a user.
     */
    public function getUserPermissions(int $userId): array;
    
    /**
     * Check if a user can access a specific company.
     */
    public function canUserAccessCompany(int $userId, int $companyId): bool;
    
    /**
     * Check if a user has a specific permission.
     */
    public function userHasPermission(int $userId, string $permission): bool;
    
    /**
     * Check if a user can perform an action on a resource.
     */
    public function userCan(int $userId, string $ability, object $resource): bool;
}