# NEXUS-WORKFLOW PACKAGE: COPILOT INSTRUCTIONS

## 🎯 Package Identity & Mission

**Package Name:** `nexus/workflow`  
**Namespace:** `Nexus\Workflow`  
**Purpose:** Progressive workflow and state machine engine for Laravel applications  
**Architecture:** Atomic package with zero dependencies on Nexus ERP Core

### Core Principles

1. **Maximum Atomicity**: This package MUST function standalone without any Nexus ERP Core dependencies
2. **Progressive Complexity**: Support both simple in-memory state machines (Phase 1) and complex database-driven workflows (Phase 2)
3. **Framework Agnostic Core**: Business logic MUST NOT depend on Laravel facades
4. **Contract-Driven Design**: All services MUST implement contracts for flexibility

---

## 📦 Package Structure

```
nexus-workflow/
├── src/
│   ├── Core/                          # Phase 1: In-memory state machine
│   │   ├── Contracts/                 # Interfaces for state machine
│   │   ├── DTOs/                      # Data transfer objects
│   │   ├── Services/                  # Core business logic
│   │   └── Enums/                     # State types, transition types
│   │
│   ├── Models/                        # Phase 2: Eloquent models
│   │   ├── WorkflowDefinition.php     # JSON workflow schemas
│   │   ├── WorkflowInstance.php       # Active workflow instances
│   │   ├── WorkflowTransition.php     # Transition history
│   │   ├── ApproverGroup.php          # Approval group definitions
│   │   ├── ApproverGroupMember.php    # Group membership
│   │   └── UserTask.php               # Task inbox
│   │
│   ├── Services/                      # Phase 2: High-level services
│   │   ├── WorkflowDefinitionService.php
│   │   ├── ApproverGroupService.php
│   │   └── UserTaskService.php
│   │
│   ├── Strategies/                    # Phase 2: Approval strategies
│   │   ├── SequentialApprovalStrategy.php
│   │   ├── ParallelApprovalStrategy.php
│   │   ├── QuorumApprovalStrategy.php
│   │   ├── AnyApprovalStrategy.php
│   │   └── WeightedApprovalStrategy.php
│   │
│   ├── Engines/                       # Phase 2: Workflow engines
│   │   └── DatabaseWorkflowEngine.php
│   │
│   ├── Traits/                        # Model integration
│   │   ├── HasWorkflow.php            # Phase 1 trait
│   │   └── HasDatabaseWorkflow.php    # Phase 2 trait
│   │
│   ├── Console/Commands/              # Artisan commands
│   │   ├── WorkflowListCommand.php
│   │   ├── WorkflowImportCommand.php
│   │   ├── WorkflowExportCommand.php
│   │   ├── WorkflowActivateCommand.php
│   │   ├── WorkflowDeactivateCommand.php
│   │   └── WorkflowShowCommand.php
│   │
│   └── WorkflowServiceProvider.php    # Laravel integration
│
├── database/migrations/               # Phase 2 database schema
├── config/workflow.php                # Package configuration
└── tests/                             # Comprehensive test suite
```

---

## 🔒 Architectural Boundaries

### ✅ ALLOWED Dependencies

- **Laravel Framework:** Eloquent, Service Container, Events, Cache
- **PHP Standard Library:** No restrictions
- **Composer Packages:** Only if absolutely necessary and documented

### ❌ FORBIDDEN Dependencies

- **Nexus ERP Core:** NEVER import from `Nexus\Erp` namespace
- **Other Nexus Packages:** No direct dependencies on `nexus-tenancy`, `nexus-accounting`, etc.
- **External Workflows:** No calls to other atomic packages

### 🔄 Communication Patterns

**If this package needs data from another package:**
1. Define a **Contract (Interface)** in this package
2. Let the **Nexus ERP Core** bind the implementation
3. Use dependency injection to receive the implementation

**Example:**
```php
namespace Nexus\Workflow\Contracts;

interface UserRepositoryContract
{
    public function find(string $userId): ?object;
}

// In service
class UserTaskService
{
    public function __construct(
        private UserRepositoryContract $userRepo // Injected by Core
    ) {}
}
```

---

## 🏗️ Development Guidelines

### Phase 1: In-Memory State Machine

**Location:** `src/Core/`  
**Responsibility:** Stateless state machine logic  
**No Database:** All state stored on Eloquent model column  
**Trait:** `HasWorkflow`

**When modifying Phase 1:**
- ✅ Pure functions, no side effects
- ✅ DTO-based communication
- ✅ Extensive validation
- ❌ No database queries
- ❌ No Laravel facades in services

### Phase 2: Database-Driven Workflows

**Location:** `src/Models/`, `src/Services/`, `src/Engines/`  
**Responsibility:** Persistent workflow management  
**Database:** PostgreSQL with JSONB for definitions  
**Trait:** `HasDatabaseWorkflow`

**When modifying Phase 2:**
- ✅ Use Eloquent models for data access
- ✅ Wrap operations in DB transactions
- ✅ Emit events for state changes
- ✅ Cache workflow definitions (1 hour TTL)
- ❌ Never call other package repositories directly

### Multi-Approver Engine

**Location:** `src/Strategies/`, `src/Services/ApproverGroupService.php`  
**Responsibility:** Evaluate approval completion

**5 Strategies:**
1. **Sequential:** Approvers in order (1→2→3)
2. **Parallel:** All must approve (unanimous)
3. **Quorum:** N of M approvals required
4. **Any:** First approval wins
5. **Weighted:** Sum of weights ≥ threshold

**When adding new strategies:**
1. Implement `ApprovalStrategyContract`
2. Add to `ApprovalStrategyFactory`
3. Register in `config/workflow.php`
4. Write comprehensive tests

### Task Management

**Location:** `src/Services/UserTaskService.php`, `src/Models/UserTask.php`  
**Responsibility:** User task inbox and lifecycle

**Task Lifecycle:**
```
pending → in_progress → completed
        ↓            ↓
      cancelled   failed
```

**Priority Levels:**
- 1-4: Low
- 5-9: Normal
- 10-19: High
- 20+: Urgent

---

## 🧪 Testing Requirements

### Unit Tests

**Required for:**
- All services (CRUD operations)
- All approval strategies (evaluation logic)
- DTO validation
- State machine transitions

**Location:** `tests/Unit/`

### Feature Tests

**Required for:**
- Eloquent model relationships
- Service integration
- Database transactions
- Cache behavior

**Location:** `tests/Feature/`

### Integration Tests

**Required for:**
- Complete workflow lifecycles
- Multi-approver scenarios
- Task management flows

**Location:** `tests/Integration/`

### Test Commands

```bash
# Run all tests
vendor/bin/pest

# Run specific suite
vendor/bin/pest tests/Feature/

# Run with coverage
vendor/bin/pest --coverage
```

---

## 📝 Code Standards

### Service Layer

```php
class WorkflowDefinitionService
{
    // ✅ Constructor injection
    public function __construct(
        private WorkflowDefinitionRepository $repository
    ) {}
    
    // ✅ Return type declarations
    public function create(array $data): WorkflowDefinition
    {
        // ✅ Validation first
        $this->validate($data);
        
        // ✅ DB transaction wrapper
        return DB::transaction(function () use ($data) {
            $workflow = $this->repository->create($data);
            
            // ✅ Event emission
            event(new WorkflowCreated($workflow));
            
            return $workflow;
        });
    }
    
    // ✅ Protected validation methods
    protected function validate(array $data): void
    {
        // Validation logic
    }
}
```

### Approval Strategies

```php
class SequentialApprovalStrategy implements ApprovalStrategyContract
{
    // ✅ Required methods
    public function evaluate(
        Collection $members,
        Collection $completedTasks,
        array $config
    ): bool {
        // Approval logic
    }
    
    public function getProgress(
        Collection $members,
        Collection $completedTasks,
        array $config
    ): array {
        return [
            'completed' => $completed,
            'pending' => $pending,
            'next_approver' => $next,
        ];
    }
    
    public function getName(): string
    {
        return 'sequential';
    }
}
```

### Models

```php
class WorkflowDefinition extends Model
{
    // ✅ UUID primary keys
    use HasUuids;
    
    // ✅ Mass assignment protection
    protected $fillable = ['code', 'name', 'version', 'definition', 'is_active'];
    
    // ✅ JSON casting
    protected $casts = [
        'definition' => 'json',
        'is_active' => 'boolean',
    ];
    
    // ✅ Relationships
    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }
    
    // ✅ Scopes for common queries
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

---

## 🚀 Adding New Features

### Checklist for New Features

1. **Define Contracts** - Create interfaces in `Contracts/`
2. **Implement Core Logic** - Business logic in `Services/` or `Core/Services/`
3. **Create Models** - Eloquent models in `Models/` (if database-backed)
4. **Add Migrations** - Database schema in `database/migrations/`
5. **Write Tests** - Comprehensive test coverage
6. **Update Documentation** - README.md and inline PHPDoc
7. **Add Configuration** - Options in `config/workflow.php`
8. **Register Services** - Bind in `WorkflowServiceProvider`

### Example: Adding a New Approval Strategy

1. Create strategy class implementing `ApprovalStrategyContract`
2. Add to `ApprovalStrategyFactory`
3. Register in `config/workflow.php` under `approval_strategies`
4. Write unit tests for evaluation logic
5. Write feature tests for integration
6. Document usage in README.md

---

## 🔧 Configuration

### Key Configuration Options

```php
// config/workflow.php

return [
    // Engine selection
    'engine' => env('WORKFLOW_ENGINE', 'database'),
    
    // Cache TTL for definitions
    'cache_ttl' => env('WORKFLOW_CACHE_TTL', 3600),
    
    // Database table names
    'tables' => [
        'workflow_definitions' => 'workflow_definitions',
        'workflow_instances' => 'workflow_instances',
        // ...
    ],
    
    // User model for task assignments
    'user_model' => env('WORKFLOW_USER_MODEL', 'App\\Models\\User'),
    
    // Approval strategies
    'approval_strategies' => [
        'sequential' => SequentialApprovalStrategy::class,
        'parallel' => ParallelApprovalStrategy::class,
        // ...
    ],
    
    // Task configuration
    'task_priorities' => [
        'low' => 1,
        'normal' => 5,
        'high' => 10,
        'urgent' => 20,
    ],
    
    // Behavior
    'event_logging' => env('WORKFLOW_EVENT_LOGGING', true),
    'auto_assign_tasks' => env('WORKFLOW_AUTO_ASSIGN_TASKS', true),
];
```

---

## 🎯 Usage Context: Inside vs Outside Package

### Inside Package (Development)

**You are working ON the nexus-workflow package:**

- ✅ Modify core services and strategies
- ✅ Add new approval strategies
- ✅ Extend database schema
- ✅ Add new Artisan commands
- ✅ Write comprehensive tests
- ❌ Import from Nexus ERP Core
- ❌ Reference other Nexus packages

### Outside Package (Integration)

**You are using nexus-workflow IN an application:**

- ✅ Add trait to Eloquent models
- ✅ Create workflow definitions via service
- ✅ Use Artisan commands for management
- ✅ Configure via `config/workflow.php`
- ✅ Bind custom implementations
- ❌ Modify package source code
- ❌ Access internal classes directly

---

## 📚 Key Contracts for Extension

### WorkflowEngineContract

Implement custom workflow engines:

```php
interface WorkflowEngineContract
{
    public function canTransition(WorkflowInstanceDTO $instance, string $transitionName, array $context = []): bool;
    public function applyTransition(WorkflowInstanceDTO $instance, string $transitionName, array $context = []): WorkflowTransitionDTO;
    public function getAvailableTransitions(WorkflowInstanceDTO $instance, array $context = []): array;
}
```

### ApprovalStrategyContract

Implement custom approval strategies:

```php
interface ApprovalStrategyContract
{
    public function evaluate(Collection $members, Collection $completedTasks, array $config): bool;
    public function getProgress(Collection $members, Collection $completedTasks, array $config): array;
    public function getName(): string;
}
```

---

## 🐛 Common Issues & Solutions

### Issue: "Workflow definition not found"

**Solution:** Ensure workflow is active and code matches:
```php
WorkflowDefinition::where('code', 'your-code')
    ->where('is_active', true)
    ->exists();
```

### Issue: "Cache not clearing"

**Solution:** Manually clear workflow cache:
```php
$engine = app(DatabaseWorkflowEngine::class);
$engine->clearCache($workflowId);
```

### Issue: "Approval not evaluating correctly"

**Solution:** Check strategy configuration:
```php
$group = ApproverGroup::find($groupId);
dd($group->strategy, $group->config);
```

### Issue: "Task not appearing in inbox"

**Solution:** Verify task assignment:
```php
UserTask::where('assigned_to', $userId)
    ->where('status', 'pending')
    ->get();
```

---

## 🎓 Learning Resources

### For Phase 1 (State Machine)

- Read: `src/Core/README.md`
- Study: `tests/Unit/StateTransitionServiceTest.php`
- Example: Simple blog post workflow (draft → published)

### For Phase 2 (Database Workflows)

- Read: Main `README.md` (this file)
- Study: `tests/Feature/Phase2IntegrationTest.php`
- Example: Purchase order approval workflow

### For Multi-Approver

- Read: `src/Strategies/` implementations
- Study: `tests/Feature/ApproverGroupServiceTest.php`
- Example: Finance approval chain (analyst → manager → director)

---

## ⚡ Performance Considerations

### Caching Strategy

- **Workflow Definitions:** Cached for 1 hour (configurable)
- **Cache Key Format:** `workflow:definition:{uuid}`
- **Cache Driver:** Uses Laravel's configured cache driver
- **Invalidation:** Automatic on activate/deactivate

### Database Optimization

- **Indexes:** All foreign keys indexed
- **UUIDs:** Used for all primary keys (security & distribution)
- **JSONB:** PostgreSQL JSONB for flexible schemas
- **Eager Loading:** Always eager load relationships in services

### Query Optimization

```php
// ✅ Good: Eager load relationships
$instances = WorkflowInstance::with(['definition', 'transitions'])->get();

// ❌ Bad: N+1 queries
$instances = WorkflowInstance::all();
foreach ($instances as $instance) {
    $instance->definition; // N+1 query
}
```

---

## 🚨 Critical Rules for Future Agents

### MUST DO

1. ✅ Maintain atomicity - package works standalone
2. ✅ Write tests for all new features
3. ✅ Use contracts for inter-package communication
4. ✅ Validate all inputs before processing
5. ✅ Wrap database operations in transactions
6. ✅ Emit events for state changes
7. ✅ Update README.md with new features
8. ✅ Follow PSR-12 coding standards

### MUST NOT DO

1. ❌ Import from Nexus ERP Core namespace
2. ❌ Call other atomic package services directly
3. ❌ Use Laravel facades in core business logic
4. ❌ Skip validation on user inputs
5. ❌ Modify database without migrations
6. ❌ Break backward compatibility
7. ❌ Add features without tests
8. ❌ Hardcode configuration values

---

## 📞 Package Maintainer Notes

**Current Status:** ✅ Phase 1 Complete | ✅ Phase 2 Complete  
**Next Phase:** Phase 3 (SLA tracking, escalation, delegation)  
**Active PR:** #148 (Phase 1 & 2)  
**Branch:** `developing-workflow`

**Key Contacts:**
- Architecture Review Board (ARB)
- Nexus ERP Core Team
- Package Developers Community

---

**Remember:** This package is the foundation of Nexus ERP's workflow capabilities. Every change must maintain the balance between simplicity (Phase 1) and power (Phase 2) while preserving maximum atomicity.
