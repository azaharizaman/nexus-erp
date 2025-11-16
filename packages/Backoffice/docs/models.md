# Models & Relationships

The BackOffice package provides several Eloquent models that work together to create a comprehensive organizational structure. This guide explains each model, their relationships, and how to use them effectively.

## Model Overview

### Core Models

1. **Company** - Root organizational entity
2. **Office** - Physical office locations (hierarchical)
3. **Department** - Logical organizational units (hierarchical)
4. **Position** - Job positions with hierarchical types (NEW)
5. **Staff** - Employees/personnel
6. **Unit** - Logical staff groupings (flat structure)
7. **UnitGroup** - Containers for units
8. **OfficeType** - Categorization for offices

> **📖 For comprehensive Position documentation**, see [Position Management](positions.md)

## Company Model

The Company model represents the top-level organizational entity.

### Properties

```php
// Required
$company->name;           // string - Company name
$company->is_active;      // boolean - Active status

// Optional
$company->code;           // string|null - Company code/abbreviation
$company->description;    // string|null - Company description
$company->parent_company_id; // int|null - Parent company ID
```

### Relationships

```php
// Hierarchical relationships
$company->parentCompany;     // BelongsTo - Parent company
$company->childCompanies;    // HasMany - Direct child companies
$company->allChildCompanies; // Collection - All descendant companies
$company->allParentCompanies; // Collection - All ancestor companies

// Organizational entities
$company->offices;          // HasMany - Company offices
$company->departments;      // HasMany - Company departments
```

### Usage Examples

```php
use AzahariZaman\BackOffice\Models\Company;

// Create a parent company
$parentCompany = Company::create([
    'name' => 'ABC Corporation',
    'code' => 'ABC',
    'description' => 'Main holding company',
    'is_active' => true,
]);

// Create a subsidiary
$subsidiary = Company::create([
    'name' => 'ABC Tech Solutions',
    'code' => 'ABCTECH',
    'parent_company_id' => $parentCompany->id,
    'is_active' => true,
]);

// Get all subsidiaries
$allSubsidiaries = $parentCompany->allChildCompanies();

// Check if company is root
if ($company->isRoot()) {
    echo "This is a root company";
}

// Get company hierarchy depth
$depth = $company->getDepth();
```

## Office Model

The Office model represents physical office locations with hierarchical capabilities.

### Properties

```php
// Required
$office->name;              // string - Office name
$office->company_id;        // int - Company ID
$office->is_active;         // boolean - Active status

// Optional
$office->code;              // string|null - Office code
$office->description;       // string|null - Office description
$office->parent_office_id;  // int|null - Parent office ID
$office->address;           // string|null - Physical address
$office->phone;             // string|null - Phone number
$office->email;             // string|null - Email address
```

### Relationships

```php
// Company relationship
$office->company;           // BelongsTo - Owning company

// Hierarchical relationships
$office->parentOffice;      // BelongsTo - Parent office
$office->childOffices;      // HasMany - Direct child offices
$office->allChildOffices;   // Collection - All descendant offices

// Office types
$office->officeTypes;       // BelongsToMany - Assigned office types

// Staff
$office->staff;             // HasMany - Office staff
```

### Usage Examples

```php
use AzahariZaman\BackOffice\Models\Office;
use AzahariZaman\BackOffice\Models\OfficeType;

// Create main office
$mainOffice = Office::create([
    'name' => 'Headquarters',
    'code' => 'HQ',
    'company_id' => $company->id,
    'address' => '123 Business Street, City',
    'phone' => '+1-555-0123',
    'email' => 'hq@company.com',
    'is_active' => true,
]);

// Create branch office
$branchOffice = Office::create([
    'name' => 'Branch Office - North',
    'code' => 'BR-N',
    'company_id' => $company->id,
    'parent_office_id' => $mainOffice->id,
    'address' => '456 North Avenue, City',
    'is_active' => true,
]);

// Assign office types
$headOfficeType = OfficeType::where('code', 'HO')->first();
$mainOffice->officeTypes()->attach($headOfficeType);

// Get all branch offices
$branches = $mainOffice->allChildOffices();

// Filter offices by type
$salesOffices = Office::withType($salesOfficeType->id)->get();
```

## Department Model

The Department model represents logical organizational units with hierarchical capabilities.

### Properties

```php
// Required
$department->name;              // string - Department name
$department->company_id;        // int - Company ID
$department->is_active;         // boolean - Active status

// Optional
$department->code;              // string|null - Department code
$department->description;       // string|null - Department description
$department->parent_department_id; // int|null - Parent department ID
```

### Relationships

```php
// Company relationship
$department->company;           // BelongsTo - Owning company

// Hierarchical relationships
$department->parentDepartment;  // BelongsTo - Parent department
$department->childDepartments;  // HasMany - Direct child departments
$department->allChildDepartments; // Collection - All descendant departments

// Staff
$department->staff;             // HasMany - Department staff
```

### Usage Examples

```php
use AzahariZaman\BackOffice\Models\Department;

// Create main department
$hrDepartment = Department::create([
    'name' => 'Human Resources',
    'code' => 'HR',
    'company_id' => $company->id,
    'description' => 'Human resources and administration',
    'is_active' => true,
]);

// Create sub-department
$recruitmentDept = Department::create([
    'name' => 'Recruitment',
    'code' => 'HR-REC',
    'company_id' => $company->id,
    'parent_department_id' => $hrDepartment->id,
    'is_active' => true,
]);

// Get department tree
$deptTree = $hrDepartment->getTree();

// Check department hierarchy
if ($recruitmentDept->isDescendantOf($hrDepartment)) {
    echo "Recruitment is under HR";
}
```

## Staff Model

The Staff model represents employees who can be assigned to offices and/or departments.

### Properties

```php
// Required
$staff->employee_id;        // string - Unique employee identifier
$staff->first_name;         // string - First name
$staff->last_name;          // string - Last name
$staff->is_active;          // boolean - Active status

// Optional
$staff->email;              // string|null - Email address
$staff->phone;              // string|null - Phone number
$staff->office_id;          // int|null - Office assignment
$staff->department_id;      // int|null - Department assignment
$staff->position;           // string|null - Job position/title
$staff->hire_date;          // Carbon|null - Hire date
```

### Computed Properties

```php
$staff->full_name;          // string - First name + Last name
```

### Relationships

```php
// Assignments
$staff->office;             // BelongsTo - Assigned office
$staff->department;         // BelongsTo - Assigned department

// Units
$staff->units;              // BelongsToMany - Assigned units
```

### Usage Examples

```php
use AzahariZaman\BackOffice\Models\Staff;

// Create staff member
$staff = Staff::create([
    'employee_id' => 'EMP001',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john.doe@company.com',
    'phone' => '+1-555-0456',
    'office_id' => $mainOffice->id,
    'department_id' => $hrDepartment->id,
    'position' => 'HR Manager',
    'hire_date' => now()->subYears(2),
    'is_active' => true,
]);

// Check assignments
if ($staff->hasOffice()) {
    echo "Staff has office assignment";
}

if ($staff->hasDepartment()) {
    echo "Staff has department assignment";
}

// Get company through office or department
$company = $staff->getCompany();

// Search staff
$results = Staff::searchByName('John')->get();

// Filter by office
$officeStaff = Staff::inOffice($office->id)->get();

// Filter by department
$deptStaff = Staff::inDepartment($department->id)->get();
```

## Unit Model

The Unit model represents logical groupings of staff. Unlike other models, units are not hierarchical.

### Properties

```php
// Required
$unit->name;                // string - Unit name
$unit->unit_group_id;       // int - Unit group ID
$unit->is_active;           // boolean - Active status

// Optional
$unit->code;                // string|null - Unit code
$unit->description;         // string|null - Unit description
```

### Relationships

```php
// Unit group
$unit->unitGroup;           // BelongsTo - Parent unit group

// Staff
$unit->staff;               // BelongsToMany - Unit members
```

### Usage Examples

```php
use AzahariZaman\BackOffice\Models\Unit;
use AzahariZaman\BackOffice\Models\UnitGroup;

// Create unit group first
$unitGroup = UnitGroup::create([
    'name' => 'Project Teams',
    'code' => 'PROJ',
    'company_id' => $company->id,
    'is_active' => true,
]);

// Create units
$alphaTeam = Unit::create([
    'name' => 'Alpha Team',
    'code' => 'ALPHA',
    'unit_group_id' => $unitGroup->id,
    'description' => 'Product development team',
    'is_active' => true,
]);

$betaTeam = Unit::create([
    'name' => 'Beta Team',
    'code' => 'BETA',
    'unit_group_id' => $unitGroup->id,
    'description' => 'Quality assurance team',
    'is_active' => true,
]);

// Assign staff to units
$alphaTeam->staff()->attach([$staff1->id, $staff2->id]);
$betaTeam->staff()->attach([$staff2->id, $staff3->id]); // Staff can be in multiple units

// Get company through unit group
$company = $alphaTeam->getCompany();
```

## UnitGroup Model

The UnitGroup model serves as a container for organizing units.

### Properties

```php
// Required
$unitGroup->name;           // string - Unit group name
$unitGroup->company_id;     // int - Company ID
$unitGroup->is_active;      // boolean - Active status

// Optional
$unitGroup->code;           // string|null - Unit group code
$unitGroup->description;    // string|null - Unit group description
```

### Relationships

```php
// Company
$unitGroup->company;        // BelongsTo - Owning company

// Units
$unitGroup->units;          // HasMany - Units in this group
```

### Usage Examples

```php
use AzahariZaman\BackOffice\Models\UnitGroup;

// Create unit group
$projectGroup = UnitGroup::create([
    'name' => 'Development Projects',
    'code' => 'DEV-PROJ',
    'company_id' => $company->id,
    'description' => 'Software development project teams',
    'is_active' => true,
]);

// Get all staff in unit group
$allStaff = $projectGroup->getAllStaff();
```

## OfficeType Model

The OfficeType model provides categorization for offices.

### Properties

```php
// Required
$officeType->name;          // string - Office type name
$officeType->is_active;     // boolean - Active status

// Optional
$officeType->code;          // string|null - Office type code
$officeType->description;   // string|null - Office type description
```

### Relationships

```php
// Offices
$officeType->offices;       // BelongsToMany - Offices with this type
```

### Usage Examples

```php
use AzahariZaman\BackOffice\Models\OfficeType;

// Create office type
$salesOfficeType = OfficeType::create([
    'name' => 'Sales Office',
    'code' => 'SALES',
    'description' => 'Dedicated sales and customer service office',
    'is_active' => true,
]);

// Get all sales offices
$salesOffices = $salesOfficeType->offices;
```

## Common Patterns

### Querying Hierarchies

```php
// Get root companies
$rootCompanies = Company::roots()->get();

// Get leaf offices (no children)
$leafOffices = Office::leaves()->get();

// Get specific hierarchy level
$level2Departments = Department::where('parent_department_id', '!=', null)
    ->whereHas('parentDepartment', function($q) {
        $q->whereNull('parent_department_id');
    })->get();
```

### Scopes and Filtering

```php
// Active entities only
$activeCompanies = Company::active()->get();
$activeStaff = Staff::active()->get();

// Company-specific queries
$companyOffices = Office::forCompany($company->id)->get();
$companyDepartments = Department::forCompany($company->id)->get();
$companyUnits = Unit::forCompany($company->id)->get();
```

### Validation and Constraints

All models include built-in validation through observers to prevent:
- Circular references in hierarchies
- Staff without office or department assignments
- Invalid parent-child relationships

## Next Steps

- Learn about [Traits & Behaviors](traits.md)
- Explore [Policies & Authorization](policies.md)
- Check out [Examples](examples.md)