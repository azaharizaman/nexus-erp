<?php

declare(strict_types=1);

namespace Nexus\OrgStructure\Services;

use Illuminate\Support\Collection;
use Nexus\OrgStructure\Contracts\OrganizationServiceContract;
use Nexus\OrgStructure\Models\Assignment;
use Nexus\OrgStructure\Models\OrgUnit;
use Nexus\OrgStructure\Models\Position;
use Nexus\OrgStructure\Models\ReportingLine;

class DefaultOrganizationService implements OrganizationServiceContract
{
    public function getOrgUnit(string $orgUnitId): ?array
    {
        $unit = OrgUnit::query()->find($orgUnitId);
        return $unit?->toArray();
    }

    public function getPosition(string $positionId): ?array
    {
        $pos = Position::query()->find($positionId);
        return $pos?->toArray();
    }

    public function getManager(string $employeeId): ?array
    {
        // Find the employee's primary assignment
        $assignment = Assignment::query()
            ->where('employee_id', $employeeId)
            ->where('is_primary', true)
            ->current()
            ->first();

        if (!$assignment) {
            return null;
        }

        // Find reporting line for this employee
        $reportingLine = ReportingLine::query()
            ->where('subordinate_employee_id', $employeeId)
            ->current()
            ->first();

        if (!$reportingLine) {
            return null;
        }

        // Get manager's primary assignment
        $managerAssignment = Assignment::query()
            ->where('employee_id', $reportingLine->manager_employee_id)
            ->where('is_primary', true)
            ->current()
            ->with(['position', 'orgUnit'])
            ->first();

        if (!$managerAssignment) {
            return null;
        }

        return [
            'employee_id' => $managerAssignment->employee_id,
            'position' => $managerAssignment->position?->toArray(),
            'org_unit' => $managerAssignment->orgUnit?->toArray(),
            'assignment' => $managerAssignment->toArray(),
        ];
    }

    public function getSubordinates(string $employeeId): Collection
    {
        // Find all reporting lines where this employee is the manager
        $reportingLines = ReportingLine::query()
            ->where('manager_employee_id', $employeeId)
            ->current()
            ->get();

        $subordinates = collect();

        foreach ($reportingLines as $reportingLine) {
            // Get subordinate's primary assignment
            $assignment = Assignment::query()
                ->where('employee_id', $reportingLine->subordinate_employee_id)
                ->where('is_primary', true)
                ->current()
                ->with(['position', 'orgUnit'])
                ->first();

            if ($assignment) {
                $subordinates->push([
                    'employee_id' => $assignment->employee_id,
                    'position' => $assignment->position?->toArray(),
                    'org_unit' => $assignment->orgUnit?->toArray(),
                    'assignment' => $assignment->toArray(),
                ]);
            }
        }

        return $subordinates;
    }

    public function getAssignmentsForEmployee(string $employeeId): Collection
    {
        return Assignment::query()
            ->where('employee_id', $employeeId)
            ->with(['position', 'orgUnit'])
            ->orderByDesc('effective_from')
            ->get()
            ->map(function ($assignment) {
                return [
                    'assignment' => $assignment->toArray(),
                    'position' => $assignment->position?->toArray(),
                    'org_unit' => $assignment->orgUnit?->toArray(),
                ];
            });
    }

    public function resolveReportingChain(string $employeeId): Collection
    {
        $chain = collect();
        $currentEmployeeId = $employeeId;
        $visited = []; // Prevent infinite loops

        while ($currentEmployeeId && !in_array($currentEmployeeId, $visited)) {
            $visited[] = $currentEmployeeId;

            // Get current employee's assignment
            $assignment = Assignment::query()
                ->where('employee_id', $currentEmployeeId)
                ->where('is_primary', true)
                ->current()
                ->with(['position', 'orgUnit'])
                ->first();

            if ($assignment) {
                $chain->push([
                    'employee_id' => $currentEmployeeId,
                    'level' => count($chain),
                    'position' => $assignment->position?->toArray(),
                    'org_unit' => $assignment->orgUnit?->toArray(),
                    'assignment' => $assignment->toArray(),
                ]);

                // Find manager for next iteration
                $reportingLine = ReportingLine::query()
                    ->where('subordinate_employee_id', $currentEmployeeId)
                    ->current()
                    ->first();

                $currentEmployeeId = $reportingLine?->manager_employee_id;
            } else {
                break;
            }
        }

        return $chain;
    }

    /**
     * Create a new organizational unit
     */
    public function createOrgUnit(
        string $tenantId,
        string $name,
        string $code,
        ?string $parentId = null,
        ?array $metadata = null
    ): string {
        $orgUnit = OrgUnit::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'code' => $code,
            'parent_org_unit_id' => $parentId,
            'metadata' => $metadata ?? [],
        ]);

        return $orgUnit->id;
    }

    /**
     * Update an organizational unit
     */
    public function updateOrgUnit(
        string $orgUnitId,
        array $data
    ): void {
        OrgUnit::findOrFail($orgUnitId)->update($data);
    }

    /**
     * Delete an organizational unit
     */
    public function deleteOrgUnit(string $orgUnitId): void
    {
        OrgUnit::findOrFail($orgUnitId)->delete();
    }

    /**
     * Create a new position
     */
    public function createPosition(
        string $tenantId,
        string $title,
        string $code,
        string $orgUnitId,
        ?array $metadata = null
    ): string {
        $position = Position::create([
            'tenant_id' => $tenantId,
            'title' => $title,
            'code' => $code,
            'org_unit_id' => $orgUnitId,
            'metadata' => $metadata ?? [],
        ]);

        return $position->id;
    }

    /**
     * Update a position
     */
    public function updatePosition(
        string $positionId,
        array $data
    ): void {
        Position::findOrFail($positionId)->update($data);
    }

    /**
     * Delete a position
     */
    public function deletePosition(string $positionId): void
    {
        Position::findOrFail($positionId)->delete();
    }

    /**
     * Create a new assignment
     */
    public function createAssignment(
        string $tenantId,
        string $employeeId,
        string $positionId,
        string $orgUnitId,
        string $effectiveFrom,
        ?string $effectiveTo = null,
        bool $isPrimary = false,
        ?array $metadata = null
    ): string {
        $assignment = Assignment::create([
            'tenant_id' => $tenantId,
            'employee_id' => $employeeId,
            'position_id' => $positionId,
            'org_unit_id' => $orgUnitId,
            'effective_from' => $effectiveFrom,
            'effective_to' => $effectiveTo,
            'is_primary' => $isPrimary,
            'metadata' => $metadata ?? [],
        ]);

        return $assignment->id;
    }

    /**
     * Update an assignment
     */
    public function updateAssignment(
        string $assignmentId,
        array $data
    ): void {
        Assignment::findOrFail($assignmentId)->update($data);
    }

    /**
     * Terminate an assignment
     */
    public function terminateAssignment(
        string $assignmentId,
        string $endDate
    ): void {
        Assignment::findOrFail($assignmentId)->update([
            'effective_to' => $endDate,
        ]);
    }

    /**
     * Create a reporting line
     */
    public function createReportingLine(
        string $tenantId,
        string $managerId,
        string $subordinateId,
        ?string $positionId = null,
        string $effectiveFrom,
        ?string $effectiveTo = null,
        ?array $metadata = null
    ): string {
        $reportingLine = ReportingLine::create([
            'tenant_id' => $tenantId,
            'manager_employee_id' => $managerId,
            'subordinate_employee_id' => $subordinateId,
            'position_id' => $positionId,
            'effective_from' => $effectiveFrom,
            'effective_to' => $effectiveTo,
            'metadata' => $metadata ?? [],
        ]);

        return $reportingLine->id;
    }

    /**
     * Update a reporting line
     */
    public function updateReportingLine(
        string $reportingLineId,
        array $data
    ): void {
        ReportingLine::findOrFail($reportingLineId)->update($data);
    }

    /**
     * Terminate a reporting line
     */
    public function terminateReportingLine(
        string $reportingLineId,
        string $endDate
    ): void {
        ReportingLine::findOrFail($reportingLineId)->update([
            'effective_to' => $endDate,
        ]);
    }
}
