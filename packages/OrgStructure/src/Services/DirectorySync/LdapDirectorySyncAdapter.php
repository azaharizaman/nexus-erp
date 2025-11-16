<?php

declare(strict_types=1);

namespace Nexus\OrgStructure\Services\DirectorySync;

use Nexus\OrgStructure\Contracts\DirectorySyncAdapterContract;

class LdapDirectorySyncAdapter implements DirectorySyncAdapterContract
{
    private array $config = [];

    public function configure(array $settings): void
    {
        $this->config = array_merge([
            'host' => 'localhost',
            'port' => 389,
            'base_dn' => '',
            'bind_dn' => '',
            'bind_password' => '',
            'user_filter' => '(objectClass=person)',
            'org_unit_filter' => '(objectClass=organizationalUnit)',
            'attributes' => [
                'employee_id' => 'employeeID',
                'name' => 'cn',
                'email' => 'mail',
                'department' => 'department',
                'manager' => 'manager',
            ],
        ], $settings);
    }

    public function testConnection(): bool
    {
        try {
            // In a real implementation, this would attempt to connect to LDAP
            // For now, return true if configuration is present
            return !empty($this->config['host']);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function fetchChanges(?string $sinceCursor = null): iterable
    {
        // In a real implementation, this would query LDAP for changes
        // For now, return empty array
        return [];
    }

    public function currentCursor(): ?string
    {
        // In a real implementation, this would return a timestamp or change ID
        return now()->toISOString();
    }

    public function normalizeOrgUnit(array $external): array
    {
        return [
            'name' => $external[$this->config['attributes']['name']] ?? '',
            'code' => $external['ou'] ?? '',
            'metadata' => $external,
        ];
    }

    public function normalizePosition(array $external): array
    {
        return [
            'title' => $external[$this->config['attributes']['name']] ?? '',
            'code' => $external['ou'] ?? '',
            'metadata' => $external,
        ];
    }

    public function normalizeAssignment(array $external): array
    {
        return [
            'employee_id' => $external[$this->config['attributes']['employee_id']] ?? '',
            'metadata' => $external,
        ];
    }
}