# Nexus Sequencing - Atomic Serial Number Generation

**Status:** ✅ Phase 1 Complete | 🔄 Phase 1.5 Refactoring Planned | ⏳ Phase 2 Enhancements Planned

[![Latest Version](https://img.shields.io/packagist/v/azaharizaman/erp-serial-numbering.svg)](https://packagist.org/packages/azaharizaman/erp-serial-numbering)
[![License](https://img.shields.io/packagist/l/azaharizaman/erp-serial-numbering.svg)](LICENSE.md)

**Atomic, transaction-safe serial number generation for Laravel ERP systems with configurable patterns, scope isolation, and comprehensive audit trails.**

---

## 🎯 What This Package Does

The **Nexus Sequencing** package provides **one core capability**: generating **unique, sequential base identifiers** with atomic database locking.

### ✅ This Package Handles:
- **Atomic Counter Management** - Race-condition-free number generation
- **Pattern Formatting** - Flexible templates like `INV-{YEAR}-{COUNTER:5}`
- **Scope Isolation** - Multi-tenant or multi-department sequences
- **Transaction Safety** - Automatic rollback on failures
- **Reset Periods** - Daily, monthly, yearly, or never
- **Audit Trail** - Complete history of all generations and overrides

### ❌ This Package Does NOT Handle:
- **Sub-Identifiers** (versions `/v2`, spawns `(a)`, copies `1 of 3`) - Your application manages these
- **Document Status** (active/voided) - Use `nexus-workflow` package for lifecycle management
- **Business Logic** - Package is agnostic to what the numbers represent

---

## 📚 Table of Contents

1. [Installation](#installation)
2. [Progressive Journey](#progressive-journey)
   - [Level 1: Simple Direct Usage](#level-1-simple-direct-usage-current-phase-1) ⭐ **Start Here**
   - [Level 2: Advanced Patterns](#level-2-advanced-patterns)
   - [Level 3: Custom Orchestration](#level-3-custom-orchestration)
3. [Pattern Variables Reference](#pattern-variables-reference)
4. [Reset Periods](#reset-periods)
5. [Preview Mode](#preview-mode)
6. [Manual Override](#manual-override)
7. [API Endpoints](#api-endpoints)
8. [Events](#events)
9. [Phase 2 Preview](#phase-2-preview-coming-soon)
10. [Architecture Notes](#architecture-notes)

---

## Installation

### Step 1: Install Package

```bash
composer require azaharizaman/erp-serial-numbering
```

### Step 2: Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=serial-numbering-config
```

Configuration file: `config/serial-numbering.php`

### Step 3: Run Migrations

```bash
php artisan migrate
```

**Tables Created:**
- `serial_number_sequences` - Sequence configurations
- `serial_number_logs` - Audit trail of all generations

### Step 4: Configure Sequence

Create a sequence definition programmatically or via API:

```php
use Nexus\Sequencing\Contracts\SequenceRepositoryContract;

$repository = app(SequenceRepositoryContract::class);

$sequence = $repository->create([
    'tenant_id' => '123',                    // Scope identifier
    'sequence_name' => 'invoices',           // Unique name per tenant
    'pattern' => 'INV-{YEAR}-{COUNTER:5}',  // Format template
    'reset_period' => 'yearly',              // When to reset counter
    'padding' => 5,                          // Minimum digits for counter
]);
```

---

## Progressive Journey

### Level 1: Simple Direct Usage (Current - Phase 1) ⭐

**For:** Most developers who just need unique numbers  
**Complexity:** Simple action call  
**Use When:** You need a unique identifier for a document/record

#### Basic Generation

```php
use Nexus\Sequencing\Actions\GenerateSerialNumberAction;

// Generate next invoice number
$invoiceNumber = GenerateSerialNumberAction::run(
    tenantId: '123',
    sequenceName: 'invoices'
);
// Result: INV-2025-00001
```

#### With Custom Context

```php
$poNumber = GenerateSerialNumberAction::run(
    tenantId: '456',
    sequenceName: 'purchase-orders',
    context: [
        'department_code' => 'IT',
        'prefix' => 'URGENT',
    ]
);
// Result (if pattern is {PREFIX}-PO-{DEPARTMENT}-{COUNTER:4}): URGENT-PO-IT-0001
```

#### In Your Controller

```php
use App\Models\Invoice;
use Nexus\Sequencing\Actions\GenerateSerialNumberAction;

class InvoiceController extends Controller
{
    public function store(Request $request)
    {
        $invoice = new Invoice($request->validated());
        
        // Generate unique invoice number
        $invoice->invoice_number = GenerateSerialNumberAction::run(
            tenantId: auth()->user()->tenant_id,
            sequenceName: 'invoices'
        );
        
        $invoice->save();
        
        return response()->json($invoice, 201);
    }
}
```

---

### Level 2: Advanced Patterns

**For:** Developers needing complex numbering schemes  
**Complexity:** Pattern configuration + context variables  
**Use When:** You need department codes, dates, or custom prefixes in numbers

#### Example Patterns

| Pattern | Context | Result | Use Case |
|---------|---------|--------|----------|
| `INV-{YEAR}-{COUNTER:5}` | - | `INV-2025-00001` | Simple invoice numbering |
| `PO-{YEAR:2}{MONTH}-{COUNTER:4}` | - | `PO-2511-0001` | Purchase orders with YY-MM |
| `{TENANT}-RCP-{YEAR}-{MONTH}-{DAY}-{COUNTER:3}` | `tenant_code: ACME` | `ACME-RCP-2025-11-14-001` | Receipts with full date |
| `{PREFIX}-{DEPARTMENT}-{COUNTER:6}` | `prefix: URGENT`<br>`department_code: IT` | `URGENT-IT-000001` | Department-scoped with priority |
| `DOC-{YEAR}/{COUNTER:4}` | - | `DOC-2025/0001` | Simple documents |

#### Using Complex Patterns

```php
// IT Purchase Order
$itPO = GenerateSerialNumberAction::run('acme-corp', 'it-purchase-orders');
// Result: IT-PO-2511-00001

// Finance Invoice with Department
$invoice = GenerateSerialNumberAction::run(
    tenantId: 'acme-corp',
    sequenceName: 'finance-invoices',
    context: ['department_code' => 'SALES']
);
// Result: SALES-INV-2025-000001
```

---

### Level 3: Custom Orchestration

**For:** Advanced users managing spawns, versions, and copies  
**Complexity:** Application-level orchestration  
**Use When:** You need sub-identifiers like `PO-224(a)` or `DOC-001/v2`

#### Understanding Responsibility Split

| Component | Provides | Example |
|-----------|----------|---------|
| **Nexus Sequencing** | Base unique number | `PO-224` |
| **Your Application** | Sub-identifiers & orchestration | `PO-224(a)`, `PO-224(b)`, `PO-224(c)` |

#### Example: Purchase Order Spawns

```php
use App\Models\PurchaseOrder;
use Nexus\Sequencing\Actions\GenerateSerialNumberAction;

class PurchaseOrderOrchestrator
{
    public function createWithSpawns(array $data, int $spawnCount): Collection
    {
        // Step 1: Get base number from sequencing (only once!)
        $baseNumber = GenerateSerialNumberAction::run(
            auth()->user()->tenant_id,
            'purchase-orders'
        );
        // Result: PO-224
        
        // Step 2: Create spawns with sub-identifiers
        $spawns = collect();
        $spawnLetters = range('a', chr(ord('a') + $spawnCount - 1));
        
        foreach ($spawnLetters as $letter) {
            $po = PurchaseOrder::create([
                'po_number' => $baseNumber . '(' . $letter . ')',
                'base_number' => $baseNumber,
                'spawn_identifier' => $letter,
                // ... other fields
            ]);
            
            $spawns->push($po);
        }
        
        return $spawns;
    }
}

// Usage
$orchestrator = new PurchaseOrderOrchestrator();
$pos = $orchestrator->createWithSpawns($request->validated(), 3);
// Creates: PO-224(a), PO-224(b), PO-224(c)
```

---

## Pattern Variables Reference

### Built-in Date Variables

| Variable | Description | Example | Format |
|----------|-------------|---------|--------|
| `{YEAR}` | 4-digit year | `2025` | `YYYY` |
| `{YEAR:2}` | 2-digit year | `25` | `YY` |
| `{MONTH}` | 2-digit month | `11` | `MM` |
| `{DAY}` | 2-digit day | `14` | `DD` |

### Counter Variable

| Variable | Description | Example | Notes |
|----------|-------------|---------|-------|
| `{COUNTER}` | Auto-increment with default padding | `00001` | Uses `padding` from sequence config |
| `{COUNTER:N}` | Counter with N-digit padding | `{COUNTER:8}` = `00000001` | Overrides sequence padding |

### Context Variables

| Variable | Description | Example | Context Key |
|----------|-------------|---------|-------------|
| `{PREFIX}` | Custom prefix | `URGENT` | `prefix` |
| `{TENANT}` | Tenant code | `ACME` | `tenant_code` |
| `{DEPARTMENT}` | Department code | `IT`, `SALES` | `department_code` |

---

## Reset Periods

Control when the counter resets to 1:

| Reset Period | Behavior | Use Case |
|--------------|----------|----------|
| **never** | Counter never resets | Lifetime sequential numbers |
| **daily** | Resets at midnight | Daily reports |
| **monthly** | Resets on 1st of month | Monthly invoices |
| **yearly** | Resets on Jan 1st | Annual contracts |

---

## Preview Mode

Preview the next number **without consuming** the counter:

```php
use Nexus\Sequencing\Actions\PreviewSerialNumberAction;

$preview = PreviewSerialNumberAction::run(
    tenantId: '123',
    sequenceName: 'invoices'
);
// Returns: INV-2025-00042
// Counter is NOT incremented
```

---

## Manual Override

Administrators can manually set the counter:

```php
use Nexus\Sequencing\Actions\OverrideSerialNumberAction;

OverrideSerialNumberAction::run(
    tenantId: '123',
    sequenceName: 'invoices',
    newValue: 5000,
    reason: 'Migration from legacy system',
    causer: auth()->user()
);
// Next generation will be 5001
```

---

## API Endpoints

All endpoints require `auth:sanctum` middleware.

```http
GET    /api/v1/sequences              - List all sequences
POST   /api/v1/sequences              - Create sequence
GET    /api/v1/sequences/{name}       - Get details
PATCH  /api/v1/sequences/{name}       - Update
DELETE /api/v1/sequences/{name}       - Delete
POST   /api/v1/sequences/{name}/generate  - Generate number
GET    /api/v1/sequences/{name}/preview   - Preview next
```

---

## Events

### SequenceGeneratedEvent

```php
use Nexus\Sequencing\Events\SequenceGeneratedEvent;

Event::listen(SequenceGeneratedEvent::class, function ($event) {
    Log::info("Generated: {$event->generatedNumber}");
});
```

### SequenceResetEvent

```php
use Nexus\Sequencing\Events\SequenceResetEvent;

Event::listen(SequenceResetEvent::class, function ($event) {
    Log::info("Reset: {$event->sequenceName}");
});
```

### SequenceOverriddenEvent

```php
use Nexus\Sequencing\Events\SequenceOverriddenEvent;

Event::listen(SequenceOverriddenEvent::class, function ($event) {
    Log::warning("Override by: {$event->causer->name}");
});
```

---

## Phase 2 Preview (Coming Soon)

### 🔄 Phase 1.5: Core Refactoring
- Pure PHP `Core/` separation
- Zero Laravel dependencies in core
- 95%+ test coverage

### ⏳ Phase 2: New Features
1. **HasSequence Trait** - Automatic generation on model creation
2. **Pattern Validation** - Validate existing numbers against patterns
3. **Step Size Support** - Increment by custom amounts
4. **Count-Based Reset** - Reset after N generations
5. **Preview with Remaining Count** - See counts until next reset

**See [REQUIREMENTS.md](REQUIREMENTS.md) for complete roadmap.**

---

## Architecture Notes

### Maximum Atomicity Principle

✅ **This Package:**
- Generates unique base identifiers
- Manages counter state
- Handles pattern formatting
- Provides audit trail

❌ **Not This Package:**
- Document lifecycle (use `nexus-workflow`)
- Business validation (your application)
- Sub-identifiers (your application)
- Multi-package orchestration (Nexus ERP Core)

### Transaction Safety

All operations wrapped in database transactions with atomic locking:

```sql
SELECT * FROM serial_number_sequences 
WHERE tenant_id = ? AND sequence_name = ?
FOR UPDATE;  -- Row-level lock
```

**Result:** 100 concurrent requests = 100 unique numbers, zero duplicates.

---

## Testing

```bash
cd packages/nexus-sequencing
vendor/bin/pest tests/Unit

# Integration tests in Edward CLI
cd apps/edward
php artisan test --filter=Sequencing
```

---

## Performance

| Operation | Target | Current | Status |
|-----------|--------|---------|--------|
| Generate (single) | < 50ms | ~30ms | ✅ |
| Generate (100 concurrent) | < 100ms p95 | ~80ms | ✅ |
| Preview | < 20ms | ~15ms | ✅ |

---

## License

MIT License - See [LICENSE.md](LICENSE.md)

---

## Support

- **Documentation:** [REQUIREMENTS.md](REQUIREMENTS.md)
- **Issues:** GitHub Issues
- **Discussions:** GitHub Discussions

---

**Package Status:**
- ✅ **Phase 1:** Implementation complete
- 🔄 **Phase 1.5:** Core refactoring planned
- ⏳ **Phase 2:** New features planned

*This package maintains **Maximum Atomicity** - it does one thing exceptionally well: generating unique, sequential base identifiers.*
