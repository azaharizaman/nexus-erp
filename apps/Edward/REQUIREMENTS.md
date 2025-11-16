# Edward CLI Demo - Field Service Module Requirements

**Version:** 1.0.0  
**Last Updated:** November 15, 2025  
**Status:** Initial Requirements - Terminal Interface for Field Service

---

## Executive Summary

**Edward CLI Demo** is a pure terminal-based demonstration application for Nexus ERP, showcasing the power of headless ERP systems through command-line interfaces. This document defines the Field Service Management menu requirements for Edward, demonstrating how the `nexus-field-service` package can be consumed via terminal operations.

### Core Philosophy

1. **Action Orchestration** - All operations invoke Laravel Actions from `Nexus\Erp\Actions\FieldService\*`
2. **Pure Terminal Interface** - No web routes, no API endpoints, no views
3. **Interactive Menus** - Using Laravel Prompts for elegant terminal UX
4. **Headless Demonstration** - Proves ERP doesn't need GUIs to be powerful
5. **CLI-First Approach** - Perfect for automation, scripting, and remote management

---

## Field Service Menu Structure

### Main Menu Integration

Add **Field Service Management** to Edward's main menu as the 8th module:

```
═══ EDWARD MAIN MENU ═══

  ❯ 🏢 Tenant Management (8 operations)
    👤 User Management (9 operations)
    📦 Inventory Management (9 operations)
    ⚙️  Settings & Configuration (9 operations)
    📊 Reports & Analytics (9 operations)
    🔍 Search & Query (9 operations)
    📝 Audit Logs (9 operations)
    🔧 Field Service Management (10 operations)  ← NEW MODULE
    🚪 Exit Edward
```

### Field Service Sub-Menu

When user selects **Field Service Management**, display:

```
═══ FIELD SERVICE MANAGEMENT ═══

  ❯ 📋 List Service Orders (view all work orders)
    ➕ Create Service Order (new service request)
    👨‍🔧 Assign Technician (dispatch technician to job)
    📅 View Technician Schedule (daily/weekly calendar)
    🚀 Start Job (technician begins work)
    ✅ Complete Job (finish and capture details)
    📄 Generate Service Report (create PDF report)
    🔧 Manage Assets (equipment/asset tracking)
    📊 View SLA Status (compliance dashboard)
    🔙 Back to Main Menu
```

---

## Functional Requirements

### FR-001: List Service Orders

**Command:** `php artisan erp:field-service:list-orders`

**Menu Option:** "📋 List Service Orders (view all work orders)"

**Interaction Flow:**
1. Display filter options:
   - Status (all, new, scheduled, in_progress, completed, closed)
   - Priority (all, low, normal, high, urgent)
   - Assigned Technician (all, specific technician)
   - Date Range (today, this week, this month, custom)
2. Fetch work orders via `ListServiceOrdersAction`
3. Display table with columns:
   - Work Order #
   - Customer
   - Service Location
   - Status
   - Priority
   - Assigned Technician
   - Scheduled Date
   - SLA Status (on-time / at-risk / breached)
4. Allow selection for drill-down details
5. Options: View Details, Assign Technician, Update Status, Back

**Action Invoked:** `Nexus\Erp\Actions\FieldService\ListServiceOrdersAction`

**Success Criteria:**
- Display up to 25 orders per page with pagination
- Color-coded status (green=completed, yellow=in_progress, red=breached SLA)
- Response time < 2 seconds for 100 orders

---

### FR-002: Create Service Order

**Command:** `php artisan erp:field-service:create-order`

**Menu Option:** "➕ Create Service Order (new service request)"

**Interaction Flow:**
1. Prompt for customer selection (search by name or ID)
2. Prompt for service location (select from customer locations or enter new)
3. Select work category:
   - Maintenance
   - Installation
   - Inspection
   - Cleaning
   - Repair
   - Emergency
4. Select priority:
   - Low
   - Normal
   - High
   - Urgent
5. Enter description of work required (multi-line text input)
6. Optional: Link to asset/equipment (search and select)
7. Optional: Link to service contract (if customer has active contract)
8. Select scheduled date and time (or leave blank for immediate)
9. Confirm details and create
10. Display created work order number and success message

**Action Invoked:** `Nexus\Erp\Actions\FieldService\CreateServiceOrderAction`

**Validation:**
- Customer is required
- Service location is required
- Work category is required
- Priority is required
- Description minimum 10 characters
- If service contract linked, validate contract is active

**Success Criteria:**
- Work order created in < 1 second
- Auto-generate work order number via nexus-sequencing
- If SLA contract exists, auto-calculate SLA deadlines
- Audit log entry created

---

### FR-003: Assign Technician

**Command:** `php artisan erp:field-service:assign-technician`

**Menu Option:** "👨‍🔧 Assign Technician (dispatch technician to job)"

**Interaction Flow:**
1. Select work order (search by number or select from list)
2. Display work order details:
   - Customer, location, work category, priority
   - Required skills (if defined)
   - Scheduled date/time
3. Display available technicians:
   - Technician name
   - Current location (if GPS enabled)
   - Skills (highlight matching skills)
   - Availability status (available / busy / on-leave)
   - Current workload (jobs scheduled today)
4. Select technician from list
5. Optionally override auto-assignment (explain why)
6. Confirm assignment
7. Trigger notification to technician (email/SMS simulation in terminal)

**Action Invoked:** `Nexus\Erp\Actions\FieldService\AssignTechnicianAction`

**Business Rules:**
- Technician must have required skills (if skills defined on job)
- Technician must be available (not on leave, not at capacity)
- Warn if technician is already overloaded (> 8 jobs today)
- Warn if technician is far from service location (> 50km)

**Success Criteria:**
- Assignment completes in < 1 second
- Technician receives notification (simulated in terminal)
- Audit log entry created
- Work order status changes from "new" to "assigned"

---

### FR-004: View Technician Schedule

**Command:** `php artisan erp:field-service:schedule`

**Menu Option:** "📅 View Technician Schedule (daily/weekly calendar)"

**Interaction Flow:**
1. Select view mode:
   - Daily (show today's schedule for all technicians)
   - Weekly (show this week's schedule for all technicians)
   - Technician-specific (select one technician, show their schedule)
2. Display schedule in table format:
   - Rows: Time slots (8 AM - 5 PM)
   - Columns: Technicians
   - Cells: Assigned jobs (show work order #, customer, location)
3. Color-code by priority: Red (urgent), Yellow (high), Green (normal), Blue (low)
4. Show travel time between jobs (if route optimization enabled)
5. Options: Assign New Job, Reassign Job, View Job Details, Back

**Action Invoked:** `Nexus\Erp\Actions\FieldService\GetTechnicianScheduleAction`

**Success Criteria:**
- Schedule loads in < 2 seconds
- Display up to 10 technicians side-by-side
- Show conflicts (overlapping appointments) in red
- Calculate total scheduled hours per technician

---

### FR-005: Start Job

**Command:** `php artisan erp:field-service:start-job`

**Menu Option:** "🚀 Start Job (technician begins work)"

**Interaction Flow:**
1. Select technician (from list of technicians with assigned jobs)
2. Display technician's assigned jobs for today
3. Select job to start
4. Display job details:
   - Customer, location, work category
   - Description, special instructions
   - Required parts (if pre-allocated)
5. Confirm job start
6. Capture start time (current timestamp)
7. Optional: Capture GPS location (simulate: "GPS: 3.1390° N, 101.6869° E")
8. Display success message: "Job started at 10:35 AM"

**Action Invoked:** `Nexus\Erp\Actions\FieldService\StartJobAction`

**Business Rules:**
- Cannot start job if not assigned to technician
- Cannot start job if another job is already in progress for same technician
- Work order status changes from "assigned" to "in_progress"

**Success Criteria:**
- Job starts in < 1 second
- Start time and GPS location captured
- Audit log entry created
- SLA response timer stops (if applicable)

---

### FR-006: Complete Job

**Command:** `php artisan erp:field-service:complete-job`

**Menu Option:** "✅ Complete Job (finish and capture details)"

**Interaction Flow:**
1. Select technician (from list of technicians with in-progress jobs)
2. Display technician's in-progress jobs
3. Select job to complete
4. Capture job completion details:
   - Work performed (multi-line text input)
   - Parts used (select from inventory, enter quantities)
   - Photos uploaded (simulate: "Before: photo1.jpg, After: photo2.jpg")
   - Customer feedback/notes
   - Customer signature (simulate: "Signature captured: John Doe, 2025-11-15 14:30")
5. Confirm completion
6. Capture end time (current timestamp)
7. Calculate labor hours (end time - start time)
8. Display success message: "Job completed. Labor hours: 3.5. Service report will be generated."

**Action Invoked:** `Nexus\Erp\Actions\FieldService\CompleteJobAction`

**Business Rules:**
- Cannot complete job if not in "in_progress" status
- Parts consumption auto-deducts from inventory (technician van stock first, then warehouse)
- Work order status changes from "in_progress" to "completed"
- Trigger service report generation (auto-email to customer)

**Success Criteria:**
- Job completes in < 3 seconds (including inventory updates)
- Labor hours calculated accurately
- Parts deducted from inventory
- Service report queued for generation
- Audit log entry created

---

### FR-007: Generate Service Report

**Command:** `php artisan erp:field-service:generate-report`

**Menu Option:** "📄 Generate Service Report (create PDF report)"

**Interaction Flow:**
1. Select work order (from completed jobs without reports)
2. Display work order details for confirmation
3. Select report template:
   - Standard Service Report
   - Detailed Technical Report
   - Warranty Service Report
4. Generate PDF report (simulate: "Generating PDF... Done")
5. Display report summary:
   - Report number (auto-generated)
   - Work order number
   - Customer name
   - Service performed
   - Parts used
   - Labor hours
   - Total cost (if billing enabled)
6. Options:
   - View Report (display PDF content in terminal as text)
   - Email to Customer (simulate: "Email sent to customer@example.com")
   - Save to File System (simulate: "/storage/service-reports/SR-2025-11-15-0001.pdf")
   - Back

**Action Invoked:** `Nexus\Erp\Actions\FieldService\GenerateServiceReportAction`

**Success Criteria:**
- Report generates in < 5 seconds
- PDF includes: company logo, customer signature, before/after photos
- Auto-email to customer (if configured)
- Work order status changes from "completed" to "verified"
- Audit log entry created

---

### FR-008: Manage Assets

**Command:** `php artisan erp:field-service:assets`

**Menu Option:** "🔧 Manage Assets (equipment/asset tracking)"

**Interaction Flow:**
1. Display sub-menu:
   - List All Assets
   - Create New Asset
   - View Asset Details
   - View Asset Service History
   - Schedule Preventive Maintenance
   - Back
2. **List All Assets:**
   - Display table: Asset Number, Type, Customer, Location, Condition, Next PM Due
   - Filter by: Customer, Asset Type, Condition
3. **Create New Asset:**
   - Prompt: Customer, Service Location, Asset Type, Manufacturer, Model, Serial Number
   - Optional: Installation Date, Warranty Expiry
4. **View Asset Details:**
   - Display: All asset info + service history (all work orders linked to asset)
5. **Schedule Preventive Maintenance:**
   - Select asset
   - Define schedule: Frequency (monthly/quarterly/yearly), Next Due Date
   - Assign default technician (optional)
   - Auto-generate PM work orders

**Action Invoked:**
- `Nexus\Erp\Actions\FieldService\ListAssetsAction`
- `Nexus\Erp\Actions\FieldService\CreateAssetAction`
- `Nexus\Erp\Actions\FieldService\ViewAssetHistoryAction`
- `Nexus\Erp\Actions\FieldService\SchedulePreventiveMaintenanceAction`

**Success Criteria:**
- Asset list loads in < 2 seconds
- Asset creation completes in < 1 second
- Service history displays all linked work orders
- PM schedules auto-generate work orders 7 days before due date

---

### FR-009: View SLA Status

**Command:** `php artisan erp:field-service:sla-status`

**Menu Option:** "📊 View SLA Status (compliance dashboard)"

**Interaction Flow:**
1. Display SLA compliance dashboard:
   - **SLA Response Time Compliance:** 92% (46/50 jobs within SLA)
   - **SLA Resolution Time Compliance:** 88% (44/50 jobs within SLA)
   - **Active Jobs with SLA:**
     - Work Order #12345 - Customer A - Response Due: 2 hours (ON TIME)
     - Work Order #12346 - Customer B - Response Due: -30 minutes (BREACHED)
     - Work Order #12347 - Customer C - Resolution Due: 4 hours (AT RISK)
2. Filter by:
   - Contract (all, specific service contract)
   - Status (on-time, at-risk, breached)
   - Technician (all, specific technician)
3. Drill down into breached SLAs:
   - Display work order details
   - Show breach reason (technician unavailable, parts delay, etc.)
   - Options: Escalate, Reassign, Add Notes

**Action Invoked:** `Nexus\Erp\Actions\FieldService\GetSLAStatusAction`

**Success Criteria:**
- Dashboard loads in < 2 seconds
- Real-time SLA timer updates (countdown to deadline)
- Color-coded: Green (on-time), Yellow (at-risk: < 30 min), Red (breached)
- Alerts for breached SLAs

---

### FR-010: Back to Main Menu

**Menu Option:** "🔙 Back to Main Menu"

**Action:** Return to Edward's main menu

---

## Technical Implementation

### Actions to Implement in Nexus\Erp

All field service operations are orchestrated through Laravel Actions in the `src/Actions/FieldService/` directory:

```php
// src/Actions/FieldService/ListServiceOrdersAction.php
namespace Nexus\Erp\Actions\FieldService;

use Lorisleiva\Actions\Concerns\AsAction;
use Nexus\FieldService\Contracts\WorkOrderServiceContract;

class ListServiceOrdersAction
{
    use AsAction;
    
    public function __construct(
        private readonly WorkOrderServiceContract $workOrderService
    ) {}
    
    public function handle(array $filters = []): array
    {
        return $this->workOrderService->list($filters);
    }
    
    // Automatically available as:
    // 1. Command: php artisan erp:field-service:list-orders
    // 2. API: GET /api/v1/field-service/work-orders
    // 3. Direct call: ListServiceOrdersAction::run($filters)
}
```

### Edward Command Implementation

```php
// apps/edward/app/Console/Commands/EdwardMenuCommand.php

protected function showFieldServiceMenu(): void
{
    $choice = select(
        label: '═══ FIELD SERVICE MANAGEMENT ═══',
        options: [
            'list' => '📋 List Service Orders',
            'create' => '➕ Create Service Order',
            'assign' => '👨‍🔧 Assign Technician',
            'schedule' => '📅 View Technician Schedule',
            'start' => '🚀 Start Job',
            'complete' => '✅ Complete Job',
            'report' => '📄 Generate Service Report',
            'assets' => '🔧 Manage Assets',
            'sla' => '📊 View SLA Status',
            'back' => '🔙 Back to Main Menu',
        ]
    );
    
    match ($choice) {
        'list' => $this->listServiceOrders(),
        'create' => $this->createServiceOrder(),
        'assign' => $this->assignTechnician(),
        'schedule' => $this->viewTechnicianSchedule(),
        'start' => $this->startJob(),
        'complete' => $this->completeJob(),
        'report' => $this->generateServiceReport(),
        'assets' => $this->manageAssets(),
        'sla' => $this->viewSLAStatus(),
        'back' => null,
    };
}

protected function listServiceOrders(): void
{
    // Invoke Action
    $orders = ListServiceOrdersAction::run(['status' => 'all']);
    
    // Display in terminal table
    $this->displayServiceOrdersTable($orders);
}
```

### Integration with nexus-field-service Package

Edward consumes the `nexus-field-service` package through:

1. **Action Orchestration:** All operations invoke Actions in `Nexus\Erp\Actions\FieldService\*`
2. **Contract-Based Access:** Actions depend on interfaces from `Nexus\FieldService\Contracts\*`
3. **Event-Driven Updates:** Listen to domain events like `JobCompletedEvent`, `SLABreachedEvent`

### Terminal UI Best Practices

1. **Color Coding:**
   - Green: Completed, On-time
   - Yellow: In Progress, At Risk
   - Red: Urgent, Breached
   - Blue: Scheduled, Low Priority

2. **Progress Indicators:**
   - Use spinner for long operations (> 2 seconds)
   - Show "Loading..." with animation
   - Display success/error messages clearly

3. **Input Validation:**
   - Validate all user inputs before calling Actions
   - Display validation errors inline
   - Provide helpful hints (e.g., "Enter customer name or ID")

4. **Navigation:**
   - Always provide "Back" option
   - Use keyboard shortcuts (1-9 for menu items)
   - Confirm destructive actions (cancel order, delete asset)

---

## Testing Requirements

### Unit Tests

```php
// tests/Unit/Commands/FieldServiceMenuTest.php

test('field service menu displays all 10 options', function () {
    $command = new EdwardMenuCommand();
    
    expect($command->getFieldServiceMenuOptions())
        ->toHaveCount(10)
        ->toContain('List Service Orders')
        ->toContain('View SLA Status');
});

test('list service orders invokes correct Action', function () {
    Mock::spy(ListServiceOrdersAction::class);
    
    $this->artisan('edward:menu')
        ->expectsChoice('field-service', 'list')
        ->assertExitCode(0);
    
    ListServiceOrdersAction::assertInvoked();
});
```

### Integration Tests

```php
// tests/Integration/FieldServiceWorkflowTest.php

test('complete field service workflow in Edward CLI', function () {
    // Create service order
    $order = CreateServiceOrderAction::run([
        'customer_id' => $customer->id,
        'service_location_id' => $location->id,
        'work_category' => 'repair',
        'priority' => 'high',
        'description' => 'AC not cooling',
    ]);
    
    // Assign technician
    AssignTechnicianAction::run([
        'work_order_id' => $order->id,
        'technician_id' => $technician->id,
    ]);
    
    // Start job
    StartJobAction::run(['work_order_id' => $order->id]);
    
    // Complete job
    CompleteJobAction::run([
        'work_order_id' => $order->id,
        'work_performed' => 'Replaced compressor',
        'parts_used' => [['part_id' => $part->id, 'quantity' => 1]],
    ]);
    
    // Generate report
    $report = GenerateServiceReportAction::run(['work_order_id' => $order->id]);
    
    expect($order->fresh()->status)->toBe('completed')
        ->and($report)->not->toBeNull();
});
```

---

## Success Metrics

| Metric | Target | Measurement Period | Why It Matters |
|--------|--------|-------------------|----------------|
| **Menu Navigation Time** | < 5 seconds per operation | Per session | User efficiency |
| **Action Response Time** | < 2 seconds for reads, < 3 seconds for writes | Per operation | Terminal responsiveness |
| **Error Rate** | < 1% of operations fail | Monthly | Reliability |
| **User Adoption (CLI)** | > 20% of field service users prefer CLI | Quarterly | Validates CLI-first approach |
| **Automation Usage** | > 30% of operations via scripted commands | Monthly | Demonstrates automation capability |

---

## Future Enhancements

1. **Voice Commands:** Integrate with terminal voice input for hands-free operation
2. **Batch Operations:** Import/export service orders via CSV
3. **Real-Time Dashboard:** Live updating SLA dashboard with auto-refresh
4. **Offline Mode:** Cache data locally, sync when online
5. **Technician Mobile App:** Extend Edward to mobile devices (Termux on Android)

---

**Document Version:** 1.0.0  
**Last Updated:** November 15, 2025  
**Status:** Ready for Implementation

---

## Notes on CLI-First Philosophy

Edward proves that **headless ERP systems can power ANY interface**, including pure terminal interfaces. The Field Service module demonstrates:

1. **Complete Functionality:** All field service workflows accessible via CLI
2. **Action Orchestration:** Single action classes invoked via CLI, API, queue, events
3. **Developer Experience:** CLI is perfect for automation, testing, and DevOps workflows
4. **Tribute to Legacy:** Honors JD Edwards terminal ERP systems while using modern Laravel patterns

**The future of ERP is API-first, and Edward showcases exactly that vision through terminal interaction.**
