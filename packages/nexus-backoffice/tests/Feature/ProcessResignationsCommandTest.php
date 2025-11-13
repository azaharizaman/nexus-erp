<?php

namespace Nexus\Backoffice\Tests\Feature;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Models\Office;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Nexus\Backoffice\Enums\StaffStatus;
use Nexus\Backoffice\Models\Department;
use Nexus\Backoffice\Models\OfficeType;
use Nexus\Backoffice\Models\StaffTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Backoffice\Observers\StaffObserver;
use Nexus\Backoffice\Observers\OfficeObserver;
use Nexus\Backoffice\BackOfficeServiceProvider;
use Nexus\Backoffice\Observers\CompanyObserver;
use Nexus\Backoffice\Observers\DepartmentObserver;
use Nexus\Backoffice\Commands\ProcessResignationsCommand;

#[CoversClass(ProcessResignationsCommand::class)]
#[CoversClass(Staff::class)]
#[CoversClass(Office::class)]
#[CoversClass(Company::class)]
#[CoversClass(Department::class)]
#[CoversClass(OfficeType::class)]
#[CoversClass(StaffTransfer::class)]
#[CoversClass(CompanyObserver::class)]
#[CoversClass(DepartmentObserver::class)]
#[CoversClass(StaffObserver::class)]
#[CoversClass(OfficeObserver::class)]
#[CoversClass(BackOfficeServiceProvider::class)]
class ProcessResignationsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function createTestStructure()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $officeType = OfficeType::factory()->create([
            'name' => 'Headquarters',
            'code' => 'HQ',
            'is_active' => true,
        ]);

        $office = Office::factory()->create([
            'name' => 'Main Office',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Attach office type using many-to-many relationship
        $office->officeTypes()->attach($officeType->id);

        $department = Department::factory()->create([
            'name' => 'IT Department',
            'code' => 'IT',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        return compact('company', 'office', 'department');
    }

    #[Test]
    public function it_processes_due_resignations()
    {
        $structure = $this->createTestStructure();

        // Create staff with resignation due yesterday
        $dueStaff = Staff::factory()->create([
            'first_name' => 'Due', 'last_name' => 'Staff',
            'email' => 'due@example.com',
            'employee_id' => 'EMP001',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        // Set resignation date to yesterday using updateQuietly to bypass validation
        $dueStaff->updateQuietly([
            'resignation_date' => Carbon::now()->subDays(1),
            'resignation_reason' => 'Time to go',
        ]);

        // Create staff with resignation in future
        $futureStaff = Staff::factory()->create([
            'first_name' => 'Future', 'last_name' => 'Staff',
            'email' => 'future@example.com',
            'employee_id' => 'EMP002',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        // Schedule future resignation
        $futureStaff->scheduleResignation(Carbon::now()->addDays(5), 'Future resignation');

        // Run the command with force flag
        $this->artisan('backoffice:process-resignations', ['--force' => true])
             ->assertExitCode(0);

        // Check that due staff was processed
        $dueStaff->refresh();
        $this->assertEquals(StaffStatus::RESIGNED, $dueStaff->status);
        $this->assertNotNull($dueStaff->resigned_at);
        $this->assertFalse($dueStaff->is_active);

        // Check that future staff was not processed
        $futureStaff->refresh();
        $this->assertEquals(StaffStatus::ACTIVE, $futureStaff->status);
        $this->assertNull($futureStaff->resigned_at);
        $this->assertTrue($futureStaff->is_active);
    }

    #[Test]
    public function it_handles_no_resignations_to_process()
    {
        $structure = $this->createTestStructure();

        // Create staff with no resignations scheduled
        Staff::factory()->create([
            'first_name' => 'Active', 'last_name' => 'Staff',
            'email' => 'active@example.com',
            'employee_id' => 'EMP003',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $this->artisan('backoffice:process-resignations', ['--force' => true])
             ->expectsOutput('No resignations to process.')
             ->assertExitCode(0);
    }

    #[Test]
    public function it_supports_dry_run_mode()
    {
        $structure = $this->createTestStructure();

        $dueStaff = Staff::factory()->create([
            'first_name' => 'Due', 'last_name' => 'Staff',
            'email' => 'due@example.com',
            'employee_id' => 'EMP004',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        // Set resignation date to yesterday using updateQuietly to bypass validation
        $dueStaff->updateQuietly([
            'resignation_date' => Carbon::now()->subDays(1),
            'resignation_reason' => 'Dry run test',
        ]);

        $this->artisan('backoffice:process-resignations', ['--dry-run' => true])
             ->expectsOutput('Dry run mode - no changes will be made.')
             ->assertExitCode(0);

        // Staff should not be processed in dry-run mode
        $dueStaff->refresh();
        $this->assertEquals(StaffStatus::ACTIVE, $dueStaff->status);
        $this->assertNull($dueStaff->resigned_at);
        $this->assertTrue($dueStaff->is_active);
    }

    #[Test]
    public function it_displays_resignation_information_before_processing()
    {
        $structure = $this->createTestStructure();

        $dueStaff = Staff::factory()->create([
            'first_name' => 'John', 'last_name' => 'Doe',
            'email' => 'john@example.com',
            'employee_id' => 'EMP005',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        // Set resignation date to yesterday using updateQuietly to bypass validation
        $dueStaff->updateQuietly([
            'resignation_date' => Carbon::now()->subDays(1),
            'resignation_reason' => 'Better opportunity',
            'resigned_at' => null, // Explicitly ensure resigned_at is null
        ]);

        // Verify the staff can be found by pendingResignation scope
        $pending = Staff::pendingResignation()
            ->whereDate('resignation_date', '<=', now()->toDateString())
            ->get();
        $this->assertCount(1, $pending, 'Staff should be found by pendingResignation scope');

        $this->artisan('backoffice:process-resignations', ['--dry-run' => true])
             ->expectsOutput('Found 1 resignation(s) to process:')
             ->assertExitCode(0);
    }

    #[Test]
    public function it_processes_multiple_resignations()
    {
        $structure = $this->createTestStructure();

        // Create multiple staff with due resignations
        $staff1 = Staff::factory()->create([
            'first_name' => 'Staff', 'last_name' => 'One',
            'email' => 'one@example.com',
            'employee_id' => 'EMP006',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        // Set resignation date to 2 days ago using updateQuietly to bypass validation
        $staff1->updateQuietly([
            'resignation_date' => Carbon::now()->subDays(2),
            'resignation_reason' => 'Staff one resignation',
        ]);

        $staff2 = Staff::factory()->create([
            'first_name' => 'Staff', 'last_name' => 'Two',
            'email' => 'two@example.com',
            'employee_id' => 'EMP007',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        // Set resignation date to yesterday using updateQuietly to bypass validation
        $staff2->updateQuietly([
            'resignation_date' => Carbon::now()->subDays(1),
            'resignation_reason' => 'Staff two resignation',
        ]);

        $this->artisan('backoffice:process-resignations', ['--force' => true])
             ->expectsOutput('Found 2 resignation(s) to process:')
             ->expectsOutputToContain('- Processed: 2')
             ->assertExitCode(0);

        // Both staff should be processed
        $staff1->refresh();
        $staff2->refresh();
        
        $this->assertEquals(StaffStatus::RESIGNED, $staff1->status);
        $this->assertEquals(StaffStatus::RESIGNED, $staff2->status);
        $this->assertNotNull($staff1->resigned_at);
        $this->assertNotNull($staff2->resigned_at);
    }

    #[Test]
    public function it_processes_resignations_due_today()
    {
        $structure = $this->createTestStructure();

        // Create staff without resignation_date to avoid observer validation
        $todayStaff = Staff::factory()->create([
            'first_name' => 'Today', 'last_name' => 'Staff',
            'email' => 'today@example.com',
            'employee_id' => 'EMP008',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        // Set resignation date for today using updateQuietly to bypass observer
        $todayStaff->updateQuietly(['resignation_date' => Carbon::now()->startOfDay()]);

        $this->artisan('backoffice:process-resignations', ['--force' => true])
             ->assertExitCode(0);

        $todayStaff->refresh();
        $this->assertEquals(StaffStatus::RESIGNED, $todayStaff->status);
        $this->assertNotNull($todayStaff->resigned_at);
    }
}