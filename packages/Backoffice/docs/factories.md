# Model Factories

The BackOffice package provides comprehensive model factories for all models, making it easy to create test data and seed your application.

## Overview

Model factories are classes that define how to generate fake data for your models. They use the Faker library to create realistic test data and support various states for different scenarios.

## Available Factories

### Company Factory

Create companies with hierarchical relationships:

```php
// Create a basic company
$company = Company::factory()->create();

// Create an inactive company
$company = Company::factory()->inactive()->create();

// Create a company hierarchy
$parent = Company::factory()->create();
$child = Company::factory()->childOf($parent)->create();

// Create a root company (no parent)
$root = Company::factory()->root()->create();
```

**Available States:**
- `active()` - Active company
- `inactive()` - Inactive company
- `childOf(Company $parent)` - Child company of specified parent
- `root()` - Root company with no parent

### Office Factory

Create offices with hierarchical relationships and company assignments:

```php
// Create an office for a company
$office = Office::factory()->for($company)->create();

// Create an office hierarchy
$mainOffice = Office::factory()->for($company)->create();
$branchOffice = Office::factory()->childOf($mainOffice)->create();

// Create an inactive office
$office = Office::factory()->inactive()->create();
```

**Available States:**
- `active()` - Active office
- `inactive()` - Inactive office
- `childOf(Office $parent)` - Child office of specified parent
- `root()` - Root office with no parent

### Department Factory

Create departments with hierarchical relationships:

```php
// Create a department for a company
$department = Department::factory()->for($company)->create();

// Create a department hierarchy
$parentDept = Department::factory()->for($company)->create();
$childDept = Department::factory()->childOf($parentDept)->create();

// Create an inactive department
$department = Department::factory()->inactive()->create();
```

**Available States:**
- `active()` - Active department
- `inactive()` - Inactive department
- `childOf(Department $parent)` - Child department of specified parent
- `root()` - Root department with no parent

### Position Factory

Create positions with various hierarchical types:

```php
// Create a basic position
$position = Position::factory()->for($company)->create();

// Create position with specific type
$ceo = Position::factory()->cLevel()->for($company)->create();
$manager = Position::factory()->management()->for($company)->create();
$executive = Position::factory()->executive()->for($company)->create();

// Create position with default department
$position = Position::factory()
    ->for($company)
    ->withDepartment($department)
    ->create();

// Create inactive position
$position = Position::factory()->inactive()->create();
```

**Available States:**
- `active()` - Active position
- `inactive()` - Inactive position
- `withDepartment(Department $department)` - Position with default department
- `cLevel()` - C-Level executive position (CEO, CFO, CTO, etc.)
- `seniorManagement()` - Top Management position (VP, Senior VP, General Manager)
- `management()` - Management position (Manager, Director, Department Head)
- `juniorManagement()` - Junior Management position (Assistant Manager, Team Leader, Supervisor)
- `seniorExecutive()` - Senior Executive position
- `executive()` - Executive position
- `juniorExecutive()` - Junior Executive position
- `nonExecutive()` - Non-Executive position (Technician, Coordinator, Support Staff)
- `clerical()` - Clerical position (Clerk, Data Entry, Secretary)
- `assistant()` - Assistant position (General Assistant, Admin Assistant, Office Assistant)

> **📖 For comprehensive Position documentation**, see [Position Management](positions.md)

### Staff Factory

Create staff with complex organizational relationships:

```php
// Create basic staff
$staff = Staff::factory()->create();

// Create staff in specific office
$staff = Staff::factory()->inOffice($office)->create();

// Create staff in specific department
$staff = Staff::factory()->inDepartment($department)->create();

// Create staff with both office and department
$staff = Staff::factory()->withBoth($office, $department)->create();

// Create staff with supervisor
$manager = Staff::factory()->manager()->create();
$employee = Staff::factory()->withSupervisor($manager)->create();

// Create staff with position
$position = Position::factory()->management()->for($company)->create();
$staff = Staff::factory()->withPosition($position)->create();

// Create top-level executive
$ceo = Staff::factory()->ceo()->create();

// Create staff with specific status
$resigned = Staff::factory()->resigned('Moving abroad')->create();
$onProbation = Staff::factory()->onProbation()->create();
$onLeave = Staff::factory()->onLeave()->create();
$suspended = Staff::factory()->suspended()->create();

// Create staff with pending resignation
$staff = Staff::factory()->pendingResignation('Better opportunity')->create();
```

**Available States:**
- `active()` - Active staff
- `inactive()` - Inactive staff
- `resigned(?string $reason)` - Resigned staff with optional reason
- `pendingResignation(?string $reason)` - Staff with scheduled resignation
- `onProbation()` - Staff on probation period
- `suspended()` - Suspended staff
- `onLeave()` - Staff on leave
- `withSupervisor(Staff $supervisor)` - Assign supervisor
- `withPosition(?Position $position)` - Assign position
- `topLevel()` - Top-level staff without supervisor
- `manager()` - Manager position
- `ceo()` - CEO/President position
- `inOffice(Office $office)` - Assign to office
- `inDepartment(Department $dept)` - Assign to department
- `withBoth(Office $office, Department $dept)` - Assign to both
- `departmentOnly(Department $dept)` - Assign to department only (no office)

### Unit and UnitGroup Factories

Create units and unit groups:

```php
// Create a unit group for a company
$unitGroup = UnitGroup::factory()->for($company)->create();

// Create a unit for a unit group
$unit = Unit::factory()->for($unitGroup)->create();

// Create inactive units
$inactiveGroup = UnitGroup::factory()->inactive()->create();
$inactiveUnit = Unit::factory()->inactive()->create();
```

**Available States:**
- `active()` - Active unit/group
- `inactive()` - Inactive unit/group

### OfficeType Factory

Create office types:

```php
// Create an office type
$type = OfficeType::factory()->create();

// Create an inactive office type
$type = OfficeType::factory()->inactive()->create();
```

**Available States:**
- `active()` - Active office type
- `inactive()` - Inactive office type

### StaffTransfer Factory

Create staff transfer requests with various states:

```php
// Create a pending transfer
$transfer = StaffTransfer::factory()->pending()->create();

// Create an immediate transfer
$transfer = StaffTransfer::factory()->immediate()->create();

// Create a scheduled transfer
$transfer = StaffTransfer::factory()->scheduled('+2 weeks')->create();

// Create an approved transfer
$transfer = StaffTransfer::factory()->approved()->create();

// Create a rejected transfer
$transfer = StaffTransfer::factory()->rejected('Not approved')->create();

// Create a complete transfer (with all changes)
$transfer = StaffTransfer::factory()
    ->complete()
    ->approved()
    ->create();

// Create transfer with specific changes
$transfer = StaffTransfer::factory()
    ->withDepartmentChange()
    ->withSupervisorChange()
    ->withPositionChange()
    ->create();
```

**Available States:**
- `immediate()` - Immediate transfer
- `scheduled(?string $date)` - Scheduled transfer with optional date
- `pending()` - Pending approval
- `approved(?string $approvedBy)` - Approved transfer
- `rejected(?string $reason)` - Rejected transfer
- `cancelled(?string $reason)` - Cancelled transfer
- `completed()` - Completed transfer
- `withDepartmentChange()` - Include department change
- `withSupervisorChange()` - Include supervisor change
- `withPositionChange()` - Include position change
- `complete()` - Include all changes (department, supervisor, position)

## Usage Examples

### Creating an Organizational Structure

```php
// Create a complete organizational structure
$company = Company::factory()->create([
    'name' => 'Acme Corporation',
]);

$headquartersType = OfficeType::factory()->create(['name' => 'Headquarters']);
$headquarters = Office::factory()
    ->for($company)
    ->create(['name' => 'Main Office']);

$itDepartment = Department::factory()
    ->for($company)
    ->create(['name' => 'IT Department']);

$ceo = Staff::factory()
    ->ceo()
    ->inOffice($headquarters)
    ->create(['first_name' => 'John', 'last_name' => 'Doe']);

$itManager = Staff::factory()
    ->manager()
    ->withSupervisor($ceo)
    ->withBoth($headquarters, $itDepartment)
    ->create(['position' => 'IT Manager']);

$developer = Staff::factory()
    ->withSupervisor($itManager)
    ->inDepartment($itDepartment)
    ->create(['position' => 'Software Developer']);
```

### Creating Test Scenarios

```php
// Test resignation workflow
$staff = Staff::factory()->active()->create();
$staff->scheduleResignation(now()->addMonth(), 'Personal reasons');

// Test transfer workflow
$sourceOffice = Office::factory()->for($company)->create();
$targetOffice = Office::factory()->for($company)->create();
$staff = Staff::factory()->inOffice($sourceOffice)->create();

$transfer = StaffTransfer::factory()->create([
    'staff_id' => $staff->id,
    'from_office_id' => $sourceOffice->id,
    'to_office_id' => $targetOffice->id,
    'status' => StaffTransferStatus::PENDING,
]);

// Test hierarchical queries
$parentCompany = Company::factory()->create();
$childCompanies = Company::factory()
    ->count(3)
    ->childOf($parentCompany)
    ->create();
```

### Bulk Creation

```php
// Create multiple models at once
$companies = Company::factory()->count(5)->create();

$offices = Office::factory()
    ->count(10)
    ->for($company)
    ->create();

$staff = Staff::factory()
    ->count(50)
    ->inOffice($office)
    ->create();
```

### Using in Tests

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use AzahariZaman\BackOffice\Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_reporting_hierarchy()
    {
        // Arrange
        $company = Company::factory()->create();
        $office = Office::factory()->for($company)->create();
        
        $ceo = Staff::factory()
            ->ceo()
            ->inOffice($office)
            ->create();
        
        $manager = Staff::factory()
            ->manager()
            ->withSupervisor($ceo)
            ->inOffice($office)
            ->create();
        
        $employee = Staff::factory()
            ->withSupervisor($manager)
            ->inOffice($office)
            ->create();

        // Act
        $reportingPath = $employee->getReportingPath();

        // Assert
        $this->assertCount(3, $reportingPath);
        $this->assertEquals($ceo->id, $reportingPath->last()->id);
    }
}
```

## Best Practices

### 1. Always Use Factories in Tests

❌ **Don't:**
```php
$company = Company::create([
    'name' => 'Test Company',
    'code' => 'TEST',
    'is_active' => true,
]);
```

✅ **Do:**
```php
$company = Company::factory()->create([
    'name' => 'Test Company',
]);
```

### 2. Use States for Clarity

❌ **Don't:**
```php
$staff = Staff::factory()->create([
    'status' => StaffStatus::RESIGNED,
    'resignation_date' => now()->subMonth(),
    'resigned_at' => now()->subMonth(),
    'is_active' => false,
]);
```

✅ **Do:**
```php
$staff = Staff::factory()->resigned()->create();
```

### 3. Chain States for Complex Scenarios

```php
$transfer = StaffTransfer::factory()
    ->complete()
    ->approved()
    ->immediate()
    ->create();
```

### 4. Override Specific Attributes

```php
$staff = Staff::factory()
    ->active()
    ->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'position' => 'Senior Developer',
    ]);
```

### 5. Use Relationships Properly

```php
// Using for() method
$office = Office::factory()->for($company)->create();

// Using relationship on created model
$staff = Staff::factory()->create();
$staff->units()->attach($unit);
```

## Seeding with Factories

You can use factories in database seeders:

```php
use AzahariZaman\BackOffice\Models\Company;
use AzahariZaman\BackOffice\Models\Office;
use AzahariZaman\BackOffice\Models\Staff;

class BackOfficeSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::factory()->create([
            'name' => 'Demo Company',
        ]);

        $offices = Office::factory()
            ->count(5)
            ->for($company)
            ->create();

        foreach ($offices as $office) {
            $manager = Staff::factory()
                ->manager()
                ->inOffice($office)
                ->create();

            Staff::factory()
                ->count(10)
                ->withSupervisor($manager)
                ->inOffice($office)
                ->create();
        }
    }
}
```

## Customizing Factories

If you need custom behavior, you can extend the factories in your application:

```php
namespace App\Database\Factories;

use AzahariZaman\BackOffice\Database\Factories\StaffFactory as BaseStaffFactory;

class StaffFactory extends BaseStaffFactory
{
    public function seniorDeveloper(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'Senior Software Developer',
            'hire_date' => now()->subYears(5),
        ]);
    }
}
```

## See Also

- [Testing Guide](../TESTING.md)
- [Models Documentation](models.md)
- [Examples](examples.md)
