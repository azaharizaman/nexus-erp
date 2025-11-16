# Examples

This guide provides practical examples of how to use the BackOffice package in real-world scenarios.

## Basic Setup

### Creating a Complete Company Structure

```php
use AzahariZaman\BackOffice\Models\Company;
use AzahariZaman\BackOffice\Models\Office;
use AzahariZaman\BackOffice\Models\Department;
use AzahariZaman\BackOffice\Models\Staff;
use AzahariZaman\BackOffice\Models\Unit;
use AzahariZaman\BackOffice\Models\UnitGroup;
use AzahariZaman\BackOffice\Models\OfficeType;

// 1. Create the main company
$company = Company::create([
    'name' => 'TechCorp Solutions',
    'code' => 'TECHCORP',
    'description' => 'Leading technology solutions provider',
    'is_active' => true,
]);

// 2. Create office types
$headOfficeType = OfficeType::create([
    'name' => 'Head Office',
    'code' => 'HO',
    'description' => 'Main headquarters',
    'is_active' => true,
]);

$branchOfficeType = OfficeType::create([
    'name' => 'Branch Office',
    'code' => 'BO',
    'description' => 'Regional branch office',
    'is_active' => true,
]);

// 3. Create main office
$headOffice = Office::create([
    'name' => 'TechCorp Headquarters',
    'code' => 'HQ',
    'company_id' => $company->id,
    'address' => '123 Tech Boulevard, Silicon Valley, CA 94000',
    'phone' => '+1-555-TECH',
    'email' => 'hq@techcorp.com',
    'is_active' => true,
]);

// Assign office type
$headOffice->officeTypes()->attach($headOfficeType);

// 4. Create regional offices
$westCoastOffice = Office::create([
    'name' => 'West Coast Regional Office',
    'code' => 'WC',
    'company_id' => $company->id,
    'parent_office_id' => $headOffice->id,
    'address' => '456 Pacific Street, Seattle, WA 98101',
    'phone' => '+1-555-WEST',
    'email' => 'west@techcorp.com',
    'is_active' => true,
]);

$eastCoastOffice = Office::create([
    'name' => 'East Coast Regional Office',
    'code' => 'EC',
    'company_id' => $company->id,
    'parent_office_id' => $headOffice->id,
    'address' => '789 Atlantic Avenue, New York, NY 10001',
    'phone' => '+1-555-EAST',
    'email' => 'east@techcorp.com',
    'is_active' => true,
]);

$westCoastOffice->officeTypes()->attach($branchOfficeType);
$eastCoastOffice->officeTypes()->attach($branchOfficeType);

// 5. Create departments
$engineeringDept = Department::create([
    'name' => 'Engineering',
    'code' => 'ENG',
    'company_id' => $company->id,
    'description' => 'Software development and engineering',
    'is_active' => true,
]);

$salesDept = Department::create([
    'name' => 'Sales & Marketing',
    'code' => 'SALES',
    'company_id' => $company->id,
    'description' => 'Sales and marketing operations',
    'is_active' => true,
]);

$hrDept = Department::create([
    'name' => 'Human Resources',
    'code' => 'HR',
    'company_id' => $company->id,
    'description' => 'Human resources and administration',
    'is_active' => true,
]);

// Create sub-departments
$frontendDept = Department::create([
    'name' => 'Frontend Development',
    'code' => 'ENG-FE',
    'company_id' => $company->id,
    'parent_department_id' => $engineeringDept->id,
    'is_active' => true,
]);

$backendDept = Department::create([
    'name' => 'Backend Development',
    'code' => 'ENG-BE',
    'company_id' => $company->id,
    'parent_department_id' => $engineeringDept->id,
    'is_active' => true,
]);
```

## Staff Management Examples

### Adding Staff with Different Assignment Patterns

```php
// 1. Staff assigned to both office and department
$seniorDeveloper = Staff::create([
    'employee_id' => 'TC001',
    'first_name' => 'John',
    'last_name' => 'Smith',
    'email' => 'john.smith@techcorp.com',
    'phone' => '+1-555-0101',
    'office_id' => $headOffice->id,
    'department_id' => $backendDept->id,
    'position' => 'Senior Backend Developer',
    'hire_date' => now()->subYears(3),
    'is_active' => true,
]);

// 2. Staff assigned to office only (support staff)
$receptionist = Staff::create([
    'employee_id' => 'TC002',
    'first_name' => 'Sarah',
    'last_name' => 'Johnson',
    'email' => 'sarah.johnson@techcorp.com',
    'phone' => '+1-555-0102',
    'office_id' => $headOffice->id,
    'department_id' => null, // No department assignment
    'position' => 'Receptionist',
    'hire_date' => now()->subYear(),
    'is_active' => true,
]);

// 3. Staff assigned to department only (remote worker)
$remoteDesigner = Staff::create([
    'employee_id' => 'TC003',
    'first_name' => 'Mike',
    'last_name' => 'Chen',
    'email' => 'mike.chen@techcorp.com',
    'phone' => '+1-555-0103',
    'office_id' => null, // Remote worker
    'department_id' => $frontendDept->id,
    'position' => 'UI/UX Designer',
    'hire_date' => now()->subMonths(6),
    'is_active' => true,
]);

// 4. Regional staff
$westCoastSales = Staff::create([
    'employee_id' => 'TC004',
    'first_name' => 'Lisa',
    'last_name' => 'Williams',
    'email' => 'lisa.williams@techcorp.com',
    'phone' => '+1-555-0104',
    'office_id' => $westCoastOffice->id,
    'department_id' => $salesDept->id,
    'position' => 'Regional Sales Manager',
    'hire_date' => now()->subYears(2),
    'is_active' => true,
]);
```

### Unit Management Examples

```php
// Create unit groups for different purposes
$projectTeams = UnitGroup::create([
    'name' => 'Project Teams',
    'code' => 'PROJ',
    'company_id' => $company->id,
    'description' => 'Cross-functional project teams',
    'is_active' => true,
]);

$committees = UnitGroup::create([
    'name' => 'Committees',
    'code' => 'COMM',
    'company_id' => $company->id,
    'description' => 'Organizational committees and working groups',
    'is_active' => true,
]);

// Create project teams
$mobileAppTeam = Unit::create([
    'name' => 'Mobile App Development Team',
    'code' => 'MOBILE',
    'unit_group_id' => $projectTeams->id,
    'description' => 'iOS and Android app development',
    'is_active' => true,
]);

$webPlatformTeam = Unit::create([
    'name' => 'Web Platform Team',
    'code' => 'WEB',
    'unit_group_id' => $projectTeams->id,
    'description' => 'Web application development',
    'is_active' => true,
]);

// Create committees
$safetyCommittee = Unit::create([
    'name' => 'Safety Committee',
    'code' => 'SAFETY',
    'unit_group_id' => $committees->id,
    'description' => 'Workplace safety and health committee',
    'is_active' => true,
]);

// Assign staff to units (staff can be in multiple units)
$mobileAppTeam->staff()->attach([
    $seniorDeveloper->id,
    $remoteDesigner->id,
]);

$webPlatformTeam->staff()->attach([
    $seniorDeveloper->id, // Can be in multiple teams
]);

$safetyCommittee->staff()->attach([
    $westCoastSales->id,
    $receptionist->id,
]);
```

## Advanced Queries

### Hierarchy Traversal Examples

```php
// Get all companies in a group
$parentCompany = Company::where('code', 'PARENT')->first();
$allSubsidiaries = $parentCompany->allChildCompanies();

// Get company hierarchy tree
$companyTree = $parentCompany->getTree();

// Find root company for any subsidiary
$anySubsidiary = Company::find(5);
$rootCompany = $anySubsidiary->rootCompany();

// Get all offices under headquarters
$allOffices = $headOffice->allChildOffices();

// Get office hierarchy path
$regionalOffice = Office::find(3);
$officePath = $regionalOffice->getPath(); // Returns collection from root to current

// Check relationships
if ($westCoastOffice->isDescendantOf($headOffice)) {
    echo "West Coast office is under headquarters";
}

// Get department tree
$engineeringTree = $engineeringDept->getTree();

// Find all staff in department hierarchy
$engineeringStaffIds = $engineeringDept->allChildDepartments()
    ->pluck('id')
    ->push($engineeringDept->id);
    
$allEngineeringStaff = Staff::whereIn('department_id', $engineeringStaffIds)->get();
```

### Complex Filtering Examples

```php
// Find all active staff in specific office and its sub-offices
$headOfficeAndSubOffices = $headOffice->allChildOffices()
    ->pluck('id')
    ->push($headOffice->id);
    
$headOfficeStaff = Staff::active()
    ->whereIn('office_id', $headOfficeAndSubOffices)
    ->get();

// Find staff by multiple criteria
$seniorStaff = Staff::active()
    ->where('hire_date', '<', now()->subYears(2))
    ->whereHas('department', function ($query) {
        $query->where('code', 'LIKE', 'ENG%'); // Engineering departments
    })
    ->get();

// Find offices of specific type in company
$branchOffices = Office::forCompany($company->id)
    ->withType($branchOfficeType->id)
    ->active()
    ->get();

// Search staff by name across company
$searchResults = Staff::searchByName('John')
    ->whereHas('office.company', function ($query) use ($company) {
        $query->where('id', $company->id);
    })
    ->get();

// Get staff in multiple units
$projectStaff = Staff::whereHas('units', function ($query) use ($projectTeams) {
        $query->where('unit_group_id', $projectTeams->id);
    })
    ->with(['units', 'office', 'department'])
    ->get();
```

## Reporting Examples

### Generate Organization Chart Data

```php
function generateOrgChart(Company $company)
{
    $chart = [
        'company' => [
            'name' => $company->name,
            'code' => $company->code,
            'offices' => [],
            'departments' => [],
        ]
    ];
    
    // Get root offices
    $rootOffices = $company->offices()->root()->with(['childOffices', 'staff'])->get();
    
    foreach ($rootOffices as $office) {
        $chart['company']['offices'][] = [
            'name' => $office->name,
            'code' => $office->code,
            'address' => $office->address,
            'staff_count' => $office->staff()->count(),
            'child_offices' => buildOfficeTree($office),
        ];
    }
    
    // Get root departments
    $rootDepartments = $company->departments()->root()->with(['childDepartments', 'staff'])->get();
    
    foreach ($rootDepartments as $department) {
        $chart['company']['departments'][] = [
            'name' => $department->name,
            'code' => $department->code,
            'staff_count' => $department->staff()->count(),
            'child_departments' => buildDepartmentTree($department),
        ];
    }
    
    return $chart;
}

function buildOfficeTree(Office $office)
{
    $tree = [];
    foreach ($office->childOffices as $child) {
        $tree[] = [
            'name' => $child->name,
            'code' => $child->code,
            'staff_count' => $child->staff()->count(),
            'child_offices' => buildOfficeTree($child),
        ];
    }
    return $tree;
}

function buildDepartmentTree(Department $department)
{
    $tree = [];
    foreach ($department->childDepartments as $child) {
        $tree[] = [
            'name' => $child->name,
            'code' => $child->code,
            'staff_count' => $child->staff()->count(),
            'child_departments' => buildDepartmentTree($child),
        ];
    }
    return $tree;
}

// Usage
$orgChart = generateOrgChart($company);
```

### Staff Directory Report

```php
function generateStaffDirectory(Company $company)
{
    $directory = [];
    
    // Group by department
    $departments = $company->departments()->with(['staff.office'])->get();
    
    foreach ($departments as $department) {
        $deptData = [
            'department' => $department->name,
            'code' => $department->code,
            'staff' => []
        ];
        
        foreach ($department->staff as $staff) {
            $deptData['staff'][] = [
                'employee_id' => $staff->employee_id,
                'name' => $staff->full_name,
                'position' => $staff->position,
                'email' => $staff->email,
                'phone' => $staff->phone,
                'office' => $staff->office?->name,
                'hire_date' => $staff->hire_date?->format('Y-m-d'),
                'years_of_service' => $staff->hire_date ? 
                    $staff->hire_date->diffInYears(now()) : null,
            ];
        }
        
        if (!empty($deptData['staff'])) {
            $directory[] = $deptData;
        }
    }
    
    return $directory;
}

// Usage
$staffDirectory = generateStaffDirectory($company);
```

## Migration and Data Import Examples

### Importing from CSV

```php
use Illuminate\Support\Facades\DB;

function importOfficesFromCsv(string $csvPath, Company $company)
{
    $csv = array_map('str_getcsv', file($csvPath));
    $header = array_shift($csv);
    
    DB::transaction(function () use ($csv, $header, $company) {
        foreach ($csv as $row) {
            $data = array_combine($header, $row);
            
            // Find parent office by code if specified
            $parentOffice = null;
            if (!empty($data['parent_code'])) {
                $parentOffice = Office::where('code', $data['parent_code'])
                    ->where('company_id', $company->id)
                    ->first();
            }
            
            Office::create([
                'name' => $data['name'],
                'code' => $data['code'],
                'company_id' => $company->id,
                'parent_office_id' => $parentOffice?->id,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'is_active' => ($data['is_active'] ?? 'true') === 'true',
            ]);
        }
    });
}

// CSV format:
// name,code,parent_code,address,phone,email,is_active
// "Headquarters","HQ","","123 Main St","555-0100","hq@company.com","true"
// "Branch North","BN","HQ","456 North Ave","555-0101","north@company.com","true"
```

### Bulk Staff Assignment

```php
function bulkAssignStaffToUnits(array $assignments)
{
    DB::transaction(function () use ($assignments) {
        foreach ($assignments as $assignment) {
            $staff = Staff::where('employee_id', $assignment['employee_id'])->first();
            $unit = Unit::where('code', $assignment['unit_code'])->first();
            
            if ($staff && $unit) {
                $staff->units()->syncWithoutDetaching([$unit->id]);
            }
        }
    });
}

// Usage
$assignments = [
    ['employee_id' => 'TC001', 'unit_code' => 'MOBILE'],
    ['employee_id' => 'TC001', 'unit_code' => 'WEB'],
    ['employee_id' => 'TC003', 'unit_code' => 'MOBILE'],
];

bulkAssignStaffToUnits($assignments);
```

## Testing Examples

### Model Testing

```php
// tests/Feature/CompanyHierarchyTest.php
use Tests\TestCase;
use AzahariZaman\BackOffice\Models\Company;

class CompanyHierarchyTest extends TestCase
{
    public function test_company_hierarchy_creation()
    {
        $parent = Company::factory()->create(['name' => 'Parent Corp']);
        $child = Company::factory()->create([
            'name' => 'Child Corp',
            'parent_company_id' => $parent->id
        ]);
        
        $this->assertTrue($child->parentCompany->is($parent));
        $this->assertTrue($parent->childCompanies->contains($child));
    }
    
    public function test_circular_reference_prevention()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create(['parent_company_id' => $company1->id]);
        
        $this->expectException(\InvalidArgumentException::class);
        
        // This should fail - would create circular reference
        $company1->update(['parent_company_id' => $company2->id]);
    }
    
    public function test_staff_assignment_validation()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        // This should fail - staff must have office or department
        Staff::factory()->create([
            'office_id' => null,
            'department_id' => null,
        ]);
    }
}
```

## Next Steps

- Learn about [Traits & Behaviors](traits.md)
- Explore [Console Commands](commands.md)
- Check out [API Reference](api.md)
- Review [Best Practices](best-practices.md)