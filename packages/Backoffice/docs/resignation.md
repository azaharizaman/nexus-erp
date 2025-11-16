# Staff Resignation Management

The BackOffice package provides comprehensive staff resignation management capabilities, allowing you to schedule resignations in advance and automatically process them when due.

## Table of Contents

- [Overview](#overview)
- [Resignation Workflow](#resignation-workflow)
- [Basic Usage](#basic-usage)
- [Advanced Features](#advanced-features)
- [Command Line Processing](#command-line-processing)
- [Scopes and Queries](#scopes-and-queries)
- [Validation and Business Rules](#validation-and-business-rules)
- [Examples](#examples)

## Overview

The resignation system allows you to:

1. **Schedule resignations** with future effective dates
2. **Automatically process** resignations when they become due
3. **Track resignation reasons** and related information
4. **Cancel scheduled resignations** if circumstances change
5. **Maintain historical records** of resignation activities

### Key Features

- ✅ **Future-dated resignations** - Schedule resignations to take effect on specific dates
- ✅ **Automatic processing** - Daily command to process due resignations
- ✅ **Reason tracking** - Store detailed resignation reasons
- ✅ **Cancellation support** - Cancel scheduled resignations before they take effect
- ✅ **Validation** - Prevent invalid resignation scenarios
- ✅ **Status management** - Automatic status transitions and validation

## Resignation Workflow

```
Active Staff → Schedule Resignation → Pending Resignation → Process → Resigned Staff
     ↑                                        ↓
     └─────────── Cancel Resignation ←──────┘
```

### Status Transitions

1. **Active** → **Pending Resignation**: When a resignation is scheduled
2. **Pending Resignation** → **Resigned**: When resignation date is reached and processed
3. **Pending Resignation** → **Active**: When resignation is cancelled
4. **Resigned** → **Active**: When staff is reactivated (clears resignation data)

## Basic Usage

### Scheduling a Resignation

```php
use AzahariZaman\BackOffice\Models\Staff;
use Carbon\Carbon;

// Get the staff member
$staff = Staff::find(1);

// Schedule resignation for 30 days from now
$resignationDate = Carbon::now()->addDays(30);
$resignationReason = 'Found a better opportunity elsewhere';

$staff->scheduleResignation($resignationDate, $resignationReason);

// Check if resignation is pending
if ($staff->hasPendingResignation()) {
    echo "Resignation scheduled for: " . $staff->resignation_date->format('Y-m-d');
    echo "Days until resignation: " . $staff->getDaysUntilResignation();
}
```

### Processing a Resignation

```php
// Process resignation immediately (typically done by command)
$staff->processResignation();

// Check if staff is resigned
if ($staff->isResigned()) {
    echo "Staff resigned on: " . $staff->resigned_at->format('Y-m-d H:i:s');
}
```

### Cancelling a Resignation

```php
// Cancel a scheduled resignation
$staff->cancelResignation();

// Resignation data is cleared
assert($staff->resignation_date === null);
assert($staff->resignation_reason === null);
assert(!$staff->hasPendingResignation());
```

## Advanced Features

### Status Checking

```php
use AzahariZaman\BackOffice\Enums\StaffStatus;

$staff = Staff::find(1);

// Check various resignation states
$isPending = $staff->hasPendingResignation();
$isDue = $staff->isResignationDue();
$isResigned = $staff->isResigned();
$daysUntil = $staff->getDaysUntilResignation();

// Status enum methods
$isTerminated = $staff->status->isTerminated(); // true for RESIGNED, TERMINATED, RETIRED
$isAvailable = $staff->status->isAvailable();   // true for ACTIVE, ON_LEAVE
```

### Bulk Operations

```php
// Get all staff with pending resignations
$pendingResignations = Staff::pendingResignation()->get();

// Get resignations due today or earlier
$dueResignations = Staff::pendingResignation()
    ->whereDate('resignation_date', '<=', now()->toDateString())
    ->get();

// Process all due resignations
foreach ($dueResignations as $staff) {
    $staff->processResignation();
}
```

## Command Line Processing

The package includes an Artisan command for automated resignation processing:

### Daily Processing Command

```bash
# Process all resignations due today or earlier
php artisan backoffice:process-resignations

# Force processing without confirmation
php artisan backoffice:process-resignations --force

# Dry run to see what would be processed
php artisan backoffice:process-resignations --dry-run
```

### Setting Up Automation

Add to your `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Process resignations daily at 6 AM
    $schedule->command('backoffice:process-resignations --force')
             ->dailyAt('06:00')
             ->emailOutputOnFailure('admin@company.com');
}
```

Or set up a cron job:

```bash
# Add to crontab
0 6 * * * cd /path/to/project && php artisan backoffice:process-resignations --force
```

## Scopes and Queries

### Available Scopes

```php
// Staff with pending resignations
$pending = Staff::pendingResignation()->get();

// Already resigned staff
$resigned = Staff::resigned()->get();

// Filter by specific status
$active = Staff::byStatus(StaffStatus::ACTIVE)->get();
$resigned = Staff::byStatus(StaffStatus::RESIGNED)->get();

// Combined queries
$resigningThisMonth = Staff::pendingResignation()
    ->whereBetween('resignation_date', [
        now()->startOfMonth(),
        now()->endOfMonth()
    ])->get();
```

### Custom Queries

```php
// Staff resigning in the next 7 days
$resigningSoon = Staff::pendingResignation()
    ->whereDate('resignation_date', '<=', now()->addDays(7))
    ->orderBy('resignation_date')
    ->get();

// Recently resigned staff (last 30 days)
$recentlyResigned = Staff::resigned()
    ->where('resigned_at', '>=', now()->subDays(30))
    ->orderBy('resigned_at', 'desc')
    ->get();
```

## Validation and Business Rules

### Automatic Validation

The system automatically enforces these rules:

1. **New staff cannot have past resignation dates**
2. **Resigned status automatically sets `resigned_at` timestamp**
3. **Resigned staff are automatically marked as inactive**
4. **Reactivating resigned staff clears resignation data**

### Custom Validation

```php
// In a form request or validator
'resignation_date' => [
    'nullable',
    'date',
    'after:today', // Only allow future dates for new resignations
],
'resignation_reason' => [
    'nullable',
    'string',
    'max:1000',
],
```

## Examples

### Complete Resignation Workflow

```php
use AzahariZaman\BackOffice\Models\Staff;
use AzahariZaman\BackOffice\Enums\StaffStatus;
use Carbon\Carbon;

// 1. Create active staff
$staff = Staff::create([
    'name' => 'John Doe',
    'email' => 'john@company.com',
    'employee_id' => 'EMP001',
    'department_id' => 1,
    'status' => StaffStatus::ACTIVE,
    'is_active' => true,
]);

// 2. Schedule resignation
$resignationDate = Carbon::now()->addDays(14);
$reason = 'Relocating to another city';

$staff->scheduleResignation($resignationDate, $reason);

echo "Resignation scheduled for: " . $staff->resignation_date->format('Y-m-d');
echo "Reason: " . $staff->resignation_reason;

// 3. Check status before processing
if ($staff->isResignationDue()) {
    echo "Resignation is due for processing";
} else {
    echo "Days until resignation: " . $staff->getDaysUntilResignation();
}

// 4. Process resignation (when due)
$staff->processResignation();

// 5. Verify final state
assert($staff->status === StaffStatus::RESIGNED);
assert($staff->resigned_at !== null);
assert($staff->is_active === false);
```

### Handling Resignation Cancellation

```php
// Staff changes mind before resignation date
$staff = Staff::findByEmployeeId('EMP001');

if ($staff->hasPendingResignation()) {
    $daysUntil = $staff->getDaysUntilResignation();
    
    if ($daysUntil > 0) {
        // Cancel resignation
        $staff->cancelResignation();
        
        echo "Resignation cancelled successfully";
        
        // Staff remains active
        assert($staff->status === StaffStatus::ACTIVE);
        assert($staff->resignation_date === null);
        assert($staff->resignation_reason === null);
    } else {
        echo "Cannot cancel - resignation is already due";
    }
}
```

### Reporting and Analytics

```php
// Monthly resignation report
$currentMonth = now()->startOfMonth();
$nextMonth = now()->addMonth()->startOfMonth();

$resignationsThisMonth = Staff::resigned()
    ->whereBetween('resigned_at', [$currentMonth, $nextMonth])
    ->count();

$pendingResignations = Staff::pendingResignation()
    ->whereBetween('resignation_date', [$currentMonth, $nextMonth])
    ->count();

$upcomingResignations = Staff::pendingResignation()
    ->whereDate('resignation_date', '<=', now()->addDays(30))
    ->orderBy('resignation_date')
    ->get(['name', 'employee_id', 'resignation_date', 'resignation_reason']);

// Department-wise resignation stats
$departmentStats = Staff::resigned()
    ->where('resigned_at', '>=', now()->startOfYear())
    ->join('backoffice_departments', 'department_id', '=', 'backoffice_departments.id')
    ->groupBy('department_id', 'backoffice_departments.name')
    ->selectRaw('department_id, backoffice_departments.name, COUNT(*) as resignation_count')
    ->get();
```

### Integration with Events

```php
// In AppServiceProvider or EventServiceProvider
use AzahariZaman\BackOffice\Models\Staff;

Staff::observe(class {
    public function updated(Staff $staff) {
        // Send notification when resignation is scheduled
        if ($staff->wasChanged('resignation_date') && $staff->resignation_date) {
            // Notify HR department
            Mail::to('hr@company.com')->send(new ResignationScheduled($staff));
        }
        
        // Send notification when staff resigns
        if ($staff->wasChanged('status') && $staff->status === StaffStatus::RESIGNED) {
            // Notify relevant parties
            Mail::to('hr@company.com')->send(new StaffResigned($staff));
        }
    }
});
```

## Best Practices

### 1. Regular Processing
Set up daily automated processing to ensure resignations are handled promptly:

```bash
# Schedule in cron
0 6 * * * cd /path/to/project && php artisan backoffice:process-resignations --force
```

### 2. Notification System
Implement notifications for key events:
- When resignation is scheduled
- Reminders before resignation date
- When resignation is processed

### 3. Data Retention
Consider your data retention policies:
- Keep resigned staff records for reporting
- Archive old resignation data
- Maintain audit trails

### 4. Validation
Always validate resignation dates and reasons:
- Prevent past dates for new resignations
- Require resignation reasons for reporting
- Validate business rules specific to your organization

### 5. Testing
Test resignation scenarios thoroughly:
- Schedule and process resignations
- Test cancellation workflows
- Verify data integrity after operations

## Troubleshooting

### Common Issues

1. **Past resignation dates**: Ensure resignation dates are in the future for new staff
2. **Command not running**: Check cron setup and Laravel scheduler configuration
3. **Status inconsistencies**: Run `php artisan backoffice:process-resignations --force` to sync statuses
4. **Missing resignation data**: Verify migration was run and database schema is up to date

### Debugging

```php
// Check resignation status
$staff = Staff::find(1);
dd([
    'has_pending' => $staff->hasPendingResignation(),
    'is_due' => $staff->isResignationDue(),
    'days_until' => $staff->getDaysUntilResignation(),
    'resignation_date' => $staff->resignation_date,
    'resigned_at' => $staff->resigned_at,
    'status' => $staff->status->value,
]);

// Check command output
php artisan backoffice:process-resignations --dry-run
```

This resignation management system provides a robust foundation for handling staff departures while maintaining data integrity and providing the flexibility needed for various business scenarios.