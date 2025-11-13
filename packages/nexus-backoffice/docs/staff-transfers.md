# Staff Transfer System Documentation

## Overview

The Staff Transfer System is a comprehensive feature of the BackOffice Laravel package that enables organizations to manage staff movements between offices, departments, and reporting lines. It supports both immediate and scheduled transfers with full audit trails and automated processing.

## Key Features

- **Flexible Office Transfers**: Move staff between any offices within the company
- **Immediate or Scheduled Transfers**: Support for instant transfers or future-dated transfers
- **Reporting Line Management**: Can remove, change, or maintain supervisor relationships
- **Department Changes**: Optional department transfers alongside office moves
- **Position Updates**: Track position changes during transfers
- **Comprehensive Validation**: Prevents circular references, duplicate transfers, invalid dates
- **Status Workflow**: Complete lifecycle from request to completion
- **Automatic Processing**: Immediate transfers auto-complete, scheduled transfers batch process
- **Full Authorization**: Policy-based permissions for all operations
- **Audit Trail**: Complete tracking of who requested, approved, and processed transfers

## System Architecture

### Core Components

1. **StaffTransfer Model** (`src/Models/StaffTransfer.php`)
   - Central model managing transfer requests and data
   - Status-driven lifecycle with enum validation
   - Rich relationships to all organizational entities

2. **StaffTransferStatus Enum** (`src/Enums/StaffTransferStatus.php`)
   - Type-safe status management with 5 states: PENDING, APPROVED, REJECTED, COMPLETED, CANCELLED
   - Business logic methods for status transitions and validation

3. **StaffTransferPolicy** (`src/Policies/StaffTransferPolicy.php`)
   - Authorization logic for all transfer operations
   - Role-based and hierarchical permissions

4. **StaffTransferObserver** (`src/Observers/StaffTransferObserver.php`)
   - Automatic processing and validation
   - Handles immediate transfer completion and staff record updates

5. **StaffTransferHelper** (`src/Helpers/StaffTransferHelper.php`)
   - Utility functions for validation, statistics, and reporting
   - Transfer impact analysis and organizational insights

6. **ProcessStaffTransfersCommand** (`src/Commands/ProcessStaffTransfersCommand.php`)
   - Artisan command for batch processing scheduled transfers
   - Supports dry-run mode and progress tracking

## Database Schema

The `backoffice_staff_transfers` table includes:

### Core Transfer Information
- `staff_id`: The staff member being transferred
- `from_office_id` / `to_office_id`: Source and destination offices
- `from_department_id` / `to_department_id`: Optional department changes
- `from_supervisor_id` / `to_supervisor_id`: Supervisor relationship changes
- `from_position` / `to_position`: Position/title changes

### Transfer Management
- `status`: Current transfer status (enum)
- `effective_date`: When the transfer becomes effective
- `is_immediate`: Boolean flag for immediate processing
- `reason`: Transfer justification
- `notes`: Additional information

### Audit Trail
- `requested_by_id` / `requested_at`: Who requested the transfer and when
- `approved_by_id` / `approved_at`: Approval details
- `rejected_by_id` / `rejected_at` / `rejection_reason`: Rejection details
- `cancelled_by_id` / `cancelled_at` / `cancellation_reason`: Cancellation details
- `completed_at`: When the transfer was completed

## Usage Examples

### Basic Transfer Request

```php
use AzahariZaman\BackOffice\Models\{Staff, Office, StaffTransfer};
use AzahariZaman\BackOffice\Enums\StaffTransferStatus;

// Create a transfer request
$transfer = StaffTransfer::create([
    'staff_id' => $employee->id,
    'from_office_id' => $currentOffice->id,
    'to_office_id' => $newOffice->id,
    'effective_date' => now()->addWeek(),
    'reason' => 'Project requirements',
    'status' => StaffTransferStatus::PENDING,
    'requested_by_id' => $manager->id,
    'requested_at' => now(),
]);
```

### Using Helper Methods

```php
// Request transfer using Staff model helper
$transfer = $employee->requestTransfer(
    toOffice: $newOffice,
    requestedBy: $manager,
    effectiveDate: now()->addMonth(),
    toDepartment: $newDepartment,
    toSupervisor: $newSupervisor,
    reason: 'Career development opportunity'
);

// Check if staff can be transferred
if ($employee->canBeTransferred()) {
    // Proceed with transfer request
}

// Check for active transfers
if ($employee->hasActiveTransfer()) {
    // Handle existing transfer scenario
}
```

### Transfer Approval Workflow

```php
// Approve a transfer
$transfer->approve($hrManager, 'Approved for business needs');

// Reject a transfer
$transfer->reject($hrManager, 'Insufficient budget allocation');

// Cancel a transfer
$transfer->cancel('Employee withdrew request');
```

### Immediate Transfers

```php
// Create an immediate transfer (auto-processes)
$immediateTransfer = StaffTransfer::create([
    'staff_id' => $employee->id,
    'from_office_id' => $currentOffice->id,
    'to_office_id' => $newOffice->id,
    'effective_date' => now(),
    'is_immediate' => true,
    'status' => StaffTransferStatus::PENDING,
    'requested_by_id' => $manager->id,
]);

// The observer automatically completes immediate transfers
// Staff record is updated immediately
```

### Querying Transfers

```php
// Get pending transfers
$pendingTransfers = StaffTransfer::pending()->get();

// Get approved transfers due for processing
$dueTransfers = StaffTransfer::due()->get();

// Get transfers for specific staff
$staffTransfers = StaffTransfer::forStaff($employee)->get();

// Get transfers between offices
$officeTransfers = StaffTransfer::betweenOffices($fromOffice, $toOffice)->get();
```

### Transfer Statistics

```php
use AzahariZaman\BackOffice\Helpers\StaffTransferHelper;

// Get company transfer statistics
$stats = StaffTransferHelper::getTransferStatistics($company);
echo "Pending transfers: " . $stats['totals']['pending_transfers'];

// Generate transfer impact report
$impact = StaffTransferHelper::generateImpactReport($transfer);
echo "Affected subordinates: " . count($impact['affected_subordinates']);

// Validate transfer request
try {
    StaffTransferHelper::validateTransferRequest($transfer);
} catch (InvalidTransferException $e) {
    echo "Transfer validation failed: " . $e->getMessage();
}
```

## Batch Processing

### Using the Artisan Command

```bash
# Process all due transfers
php artisan backoffice:process-staff-transfers

# Dry run to see what would be processed
php artisan backoffice:process-staff-transfers --dry-run

# Process with custom batch size
php artisan backoffice:process-staff-transfers --batch-size=50

# Process for specific date
php artisan backoffice:process-staff-transfers --date=2025-11-01
```

### Programmatic Processing

```php
use AzahariZaman\BackOffice\Commands\ProcessStaffTransfersCommand;

// Get the command instance
$command = new ProcessStaffTransfersCommand();

// Process transfers programmatically
$command->handle();
```

## Validation Rules

The system enforces several business rules:

### Transfer Validation
- Cannot transfer to the same office
- Staff cannot have multiple pending/approved transfers
- Effective date cannot be in the past (unless immediate)
- Supervisor changes cannot create circular references

### Status Transitions
- Only PENDING transfers can be approved/rejected/cancelled
- Only APPROVED transfers can be completed
- COMPLETED, REJECTED, and CANCELLED are final states

### Authorization Rules
- Staff can request their own transfers
- Supervisors can request transfers for subordinates
- HR staff can approve/reject transfers
- Managers can approve transfers within their departments

## Error Handling

### Custom Exceptions

```php
use AzahariZaman\BackOffice\Exceptions\InvalidTransferException;

try {
    $transfer = StaffTransfer::create($transferData);
} catch (InvalidTransferException $e) {
    // Handle specific transfer validation errors
    switch ($e->getMessage()) {
        case 'Cannot transfer staff to the same office':
            // Handle same office error
            break;
        case 'Staff already has a pending transfer request':
            // Handle duplicate transfer error
            break;
        default:
            // Handle other validation errors
            break;
    }
}
```

### Common Exception Types
- `InvalidTransferException::sameOffice()` - Attempting to transfer to same office
- `InvalidTransferException::pendingTransferExists()` - Duplicate transfer request
- `InvalidTransferException::circularSupervisorReference()` - Invalid supervisor assignment

## Testing

### Feature Test Example

```php
public function test_it_can_create_and_approve_transfer(): void
{
    // Setup test data
    $company = Company::create(['name' => 'Test Company', 'code' => 'TEST']);
    $sourceOffice = Office::create(['name' => 'Source', 'company_id' => $company->id]);
    $targetOffice = Office::create(['name' => 'Target', 'company_id' => $company->id]);
    
    $employee = Staff::create([
        'employee_id' => 'EMP001',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@test.com',
        'company_id' => $company->id,
        'office_id' => $sourceOffice->id,
        'status' => StaffStatus::ACTIVE,
    ]);
    
    $hrManager = Staff::create([
        'employee_id' => 'HR001',
        'first_name' => 'HR',
        'last_name' => 'Manager',
        'email' => 'hr@test.com',
        'company_id' => $company->id,
        'office_id' => $sourceOffice->id,
        'status' => StaffStatus::ACTIVE,
    ]);

    // Create transfer
    $transfer = StaffTransfer::create([
        'staff_id' => $employee->id,
        'from_office_id' => $sourceOffice->id,
        'to_office_id' => $targetOffice->id,
        'effective_date' => now()->addWeek(),
        'status' => StaffTransferStatus::PENDING,
        'requested_by_id' => $employee->id,
    ]);

    // Approve transfer
    $transfer->approve($hrManager, 'Approved for business needs');

    // Assertions
    $this->assertEquals(StaffTransferStatus::APPROVED, $transfer->fresh()->status);
    $this->assertEquals($hrManager->id, $transfer->fresh()->approved_by_id);
    $this->assertNotNull($transfer->fresh()->approved_at);
}
```

## Configuration

### Package Configuration

```php
// config/backoffice.php
return [
    'transfers' => [
        'enable_immediate_processing' => true,
        'batch_size' => 100,
        'notification_settings' => [
            'send_approval_notifications' => true,
            'send_completion_notifications' => true,
        ],
    ],
];
```

## Integration Points

### Event Hooks

The system fires several events that can be listened to:

```php
// In a service provider
Event::listen('staff.transfer.created', function ($transfer) {
    // Send notification to HR
});

Event::listen('staff.transfer.completed', function ($transfer) {
    // Update external systems
    // Send welcome email for new office
});
```

### External System Integration

```php
// Custom processing after transfer completion
class StaffTransferObserver
{
    public function completed(StaffTransfer $transfer): void
    {
        // Update external HR system
        ExternalHRSystem::updateStaffLocation($transfer->staff, $transfer->toOffice);
        
        // Update access control systems
        AccessControlSystem::updateStaffAccess($transfer->staff);
        
        // Send notifications
        NotificationService::sendTransferCompletionNotice($transfer);
    }
}
```

## Performance Considerations

### Database Optimization
- Indexes on `staff_id`, `status`, `effective_date` for efficient querying
- Foreign key constraints ensure data integrity
- Soft deletes not used - completed transfers maintained for audit trail

### Batch Processing
- Process transfers in configurable batches to avoid memory issues
- Use queues for processing large numbers of transfers
- Implement proper error handling and retry logic

### Caching
- Cache transfer statistics for dashboard display
- Cache validation results for complex organizational hierarchy checks

## Security Considerations

### Authorization
- All transfer operations go through policy checks
- Staff can only request their own transfers (unless manager/HR)
- Sensitive operations require elevated permissions

### Audit Trail
- Complete audit trail maintained for all transfer actions
- Immutable record of who did what and when
- Support for compliance and audit requirements

### Data Validation
- Input validation at model level
- Business rule validation prevents invalid states
- Cross-reference validation ensures data consistency

## Troubleshooting

### Common Issues

1. **Transfer validation failures**
   - Check for existing active transfers
   - Verify office IDs are different
   - Ensure effective date is not in past

2. **Permission denied errors**
   - Check user has appropriate role/permissions
   - Verify staff hierarchy relationships
   - Ensure policy methods are correctly implemented

3. **Automatic processing not working**
   - Check observer is registered in service provider
   - Verify `is_immediate` flag is set correctly
   - Ensure no validation errors preventing processing

4. **Batch processing failures**
   - Check database locks or connection issues
   - Verify sufficient memory for batch size
   - Review error logs for specific failures

### Debug Commands

```bash
# Check pending transfers
php artisan tinker
>>> StaffTransfer::pending()->count()

# Check specific transfer status
>>> StaffTransfer::find(1)->status

# Validate transfer manually
>>> StaffTransferHelper::validateTransferRequest($transfer)
```

This documentation provides a comprehensive guide to understanding, implementing, and troubleshooting the Staff Transfer System in the BackOffice Laravel package.