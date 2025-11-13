<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Models\Office;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Nexus\Backoffice\Enums\StaffStatus;
use Nexus\Backoffice\Models\Department;
use Nexus\Backoffice\Models\StaffTransfer;
use Nexus\Backoffice\Observers\StaffObserver;
use Nexus\Backoffice\Observers\OfficeObserver;
use Nexus\Backoffice\BackOfficeServiceProvider;
use Nexus\Backoffice\Observers\CompanyObserver;
use Nexus\Backoffice\Helpers\OrganizationalChart;
use Nexus\Backoffice\Observers\DepartmentObserver;

/**
 * Organizational Chart Feature Tests
 * 
 * Tests the organizational chart functionality including
 * hierarchical relationships, reporting lines, and chart exports.
 * 
 */

#[CoversClass(OrganizationalChart::class)]
#[CoversClass(Company::class)]
#[CoversClass(Office::class)]
#[CoversClass(Department::class)]
#[CoversClass(Staff::class)]
#[CoversClass(StaffTransfer::class)]
#[CoversClass(StaffStatus::class)]
#[CoversClass(CompanyObserver::class)]
#[CoversClass(DepartmentObserver::class)]
#[CoversClass(OfficeObserver::class)]
#[CoversClass(StaffObserver::class)]
#[CoversClass(BackOfficeServiceProvider::class)]
class OrganizationalChartTest extends TestCase
{
    private Company $company;
    private Office $office;
    private Department $department;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test company structure
        $this->company = Company::factory()->create([
            'name' => 'Test Corp',
            'code' => 'TC001',
            'is_active' => true,
        ]);
        
        $this->office = Office::factory()->create([
            'name' => 'Head Office',
            'code' => 'HO001',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        
        $this->department = Department::factory()->create([
            'name' => 'Engineering',
            'code' => 'ENG001',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
    }
    
    #[Test]
    public function it_can_generate_organizational_chart_for_company()
    {
        // Create a simple organizational structure:
        // CEO (no supervisor)
        // ├── CTO (reports to CEO)
        // │   ├── Senior Developer (reports to CTO)
        // │   └── Junior Developer (reports to CTO)
        // └── CFO (reports to CEO)
        
        $ceo = Staff::factory()->create([
            'employee_id' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'email' => 'john.smith@testcorp.com',
            'office_id' => $this->office->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
            'hire_date' => now()->subYears(5),
        ]);
        
        $cto = Staff::factory()->create([
            'employee_id' => 'EMP002',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@testcorp.com',
            'office_id' => $this->office->id,
            'supervisor_id' => $ceo->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
            'hire_date' => now()->subYears(3),
        ]);
        
        $seniorDev = Staff::factory()->create([
            'employee_id' => 'EMP003',
            'first_name' => 'Bob',
            'last_name' => 'Johnson',
            'email' => 'bob.johnson@testcorp.com',
            'office_id' => $this->office->id,
            'department_id' => $this->department->id,
            'supervisor_id' => $cto->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
            'hire_date' => now()->subYears(2),
        ]);
        
        $juniorDev = Staff::factory()->create([
            'employee_id' => 'EMP004',
            'first_name' => 'Alice',
            'last_name' => 'Brown',
            'email' => 'alice.brown@testcorp.com',
            'office_id' => $this->office->id,
            'department_id' => $this->department->id,
            'supervisor_id' => $cto->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
            'hire_date' => now()->subYear(),
        ]);
        
        $cfo = Staff::factory()->create([
            'employee_id' => 'EMP005',
            'first_name' => 'Charlie',
            'last_name' => 'Wilson',
            'email' => 'charlie.wilson@testcorp.com',
            'office_id' => $this->office->id,
            'supervisor_id' => $ceo->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
            'hire_date' => now()->subYears(4),
        ]);
        
        // Test company organizational chart
        $chart = $this->company->getOrganizationalChart();
        
        $this->assertIsArray($chart);
        $this->assertArrayHasKey('company', $chart);
        $this->assertArrayHasKey('chart', $chart);
        $this->assertArrayHasKey('metadata', $chart);
        
        // Check company info
        $this->assertEquals($this->company->id, $chart['company']['id']);
        $this->assertEquals('Test Corp', $chart['company']['name']);
        $this->assertEquals('TC001', $chart['company']['code']);
        
        // Check metadata
        $this->assertEquals(5, $chart['metadata']['total_staff']);
        $this->assertEquals(2, $chart['metadata']['total_managers']); // CEO and CTO
        $this->assertGreaterThanOrEqual(1, $chart['metadata']['max_depth']);
        $this->assertArrayHasKey('generated_at', $chart['metadata']);
        
        // Check chart structure - should have CEO at top level
        $this->assertCount(1, $chart['chart']); // Only CEO at top level
        $topLevel = $chart['chart'][0];
        $this->assertEquals('John Smith', $topLevel['name']);
        $this->assertCount(2, $topLevel['subordinates']); // CTO and CFO
    }
    
    #[Test]
    public function it_can_generate_flat_organizational_chart()
    {
        // Create simple structure
        $ceo = Staff::factory()->create([
            'employee_id' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'office_id' => $this->office->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        $manager = Staff::factory()->create([
            'employee_id' => 'EMP002',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'office_id' => $this->office->id,
            'supervisor_id' => $ceo->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        $flatChart = OrganizationalChart::flatChart($this->company);
        
        $this->assertIsArray($flatChart);
        $this->assertCount(2, $flatChart);
        
        // Find CEO in flat chart
        $ceoData = collect($flatChart)->firstWhere('employee_id', 'EMP001');
        $this->assertNotNull($ceoData);
        $this->assertEquals('John Smith', $ceoData['name']);
        $this->assertNull($ceoData['supervisor']); // CEO has no supervisor
        $this->assertEquals(1, $ceoData['direct_subordinates_count']);
        $this->assertEquals(0, $ceoData['reporting_level']);
        
        // Find manager in flat chart
        $managerData = collect($flatChart)->firstWhere('employee_id', 'EMP002');
        $this->assertNotNull($managerData);
        $this->assertEquals('Jane Doe', $managerData['name']);
        $this->assertNotNull($managerData['supervisor']);
        $this->assertEquals('John Smith', $managerData['supervisor']['name']);
        $this->assertEquals(0, $managerData['direct_subordinates_count']);
        $this->assertEquals(1, $managerData['reporting_level']);
    }
    
    #[Test]
    public function it_can_generate_reporting_paths()
    {
        // Create 3-level hierarchy
        $ceo = Staff::factory()->create([
            'employee_id' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'office_id' => $this->office->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        $manager = Staff::factory()->create([
            'employee_id' => 'EMP002',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'office_id' => $this->office->id,
            'supervisor_id' => $ceo->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        $employee = Staff::factory()->create([
            'employee_id' => 'EMP003',
            'first_name' => 'Bob',
            'last_name' => 'Johnson',
            'office_id' => $this->office->id,
            'supervisor_id' => $manager->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        $reportingPaths = OrganizationalChart::reportingPaths($this->company);
        
        $this->assertIsArray($reportingPaths);
        $this->assertCount(3, $reportingPaths);
        
        // Find employee's reporting path
        $employeePath = collect($reportingPaths)->firstWhere('staff.id', $employee->id);
        $this->assertNotNull($employeePath);
        $this->assertEquals(2, $employeePath['path_length']); // Employee -> Manager -> CEO
        $this->assertCount(3, $employeePath['path']); // Including self
        
        // Check path order (should be from self to top)
        $path = $employeePath['path'];
        $this->assertEquals('Bob Johnson', $path[0]['name']); // Self
        $this->assertEquals('Jane Doe', $path[1]['name']); // Manager
        $this->assertEquals('John Smith', $path[2]['name']); // CEO
    }
    
    #[Test]
    public function it_can_generate_organizational_statistics()
    {
        // Create structure for meaningful statistics
        $ceo = Staff::factory()->create([
            'employee_id' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'office_id' => $this->office->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        // Create 3 managers under CEO
        for ($i = 2; $i <= 4; $i++) {
            $manager = Staff::factory()->create([
                'employee_id' => sprintf('EMP%03d', $i),
                'first_name' => "Manager{$i}",
                'last_name' => 'Smith',
                'office_id' => $this->office->id,
                'supervisor_id' => $ceo->id,
                'status' => StaffStatus::ACTIVE,
                'is_active' => true,
            ]);
            
            // Create 2 employees under each manager
            for ($j = 1; $j <= 2; $j++) {
                $empNum = ($i - 2) * 2 + $j + 4;
                Staff::factory()->create([
                    'employee_id' => sprintf('EMP%03d', $empNum),
                    'first_name' => "Employee{$empNum}",
                    'last_name' => 'Smith',
                    'office_id' => $this->office->id,
                    'supervisor_id' => $manager->id,
                    'status' => StaffStatus::ACTIVE,
                    'is_active' => true,
                ]);
            }
        }
        
        $stats = OrganizationalChart::statistics($this->company);
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('company', $stats);
        $this->assertArrayHasKey('totals', $stats);
        $this->assertArrayHasKey('span_of_control', $stats);
        $this->assertArrayHasKey('team_sizes', $stats);
        $this->assertArrayHasKey('hierarchy_depth', $stats);
        
        // Check totals
        $this->assertEquals(10, $stats['totals']['total_staff']); // 1 CEO + 3 managers + 6 employees
        $this->assertEquals(4, $stats['totals']['total_managers']); // CEO + 3 managers
        $this->assertEquals(1, $stats['totals']['top_level_executives']); // Only CEO
        $this->assertEquals(6, $stats['totals']['individual_contributors']); // 6 employees
        
        // Check span of control (managers have direct reports)
        $this->assertEquals(3, $stats['span_of_control']['maximum']); // CEO has 3 direct reports
        $this->assertEquals(2, $stats['span_of_control']['minimum']); // Managers have 2 direct reports
        
        // Check hierarchy depth
        $this->assertEquals(2, $stats['hierarchy_depth']['maximum_levels']); // Employee level is 2
    }
    
    #[Test]
    public function it_can_export_to_csv_format()
    {
        // Create simple structure
        $ceo = Staff::factory()->create([
            'employee_id' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'email' => 'john@test.com',
            'office_id' => $this->office->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        $manager = Staff::factory()->create([
            'employee_id' => 'EMP002',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@test.com',
            'office_id' => $this->office->id,
            'supervisor_id' => $ceo->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        $csv = OrganizationalChart::export($this->company, 'csv');
        
        $this->assertIsString($csv);
        $this->assertStringContainsString('Employee ID,Name,Position,Email,Office,Department,Supervisor,Direct Reports,Team Size,Level', $csv);
        $this->assertStringContainsString('EMP001', $csv);
        $this->assertStringContainsString('John Smith', $csv);
        $this->assertStringContainsString('EMP002', $csv);
        $this->assertStringContainsString('Jane Doe', $csv);
    }
    
    #[Test]
    public function it_can_export_to_dot_format()
    {
        // Create simple structure
        $ceo = Staff::factory()->create([
            'employee_id' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'office_id' => $this->office->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        $manager = Staff::factory()->create([
            'employee_id' => 'EMP002',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'office_id' => $this->office->id,
            'supervisor_id' => $ceo->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        $dot = OrganizationalChart::export($this->company, 'dot');
        
        $this->assertIsString($dot);
        $this->assertStringContainsString('digraph OrgChart', $dot);
        $this->assertStringContainsString('John Smith', $dot);
        $this->assertStringContainsString('Jane Doe', $dot);
        $this->assertStringContainsString('->', $dot); // Should contain arrows for relationships
    }
    
    #[Test]
    public function it_can_generate_chart_from_specific_staff()
    {
        // Create hierarchy
        $ceo = Staff::factory()->create([
            'employee_id' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'office_id' => $this->office->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        $manager = Staff::factory()->create([
            'employee_id' => 'EMP002',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'office_id' => $this->office->id,
            'supervisor_id' => $ceo->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        $employee = Staff::factory()->create([
            'employee_id' => 'EMP003',
            'first_name' => 'Bob',
            'last_name' => 'Johnson',
            'office_id' => $this->office->id,
            'supervisor_id' => $manager->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        // Generate chart from manager down
        $chart = OrganizationalChart::fromStaff($manager);
        
        $this->assertIsArray($chart);
        $this->assertArrayHasKey('root_staff', $chart);
        $this->assertArrayHasKey('chart', $chart);
        $this->assertArrayHasKey('metadata', $chart);
        
        // Check root staff info
        $this->assertEquals($manager->id, $chart['root_staff']['id']);
        $this->assertEquals('Jane Doe', $chart['root_staff']['name']);
        
        // Check metadata
        $this->assertEquals(1, $chart['metadata']['team_size']); // 1 employee below
        $this->assertEquals(1, $chart['metadata']['span_of_control']); // 1 direct report
        $this->assertEquals(1, $chart['metadata']['reporting_level']); // Manager is at level 1
        
        // Check chart structure
        $this->assertEquals('Jane Doe', $chart['chart']['name']);
        $this->assertCount(1, $chart['chart']['subordinates']); // Bob Johnson
    }
    
    #[Test]
    public function it_provides_reorganization_suggestions()
    {
        // Create a structure that should trigger suggestions
        $ceo = Staff::factory()->create([
            'employee_id' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'office_id' => $this->office->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        // Create manager with too many direct reports (>10)
        $manager = Staff::factory()->create([
            'employee_id' => 'EMP002',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'office_id' => $this->office->id,
            'supervisor_id' => $ceo->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        // Create 12 employees under manager (exceeds recommended span of 10)
        for ($i = 3; $i <= 14; $i++) {
            Staff::factory()->create([
                'employee_id' => sprintf('EMP%03d', $i),
                'first_name' => "Employee{$i}",
                'last_name' => 'Smith',
                'office_id' => $this->office->id,
                'supervisor_id' => $manager->id,
                'status' => StaffStatus::ACTIVE,
                'is_active' => true,
            ]);
        }
        
        $suggestions = OrganizationalChart::reorganizationSuggestions($this->company);
        
        $this->assertIsArray($suggestions);
        $this->assertArrayHasKey('company', $suggestions);
        $this->assertArrayHasKey('suggestions', $suggestions);
        $this->assertArrayHasKey('analysis_date', $suggestions);
        
        // Should have suggestion about span of control being too high
        $spanSuggestions = collect($suggestions['suggestions'])->where('type', 'span_of_control_too_high');
        $this->assertGreaterThan(0, $spanSuggestions->count());
        
        $spanSuggestion = $spanSuggestions->first();
        $this->assertStringContainsString('more than 10 direct reports', $spanSuggestion['description']);
        $this->assertStringContainsString('middle management', $spanSuggestion['recommendation']);
    }
}