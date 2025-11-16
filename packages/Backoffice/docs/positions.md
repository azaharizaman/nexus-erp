# Position Model Documentation

## Overview

The Position model represents job positions within an organization's hierarchical structure. It replaces the previous string-based position field in the Staff model with a robust, structured approach that enables:

- **Type-safe position categorization** using PositionType enum
- **Default department assignment** for positions
- **Department precedence logic** where staff's own department takes precedence over position's default department
- **Hierarchical classification** from C-Level to Assistant positions
- **Grade/salary band management** through the `gred` field

## Table Structure

**Table**: `backoffice_positions`

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | bigint | No | Primary key |
| `company_id` | bigint | No | Foreign key to companies table |
| `department_id` | bigint | Yes | Optional default department for this position |
| `name` | string(100) | No | Position name (e.g., "Senior Manager") |
| `code` | string(50) | No | Unique position code (e.g., "MGR-001") |
| `gred` | string(50) | Yes | Salary grade or band (e.g., "M52", "C", "D") |
| `type` | string(50) | No | Position type (PositionType enum) |
| `description` | text | Yes | Detailed position description |
| `is_active` | boolean | No | Whether position is active (default: true) |
| `created_at` | timestamp | No | Record creation timestamp |
| `updated_at` | timestamp | No | Record update timestamp |

### Indexes

- Primary key on `id`
- Composite index on `(company_id, is_active)`
- Composite index on `(company_id, type)`
- Index on `department_id`
- Unique constraint on `code`

## Position Types

The `PositionType` enum provides a hierarchical classification system:

| Type | Level | Label | Description |
|------|-------|-------|-------------|
| `C_LEVEL` | 1 | C-Level | Chief executive positions (CEO, CFO, CTO, etc.) |
| `TOP_MANAGEMENT` | 2 | Top Management | Vice Presidents, Senior VPs, General Managers |
| `MANAGEMENT` | 3 | Management | Managers, Directors, Department Heads |
| `JUNIOR_MANAGEMENT` | 4 | Junior Management | Assistant Managers, Team Leaders, Supervisors |
| `SENIOR_EXECUTIVE` | 5 | Senior Executive | Senior Executives, Senior Officers, Senior Specialists |
| `EXECUTIVE` | 6 | Executive | Executives, Officers, Specialists |
| `JUNIOR_EXECUTIVE` | 7 | Junior Executive | Junior Executives, Junior Officers, Assistant Officers |
| `NON_EXECUTIVE` | 8 | Non-Executive | Technicians, Coordinators, Support Staff |
| `CLERICAL` | 9 | Clerical | Clerks, Data Entry, Secretaries |
| `ASSISTANT` | 10 | Assistant | General Assistants, Admin Assistants, Office Assistants |

### Position Type Methods

```php
// Get human-readable label
$position->type->label(); // "C-Level"

// Get hierarchical level (1 = highest)
$position->type->level(); // 1

// Check if management level
$position->type->isManagement(); // true for C_LEVEL through JUNIOR_MANAGEMENT

// Check if executive level
$position->type->isExecutive(); // true for SENIOR_EXECUTIVE through JUNIOR_EXECUTIVE

// Get all position types
PositionType::values(); // ['c_level', 'top_management', ...]
PositionType::options(); // ['c_level' => 'C-Level', ...]
```

## Model Relationships

### Belongs To

```php
// Company (required)
$position->company; // Company instance

// Department (optional - default department for this position)
$position->department; // Department instance or null
```

### Has Many

```php
// All staff in this position
$position->staff; // Collection of Staff

// Only active staff
$position->activeStaff; // Collection of active Staff
```

## Department Precedence Logic

The Position model implements an important precedence rule for department assignments:

### The Rule

**Staff's own department takes precedence over position's default department.**

### Scenarios

#### Scenario 1: Staff has NO department, Position has default department

```php
$position = Position::factory()->withDepartment($dept1)->create();
$staff = Staff::factory()->create([
    'position_id' => $position->id,
    'department_id' => null, // No staff department
]);

$staff->getEffectiveDepartment(); // Returns $dept1
$staff->getEffectiveDepartmentId(); // Returns $dept1->id
```

#### Scenario 2: Staff HAS department, Position has default department

```php
$position = Position::factory()->withDepartment($dept1)->create();
$staff = Staff::factory()->create([
    'position_id' => $position->id,
    'department_id' => $dept2->id, // Staff has own department
]);

$staff->getEffectiveDepartment(); // Returns $dept2 (staff's department takes precedence)
$staff->getEffectiveDepartmentId(); // Returns $dept2->id
```

#### Scenario 3: No department anywhere

```php
$position = Position::factory()->create(['department_id' => null]);
$staff = Staff::factory()->create([
    'position_id' => $position->id,
    'department_id' => null,
]);

$staff->getEffectiveDepartment(); // Returns null
$staff->getEffectiveDepartmentId(); // Returns null
```

### Methods

```php
// On Staff model
$staff->getEffectiveDepartment(): ?Department
$staff->getEffectiveDepartmentId(): ?int

// On Position model
$position->hasDefaultDepartment(): bool
```

## Usage Examples

### Creating Positions

```php
use AzahariZaman\BackOffice\Models\Position;
use AzahariZaman\BackOffice\Enums\PositionType;

// Create a basic position
$position = Position::create([
    'company_id' => $company->id,
    'name' => 'Senior Manager',
    'code' => 'MGR-SR-001',
    'gred' => 'M52',
    'type' => PositionType::MANAGEMENT,
    'description' => 'Senior management position responsible for departmental operations',
    'is_active' => true,
]);

// Create position with default department
$position = Position::create([
    'company_id' => $company->id,
    'department_id' => $department->id,
    'name' => 'IT Manager',
    'code' => 'IT-MGR-001',
    'type' => PositionType::MANAGEMENT,
]);
```

### Using Factory

```php
// Create position with specific type
$ceo = Position::factory()->cLevel()->create();
$manager = Position::factory()->management()->create();
$executive = Position::factory()->executive()->create();

// Create with default department
$position = Position::factory()
    ->for($company)
    ->withDepartment($department)
    ->create();

// Create inactive position
$position = Position::factory()->inactive()->create();
```

### Querying Positions

```php
// Active positions only
$active = Position::active()->get();

// Positions by company
$companyPositions = Position::byCompany($company)->get();

// Positions by department
$deptPositions = Position::byDepartment($department)->get();

// Positions by type
$managers = Position::byType(PositionType::MANAGEMENT)->get();

// All management positions
$management = Position::management()->get();

// All executive positions
$executives = Position::executive()->get();

// Chain scopes
$activeManagement = Position::active()
    ->management()
    ->byCompany($company)
    ->get();
```

### Position Information

```php
// Check if position has default department
if ($position->hasDefaultDepartment()) {
    echo "Default department: " . $position->department->name;
}

// Get hierarchical level
$level = $position->getLevel(); // 1-10 (1 = highest)

// Check position category
if ($position->isManagement()) {
    echo "This is a management position";
}

if ($position->isExecutive()) {
    echo "This is an executive position";
}

// Count staff
$totalStaff = $position->getStaffCount();
$activeStaff = $position->getActiveStaffCount();
```

### Assigning Positions to Staff

```php
// Create staff with position
$staff = Staff::factory()->create([
    'position_id' => $position->id,
    'office_id' => $office->id,
]);

// Update staff position
$staff->update(['position_id' => $newPosition->id]);

// Get staff's effective department
$effectiveDept = $staff->getEffectiveDepartment();

// Check staff's position
if ($staff->position) {
    echo "Position: " . $staff->position->name;
    echo "Type: " . $staff->position->type->label();
    echo "Level: " . $staff->position->getLevel();
}
```

## Staff Transfers with Positions

When transferring staff, positions can be changed along with office, department, and supervisor:

```php
use AzahariZaman\BackOffice\Models\StaffTransfer;

// Create transfer with position change
$transfer = StaffTransfer::create([
    'staff_id' => $staff->id,
    'from_office_id' => $currentOffice->id,
    'to_office_id' => $targetOffice->id,
    'from_position_id' => $staff->position_id,
    'to_position_id' => $newPosition->id,
    'effective_date' => now()->addWeek(),
    'reason' => 'Promotion',
    'requested_by_id' => $manager->id,
]);

// Observer automatically fills from_position_id if not provided
$transfer = StaffTransfer::create([
    'staff_id' => $staff->id,
    'from_office_id' => $currentOffice->id,
    'to_office_id' => $targetOffice->id,
    'to_position_id' => $newPosition->id, // from_position_id auto-filled
    'effective_date' => now()->addWeek(),
]);

// Transfer processes automatically update staff position_id
```

## Authorization

The `PositionPolicy` provides authorization logic for position operations. By default:

- **View**: Allowed for all authenticated users
- **Create/Update/Delete**: Restricted (customize as needed)
- **Delete**: Cannot delete positions with staff assigned

```php
// Check authorization
Gate::authorize('create', Position::class);
Gate::authorize('update', $position);
Gate::authorize('delete', $position);
```

## Best Practices

### 1. Always Set Company

```php
// GOOD
$position = Position::factory()->for($company)->create();

// BAD - will create a new company
$position = Position::factory()->create();
```

### 2. Use Factory States

```php
// GOOD - clear and descriptive
$ceo = Position::factory()->cLevel()->for($company)->create();
$manager = Position::factory()->management()->for($company)->create();

// LESS CLEAR
$position = Position::factory()->create(['type' => PositionType::C_LEVEL]);
```

### 3. Check for Position Before Accessing

```php
// GOOD
if ($staff->position) {
    echo $staff->position->name;
}

// GOOD - using null-safe operator
echo $staff->position?->name ?? 'No position assigned';

// BAD - can cause null pointer errors
echo $staff->position->name;
```

### 4. Use Effective Department Methods

```php
// GOOD - considers both staff and position department
$dept = $staff->getEffectiveDepartment();

// LESS FLEXIBLE - only gets staff's direct department
$dept = $staff->department;
```

### 5. Unique Position Codes

```php
// GOOD - use consistent naming convention
$code = sprintf('%s-%s-%03d', $company->code, $type, $sequence);
// Example: "ACME-MGR-001"

// GOOD - make it meaningful
$position->code = 'IT-MANAGER-SENIOR';
```

## Migration from String-based Position

If upgrading from the old string-based position field:

### 1. Create positions from existing data

```php
$uniquePositions = Staff::whereNotNull('position')
    ->pluck('position')
    ->unique();

foreach ($uniquePositions as $positionName) {
    Position::create([
        'company_id' => $defaultCompany->id,
        'name' => $positionName,
        'code' => Str::slug($positionName),
        'type' => PositionType::EXECUTIVE, // Assign appropriate type
    ]);
}
```

### 2. Link staff to new positions

```php
$positions = Position::all()->keyBy('name');

Staff::whereNotNull('position')->chunk(100, function ($staffBatch) use ($positions) {
    foreach ($staffBatch as $staff) {
        $position = $positions[$staff->position] ?? null;
        if ($position) {
            $staff->update(['position_id' => $position->id]);
        }
    }
});
```

### 3. Remove old position column

The migration already handles this - the `position` string column has been replaced with `position_id` foreign key.

## Testing

Comprehensive tests are provided in `tests/Feature/PositionTest.php`:

```bash
# Run position tests
php vendor/bin/phpunit tests/Feature/PositionTest.php

# Run with coverage
php vendor/bin/phpunit tests/Feature/PositionTest.php --coverage-html coverage
```

Test coverage includes:
- ✅ Position creation and validation
- ✅ Relationships (company, department, staff)
- ✅ Query scopes (active, by company, by department, by type)
- ✅ Type-based filtering (management, executive)
- ✅ Department precedence logic
- ✅ Staff count methods
- ✅ Position type methods

## Related Documentation

- [Staff Model Documentation](./models.md#staff)
- [Department Model Documentation](./models.md#department)
- [Staff Transfers Documentation](./staff-transfers.md)
- [Factory Documentation](./factories.md)
