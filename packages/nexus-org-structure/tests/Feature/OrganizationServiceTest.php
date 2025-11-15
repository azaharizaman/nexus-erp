<?php

declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use Illuminate\Support\Str;
use Nexus\OrgStructure\Services\DefaultOrganizationService;
use Nexus\OrgStructure\Models\Assignment;
use Nexus\OrgStructure\Models\OrgUnit;
use Nexus\OrgStructure\Models\Position;
use Nexus\OrgStructure\Models\ReportingLine;
use Nexus\OrgStructure\Tests\TestCase;

class OrganizationServiceTest extends TestCase
{
    public function test_can_create_and_retrieve_organizational_unit(): void
    {
        $service = new DefaultOrganizationService();
        $tenantId = (string) Str::ulid();

        $orgUnitId = $service->createOrgUnit(
            $tenantId,
            'Engineering Department',
            'ENG',
            null,
            ['description' => 'Software development team']
        );

        $orgUnit = $service->getOrgUnit($orgUnitId);

        expect($orgUnit)->not->toBeNull();
        expect($orgUnit['name'])->toBe('Engineering Department');
        expect($orgUnit['code'])->toBe('ENG');
        expect($orgUnit['tenant_id'])->toBe($tenantId);
    }

    public function test_can_create_hierarchical_organizational_units(): void
    {
        $service = new DefaultOrganizationService();
        $tenantId = (string) Str::ulid();

        // Create parent org unit
        $parentId = $service->createOrgUnit($tenantId, 'Engineering', 'ENG');

        // Create child org unit
        $childId = $service->createOrgUnit(
            $tenantId,
            'Backend Team',
            'ENG-BACKEND',
            $parentId
        );

        $child = OrgUnit::find($childId);
        expect($child->parent_org_unit_id)->toBe($parentId);
    }

    public function test_can_create_and_retrieve_position(): void
    {
        $service = new DefaultOrganizationService();
        $tenantId = (string) Str::ulid();

        // Create org unit first
        $orgUnitId = $service->createOrgUnit($tenantId, 'Engineering', 'ENG');

        // Create position
        $positionId = $service->createPosition(
            $tenantId,
            'Senior Developer',
            'SR-DEV',
            $orgUnitId,
            ['level' => 'Senior']
        );

        $position = $service->getPosition($positionId);

        expect($position)->not->toBeNull();
        expect($position['title'])->toBe('Senior Developer');
        expect($position['code'])->toBe('SR-DEV');
        expect($position['org_unit_id'])->toBe($orgUnitId);
    }

    public function test_can_create_and_retrieve_employee_assignment(): void
    {
        $service = new DefaultOrganizationService();
        $tenantId = (string) Str::ulid();
        $employeeId = (string) Str::ulid();

        // Create org unit and position
        $orgUnitId = $service->createOrgUnit($tenantId, 'Engineering', 'ENG');
        $positionId = $service->createPosition($tenantId, 'Developer', 'DEV', $orgUnitId);

        // Create assignment
        $assignmentId = $service->createAssignment(
            $tenantId,
            $employeeId,
            $positionId,
            $orgUnitId,
            '2025-01-01',
            null,
            true
        );

        $assignments = $service->getAssignmentsForEmployee($employeeId);

        expect($assignments)->toHaveCount(1);
        expect($assignments->first()['assignment']['id'])->toBe($assignmentId);
        expect($assignments->first()['assignment']['is_primary'])->toBe(true);
    }

    public function test_can_establish_and_retrieve_reporting_relationships(): void
    {
        $service = new DefaultOrganizationService();
        $tenantId = (string) Str::ulid();
        $managerId = (string) Str::ulid();
        $employeeId = (string) Str::ulid();

        // Create org unit and positions
        $orgUnitId = $service->createOrgUnit($tenantId, 'Engineering', 'ENG');
        $managerPositionId = $service->createPosition($tenantId, 'Manager', 'MGR', $orgUnitId);
        $employeePositionId = $service->createPosition($tenantId, 'Developer', 'DEV', $orgUnitId);

        // Create assignments
        $service->createAssignment($tenantId, $managerId, $managerPositionId, $orgUnitId, '2025-01-01', null, true);
        $service->createAssignment($tenantId, $employeeId, $employeePositionId, $orgUnitId, '2025-01-01', null, true);

        // Create reporting line
        $service->createReportingLine(
            $tenantId,
            $managerId,
            $employeeId,
            $employeePositionId,
            '2025-01-01'
        );

        $manager = $service->getManager($employeeId);
        $subordinates = $service->getSubordinates($managerId);

        expect($manager)->not->toBeNull();
        expect($manager['employee_id'])->toBe($managerId);

        expect($subordinates)->toHaveCount(1);
        expect($subordinates->first()['employee_id'])->toBe($employeeId);
    }

    public function test_can_resolve_reporting_chain(): void
    {
        $service = new DefaultOrganizationService();
        $tenantId = (string) Str::ulid();
        $ceoId = (string) Str::ulid();
        $vpId = (string) Str::ulid();
        $managerId = (string) Str::ulid();
        $employeeId = (string) Str::ulid();

        // Create org unit
        $orgUnitId = $service->createOrgUnit($tenantId, 'Company', 'COMP');

        // Create positions
        $ceoPosId = $service->createPosition($tenantId, 'CEO', 'CEO', $orgUnitId);
        $vpPosId = $service->createPosition($tenantId, 'VP Engineering', 'VP-ENG', $orgUnitId);
        $mgrPosId = $service->createPosition($tenantId, 'Manager', 'MGR', $orgUnitId);
        $devPosId = $service->createPosition($tenantId, 'Developer', 'DEV', $orgUnitId);

        // Create assignments
        $service->createAssignment($tenantId, $ceoId, $ceoPosId, $orgUnitId, '2025-01-01', null, true);
        $service->createAssignment($tenantId, $vpId, $vpPosId, $orgUnitId, '2025-01-01', null, true);
        $service->createAssignment($tenantId, $managerId, $mgrPosId, $orgUnitId, '2025-01-01', null, true);
        $service->createAssignment($tenantId, $employeeId, $devPosId, $orgUnitId, '2025-01-01', null, true);

        // Create reporting chain: CEO -> VP -> Manager -> Employee
        $service->createReportingLine($tenantId, $ceoId, $vpId, $vpPosId, '2025-01-01');
        $service->createReportingLine($tenantId, $vpId, $managerId, $mgrPosId, '2025-01-01');
        $service->createReportingLine($tenantId, $managerId, $employeeId, $devPosId, '2025-01-01');

        $chain = $service->resolveReportingChain($employeeId);

        expect($chain)->toHaveCount(4); // Employee, Manager, VP, CEO
        expect($chain->first()['employee_id'])->toBe($employeeId);
        expect($chain->last()['employee_id'])->toBe($ceoId);
    }

    public function test_can_terminate_assignments(): void
    {
        $service = new DefaultOrganizationService();
        $tenantId = (string) Str::ulid();
        $employeeId = (string) Str::ulid();

        // Create org unit and position
        $orgUnitId = $service->createOrgUnit($tenantId, 'Engineering', 'ENG');
        $positionId = $service->createPosition($tenantId, 'Developer', 'DEV', $orgUnitId);

        // Create assignment
        $assignmentId = $service->createAssignment(
            $tenantId,
            $employeeId,
            $positionId,
            $orgUnitId,
            '2025-01-01'
        );

        // Terminate assignment
        $service->terminateAssignment($assignmentId, '2025-06-30');

        $assignment = Assignment::find($assignmentId);
        expect($assignment->effective_to->format('Y-m-d'))->toBe('2025-06-30');
    }

    public function test_can_update_organizational_units(): void
    {
        $service = new DefaultOrganizationService();
        $tenantId = (string) Str::ulid();

        $orgUnitId = $service->createOrgUnit($tenantId, 'Old Name', 'OLD');

        $service->updateOrgUnit($orgUnitId, [
            'name' => 'New Name',
            'code' => 'NEW',
        ]);

        $orgUnit = OrgUnit::find($orgUnitId);
        expect($orgUnit->name)->toBe('New Name');
        expect($orgUnit->code)->toBe('NEW');
    }

    public function test_assignment_scopes_work_correctly(): void
    {
        $tenantId = (string) Str::ulid();
        $employeeId = (string) Str::ulid();

        // Create org unit and position directly
        $orgUnit = OrgUnit::create([
            'tenant_id' => $tenantId,
            'name' => 'Test Unit',
            'code' => 'TU',
        ]);
        $position = Position::create([
            'tenant_id' => $tenantId,
            'org_unit_id' => $orgUnit->id,
            'title' => 'Test Position',
            'code' => 'TP',
        ]);

        // Create current primary assignment
        Assignment::create([
            'tenant_id' => $tenantId,
            'employee_id' => $employeeId,
            'position_id' => $position->id,
            'org_unit_id' => $orgUnit->id,
            'effective_from' => '2024-01-01',
            'effective_to' => null,
            'is_primary' => true,
        ]);

        // Create historical assignment
        Assignment::create([
            'tenant_id' => $tenantId,
            'employee_id' => $employeeId,
            'position_id' => $position->id,
            'org_unit_id' => $orgUnit->id,
            'effective_from' => '2023-01-01',
            'effective_to' => '2023-12-31',
            'is_primary' => false,
        ]);

        $currentAssignments = Assignment::forEmployee($employeeId)->current()->get();
        $primaryAssignments = Assignment::forEmployee($employeeId)->primary()->get();

        expect($currentAssignments)->toHaveCount(1);
        expect($primaryAssignments)->toHaveCount(1);
        expect($primaryAssignments->first()->is_primary)->toBe(true);
    }

    public function test_reporting_line_scopes_work_correctly(): void
    {
        $tenantId = (string) Str::ulid();
        $managerId = (string) Str::ulid();
        $employeeId = (string) Str::ulid();

        // Create position directly
        $position = Position::create([
            'tenant_id' => $tenantId,
            'org_unit_id' => OrgUnit::create([
                'tenant_id' => $tenantId,
                'name' => 'Test Unit',
                'code' => 'TU',
            ])->id,
            'title' => 'Test Position',
            'code' => 'TP',
        ]);

        // Create current reporting line
        ReportingLine::create([
            'tenant_id' => $tenantId,
            'manager_employee_id' => $managerId,
            'subordinate_employee_id' => $employeeId,
            'position_id' => $position->id,
            'effective_from' => '2024-01-01',
            'effective_to' => null,
        ]);

        // Create historical reporting line
        ReportingLine::create([
            'tenant_id' => $tenantId,
            'manager_employee_id' => $managerId,
            'subordinate_employee_id' => $employeeId,
            'position_id' => $position->id,
            'effective_from' => '2023-01-01',
            'effective_to' => '2023-12-31',
        ]);

        $currentLines = ReportingLine::forManager($managerId)->current()->get();
        $subordinateLines = ReportingLine::forSubordinate($employeeId)->get();

        expect($currentLines)->toHaveCount(1);
        expect($subordinateLines)->toHaveCount(2);
    }
}