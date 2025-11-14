# nexus-workflow Package Requirements

**Package Name:** `azaharizaman/nexus-workflow`  
**Namespace:** `Nexus\Workflow`  
**Version:** 2.0.0  
**Status:** Draft  
**Created:** November 14, 2025  
**Updated:** November 14, 2025

---

## Executive Summary

Best-in-class **JSON-based workflow automation engine** for PHP, designed for **headless backend systems**. This package excels at approval workflows, document routing, and business process automation with unique features like **escalation management**, **delegation chains**, and **SLA tracking** that go beyond traditional workflow engines.

The package is **standalone and extensible**, allowing users to define custom triggers, activities, timers, events, and execution strategies without dependency on other Nexus packages. No visual components—pure API and contract-based implementation.

### Core Philosophy

1. **Headless First**: Pure backend workflow engine with RESTful API, no UI components
2. **Standalone Capability**: Fully functional without other Nexus packages (nexus-tenancy, nexus-audit-log are optional)
3. **Extensibility First**: Plugin architecture for custom activities, triggers, events, and conditions
4. **Superior Features**: Escalation, delegation, SLA management—features not found in most workflow engines
5. **Framework Agnostic Core**: Core engine works outside Laravel (Laravel integration via adapter)
6. **Best Practices from BPMN**: Adopts proven patterns from BPMN 2.0 without the complexity

### Architectural Rationale

**Consolidated From:** PRD01-SUB21-WORKFLOW-ENGINE.md (originally split into Engine + Management)

**Why Consolidated:**
Workflow engine and state management are consolidated because they:
1. **Tightly coupled** - Engine requires state, state requires engine
2. **Always deployed together** - Cannot use engine without persistence
3. **Single deployment unit** - No practical benefit to separate packages
4. **Shared domain context** - Both deal with workflow execution lifecycle

**Internal Modularity:**
Maintained through:
- Clear separation: Core (framework-agnostic) vs Adapters (Laravel, Symfony, etc.)
- Contract-driven: All interactions through interfaces (StorageContract, NotificationContract, etc.)
- Plugin system for extensibility (`Nexus\Workflow\Contracts`)
- Event-driven architecture for loose coupling
- Zero Eloquent dependencies in Core (Eloquent only in Laravel adapter)

### Why JSON, Not BPMN 2.0?

**Decision:** Focus on JSON engine exclusively for v2.0.0

**Rationale:**
1. **Complexity**: BPMN 2.0 specification is massive (538 pages) with significant implementation complexity
2. **Market Need**: Most PHP applications need approval workflows, not full BPM suites
3. **Unique Value**: Our escalation, delegation, and SLA features are superior to BPMN spec
4. **Time-to-Market**: Single engine allows faster development and better quality
5. **Maintainability**: One excellent engine beats two mediocre ones
6. **Best Practices**: We adopt BPMN's proven patterns (gateways, parallel flows, events) in simpler JSON format

**What We Adopt from BPMN:**
- Gateway pattern (exclusive, parallel, inclusive routing)
- Event-driven architecture (start, intermediate, end events)
- Parallel execution with token synchronization
- Conditional routing with clear expressions
- Compensation and error handling patterns

**What We Improve:**
- ✅ Built-in escalation with configurable levels (not in BPMN)
- ✅ Delegation chains for out-of-office scenarios (not in BPMN)
- ✅ SLA tracking with breach notifications (not in BPMN)
- ✅ Human-readable JSON format (vs complex XML)
- ✅ Simpler learning curve for developers

---

## Functional Requirements

**Source:** PRD01-SUB21-WORKFLOW-ENGINE.md

### Core Workflow Requirements

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-WF-001** | Provide **API endpoints** for creating and managing workflow definitions | High |
| **FR-WF-002** | Support **multi-level routing** with parallel and sequential flows | High |
| **FR-WF-003** | Support **conditional routing** based on rules, data, or custom expressions | High |
| **FR-WF-004** | Implement **escalation rules** for overdue tasks with deadline enforcement and auto-escalation | High |
| **FR-WF-005** | Support **delegation of authority** with time-bound assignments and proxy approvals | Medium |
| **FR-WF-006** | Provide **workflow status tracking** with state query APIs | High |
| **FR-WF-007** | Support **workflow templates** for common patterns (approval, review, escalation) | Medium |
| **FR-WF-008** | Provide **task inbox API** for pending tasks with filtering and sorting | High |
| **FR-WF-009** | **Persist workflow instance state** tracking current step and full history | High |
| **FR-WF-010** | Prevent **duplicate workflow instances** for the same entity | High |
| **FR-WF-011** | Support **SLA (Service Level Agreement)** tracking with breach notifications | High |
| **FR-WF-012** | Support **compensation logic** for failed workflows with rollback strategies | Medium |

### JSON Engine Requirements

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-JSON-001** | Support **simple JSON schema** for workflow definitions with human-readable format | High |
| **FR-JSON-002** | Implement **approval chain patterns** optimized for business document routing | High |
| **FR-JSON-003** | Support **escalation matrices** with configurable levels (manager → director → VP) | High |
| **FR-JSON-004** | Implement **out-of-office delegation** with automatic routing to delegates | Medium |
| **FR-JSON-005** | Support **conditional approvers** based on amount thresholds or document attributes | High |
| **FR-JSON-006** | Provide **approval reminders** with configurable frequency (daily, every 2 days) | Medium |
| **FR-JSON-007** | Support **bulk approval** for multiple pending items | Low |
| **FR-JSON-008** | Implement **gateway patterns** (exclusive, parallel, inclusive) using simple JSON syntax | High |
| **FR-JSON-009** | Support **timer events** with ISO 8601 duration and cron expressions | High |
| **FR-JSON-010** | Implement **parallel execution** with token synchronization | Medium |

### Extensibility Requirements (Plugin System)

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-EXT-001** | Provide **plugin interface** for custom activity types with registration system | High |
| **FR-EXT-002** | Support **custom trigger definitions** (webhook, schedule, event, manual) | High |
| **FR-EXT-003** | Allow **custom condition evaluators** for routing decisions | High |
| **FR-EXT-004** | Support **custom event listeners** for workflow lifecycle hooks | Medium |
| **FR-EXT-005** | Provide **custom timer implementations** (cron, relative, absolute, calendar-based) | Medium |
| **FR-EXT-006** | Allow **custom data transformers** for input/output mapping | Medium |
| **FR-EXT-007** | Support **custom notification channels** beyond email/SMS (Slack, Teams, etc.) | Low |
| **FR-EXT-008** | Provide **plugin lifecycle management** (register, enable, disable, unregister) with configuration-based discovery | Medium |
| **FR-EXT-009** | Support **custom execution strategies** (async, sync, queued, scheduled) | Medium |

### Standalone Requirements (No Nexus Dependencies)

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-STD-001** | Function **without nexus-tenancy** - use optional tenant isolation when available | High |
| **FR-STD-002** | Function **without nexus-audit-log** - use internal history tracking, integrate when available | High |
| **FR-STD-003** | Function **without nexus-notification** - provide notification contracts, integrate when available | High |
| **FR-STD-004** | Provide **database agnostic** storage layer (MySQL, PostgreSQL, SQLite, SQL Server) | High |
| **FR-STD-005** | Support **framework-agnostic core** with Laravel adapter | Medium |
| **FR-STD-006** | Provide **REST API** for non-Laravel integrations | Medium |
| **FR-STD-007** | Support **multiple storage backends** (database, Redis, file system) via contract abstraction | Low |

---

## Business Rules

| Rule ID | Description |
|---------|-------------|
| **BR-WF-001** | Tasks must be executed in **sequential order** unless parallel routing is enabled |
| **BR-WF-002** | Users cannot approve their **own submissions** (configurable) |
| **BR-WF-003** | Escalations occur **automatically** when task deadlines are exceeded |
| **BR-WF-004** | Workflow state changes MUST be **ACID-compliant** transactions |
| **BR-WF-009** | Timer events MUST support **ISO 8601 duration** and cron expressions |
| **BR-WF-010** | Compensation MUST execute in **reverse order** of completed activities |
| **BR-WF-011** | Plugin activities MUST implement the **ActivityContract** interface |
| **BR-WF-012** | Custom triggers MUST be registered before workflow instantiation |
| **BR-WF-013** | SLA breaches MUST trigger **immediate notifications** to stakeholders |
| **BR-WF-014** | Delegation chains MUST not exceed **3 levels** to prevent infinite delegation |
| **BR-WF-015** | Gateway routing decisions must evaluate to exactly one path for exclusive gateways |
| **BR-WF-016** | Parallel gateways must wait for all incoming tokens before proceeding |
| **BR-WF-017** | Compensation activities are only triggered during error handling or explicit rollback |

---

## Data Requirements

### Core Data Requirements

| Requirement ID | Description |
|----------------|-------------|
| **DR-WF-001** | Store **workflow definitions** in JSON format with routing rules and conditions |
| **DR-WF-002** | Maintain **workflow instance state** tracking current step and history |
| **DR-WF-003** | Track **task actions** with timestamps, comments, and attachments |
| **DR-WF-004** | Store **delegation records** with start/end dates and delegator/delegatee |
| **DR-WF-005** | Track **escalation history** with each escalation level and timestamp |
| **DR-WF-006** | Store **SLA definitions** with target times and breach thresholds |
| **DR-WF-007** | Maintain **plugin registry** with installed activities, triggers, and conditions |
| **DR-WF-008** | Store **approval matrices** with amount thresholds and role mappings |
| **DR-WF-009** | Track **out-of-office schedules** for automatic delegation routing |
| **DR-WF-010** | Store **reminder schedules** for pending tasks |
| **DR-WF-011** | Track **BPMN-style token states** for concurrent execution paths in parallel gateways |
| **DR-WF-012** | Store **workflow execution context** including variables, input/output data |
| **DR-WF-013** | Persist **compensation logs** for rollback and error recovery |

---

## Integration Requirements

| Requirement ID | Description | Priority | Dependency Type |
|----------------|-------------|----------|-----------------|
| **IR-WF-001** | Integrate with **transactional modules** for approval workflows | High | Optional (via contracts) |
| **IR-WF-002** | Integrate with **notification service** for task notifications | High | Optional (via NotificationContract) |
| **IR-WF-003** | Support **external workflow triggers** via REST API | Medium | Standalone |
| **IR-WF-004** | Integrate with **nexus-tenancy** for tenant isolation when available | Medium | Optional |
| **IR-WF-005** | Integrate with **nexus-audit-log** for change tracking when available | Medium | Optional |
| **IR-WF-010** | Support **webhook triggers** for external system events | Medium | Standalone |
| **IR-WF-011** | Provide **GraphQL API** for workflow queries and mutations | Low | Optional |

---

## Performance Requirements

| Requirement ID | Description | Target |
|----------------|-------------|--------|
| **PR-WF-001** | Workflow state transition | < 100ms |
| **PR-WF-002** | Task inbox query (1000 pending) | < 500ms |
| **PR-WF-003** | Workflow instance creation | < 200ms |
| **PR-WF-008** | JSON workflow evaluation | < 50ms |
| **PR-WF-009** | Escalation check (1000 active workflows) | < 2s |
| **PR-WF-010** | Plugin activity execution | < 500ms |
| **PR-WF-011** | Bulk workflow instance creation (100 instances) | < 5s |
| **PR-WF-012** | Parallel gateway synchronization (10 concurrent paths) | < 100ms |
| **PR-WF-013** | Compensation rollback execution | < 500ms |

---

## Security Requirements

| Requirement ID | Description | Scope |
|----------------|-------------|-------|
| **SR-WF-001** | Enforce **role-based task assignment** based on user roles and permissions | Core |
| **SR-WF-002** | Implement **audit logging** for all task actions | Core |
| **SR-WF-003** | Prevent **task execution by unauthorized users** at code level | Core |
| **SR-WF-004** | Enforce **tenant isolation** for all workflow data (when tenancy enabled) | Optional |
| **SR-WF-006** | Sanitize **expression inputs** (JSON conditions) to prevent code injection | Core |
| **SR-WF-007** | Restrict **external integrations** to whitelisted URLs/services | Core |
| **SR-WF-009** | Implement **plugin sandboxing** to prevent malicious plugin code execution | Core |
| **SR-WF-010** | Support **workflow definition encryption** for sensitive process logic | Core |
| **SR-WF-011** | Validate **webhook signatures** for external triggers | Core |

---

## Dependencies

### Core Dependencies (Required)

**Framework Dependencies:**
- PHP ≥ 8.2 (strict typing, enums, readonly properties)
- Database: MySQL 8.0+, PostgreSQL 12+, SQLite 3.35+, or SQL Server 2019+
- Cache Driver: Redis, Memcached, or File (for distributed locking)

**PHP Libraries:**
- PSR-7 HTTP Message Interface (for REST API)
- PSR-14 Event Dispatcher (for event system)
- PSR-16 Simple Cache (for caching)
- PSR-17 HTTP Factories (for HTTP requests)

### Optional Laravel Integration

**Framework Dependencies:**
- Laravel Framework ≥ 12.x (when using Laravel adapter)
- Laravel Eloquent ORM (when using database storage)
- Laravel Queue (when using async execution)

### Optional Nexus Package Integration

**Nexus Package Dependencies (All Optional):**
- `azaharizaman/nexus-tenancy` - Multi-tenancy isolation (auto-detected)
- `azaharizaman/nexus-audit-log` - Enhanced change tracking (auto-detected)
- `azaharizaman/nexus-notification` - Rich notifications (auto-detected)

**Behavior when Nexus packages are NOT installed:**
- Works as standalone workflow engine
- Uses internal history tracking instead of nexus-audit-log
- Uses notification contracts that can be implemented by any notification system
- No tenant isolation (single-tenant mode)

### Plugin System Dependencies

**Plugin Development:**
- No required dependencies - pure PHP interfaces
- Optional: Laravel facades (when using Laravel adapter)
- Optional: Nexus package contracts (when integrating with other packages)

### Development Dependencies

**Testing:**
- PHPUnit ≥ 10.0 or Pest ≥ 2.0
- Mockery for mocking

**Code Quality:**
- PHP_CodeSniffer (PSR-12 compliance)
- PHPStan (static analysis)
- Laravel Pint (code formatting)

---

## Implementation Notes

### Package Structure (JSON-Only Architecture)

```
packages/nexus-workflow/
├── src/
│   ├── Core/                          # Framework-agnostic core (NO Eloquent)
│   │   ├── Contracts/                 # All interfaces (PSR-compliant)
│   │   │   ├── WorkflowEngineContract.php
│   │   │   ├── ActivityContract.php          # ADD: compensate() method
│   │   │   ├── TriggerContract.php
│   │   │   ├── ConditionContract.php
│   │   │   ├── TimerContract.php
│   │   │   ├── EventContract.php
│   │   │   ├── NotificationContract.php
│   │   │   ├── StorageContract.php           # Storage abstraction
│   │   │   └── AuditContract.php
│   │   │
│   │   ├── Engine/                    # Workflow engine implementation
│   │   │   ├── WorkflowEngine.php
│   │   │   ├── StateManager.php
│   │   │   ├── TokenEngine.php               # BPMN-style token concept
│   │   │   └── ExecutionContext.php
│   │   │
│   │   ├── Services/                  # Core business logic
│   │   │   ├── StateTransitionService.php
│   │   │   ├── RuleEvaluationService.php
│   │   │   ├── ApprovalLogicService.php
│   │   │   ├── EscalationService.php         # Superior feature
│   │   │   ├── DelegationService.php         # Superior feature
│   │   │   ├── SlaManagementService.php      # Superior feature
│   │   │   └── CompensationService.php       # Rollback logic
│   │   │
│   │   ├── DTOs/                      # Data Transfer Objects
│   │   │   ├── WorkflowDefinition.php
│   │   │   ├── WorkflowInstance.php
│   │   │   ├── TaskInstance.php
│   │   │   └── ExecutionResult.php
│   │   │
│   │   └── Exceptions/                # Core exceptions
│   │       ├── WorkflowException.php
│   │       ├── ValidationException.php
│   │       └── ExecutionException.php
│   │
│   ├── Gateways/                      # BPMN-inspired gateway patterns
│   │   ├── ExclusiveGateway.php       # One path only
│   │   ├── ParallelGateway.php        # All paths
│   │   ├── InclusiveGateway.php       # Multiple paths based on conditions
│   │   └── EventBasedGateway.php      # Wait for specific events
│   │
│   ├── Plugins/                       # Plugin system
│   │   ├── PluginManager.php          # ADD: lifecycle methods
│   │   ├── PluginRegistry.php
│   │   ├── Activities/                # Built-in activities
│   │   │   ├── ApprovalActivity.php
│   │   │   ├── NotificationActivity.php
│   │   │   ├── EmailActivity.php
│   │   │   └── WebhookActivity.php
│   │   ├── Triggers/                  # Built-in triggers
│   │   │   ├── ManualTrigger.php
│   │   │   ├── ScheduleTrigger.php
│   │   │   ├── WebhookTrigger.php
│   │   │   └── EventTrigger.php
│   │   └── Conditions/                # Built-in conditions
│   │       ├── AmountThresholdCondition.php
│   │       ├── RoleCondition.php
│   │       └── AttributeCondition.php
│   │
│   ├── Timers/                        # Event-driven timer system
│   │   ├── TimerQueue.php             # Persistent timer queue
│   │   ├── TimerWorker.php            # Process due timers
│   │   ├── TimerScheduler.php
│   │   └── Adapters/
│   │       ├── RedisTimerAdapter.php  # Redis Sorted Sets
│   │       └── DatabaseTimerAdapter.php
│   │
│   ├── Http/                          # REST API (framework-agnostic)
│   │   ├── Controllers/
│   │   │   ├── WorkflowController.php
│   │   │   ├── TaskController.php
│   │   │   └── PluginController.php
│   │   └── Middleware/
│   │       ├── ValidateWorkflowDefinition.php
│   │       └── CheckTaskAuthorization.php
│   │
│   ├── Events/                        # Domain events
│   │   ├── WorkflowInstanceCreated.php
│   │   ├── TaskAssigned.php
│   │   ├── TaskCompleted.php
│   │   ├── TaskRejected.php
│   │   ├── WorkflowEscalated.php
│   │   ├── WorkflowCompleted.php
│   │   ├── WorkflowCancelled.php
│   │   ├── DelegationCreated.php
│   │   └── SlaBreached.php
│   │
│   ├── Adapters/                      # Framework adapters
│   │   └── Laravel/
│   │       ├── Models/                # Eloquent HERE (not in Core)
│   │       │   ├── WorkflowDefinition.php
│   │       │   ├── WorkflowInstance.php
│   │       │   ├── TaskInstance.php
│   │       │   ├── TaskAction.php
│   │       │   ├── DelegationRecord.php
│   │       │   └── EscalationHistory.php
│   │       ├── Repositories/          # Eloquent repositories HERE
│   │       │   ├── EloquentWorkflowDefinitionRepository.php
│   │       │   ├── EloquentWorkflowInstanceRepository.php
│   │       │   └── EloquentTaskInstanceRepository.php
│   │       ├── LaravelWorkflowAdapter.php
│   │       ├── Facades/
│   │       │   └── Workflow.php
│   │       ├── Commands/
│   │       │   ├── ProcessTimersCommand.php       # Event-driven timer worker
│   │       │   ├── ProcessEscalationsCommand.php
│   │       │   ├── CleanupExpiredDelegationsCommand.php
│   │       │   └── MonitorSlaCommand.php
│   │       └── WorkflowServiceProvider.php
│   │
│   └── Support/                       # Utilities
│       ├── Helpers.php
│       ├── ValidationRules.php
│       └── ExpressionParser.php       # Simple expression language (no FEEL)
│
├── database/
│   ├── migrations/
│   │   ├── 2025_11_01_000001_create_workflow_definitions_table.php
│   │   ├── 2025_11_01_000002_create_workflow_instances_table.php
│   │   ├── 2025_11_01_000003_create_task_instances_table.php
│   │   ├── 2025_11_01_000004_create_task_actions_table.php
│   │   ├── 2025_11_01_000005_create_delegation_records_table.php
│   │   ├── 2025_11_01_000006_create_escalation_history_table.php
│   │   ├── 2025_11_01_000007_create_plugin_registry_table.php
│   │   └── 2025_11_01_000008_create_timer_queue_table.php
│   └── seeders/
│       ├── WorkflowTemplateSeeder.php
│       └── PluginSeeder.php
│
├── resources/
│   ├── schemas/
│   │   └── json-workflow-schema.json # JSON workflow schema
│   └── templates/                     # Workflow templates (JSON only)
│       ├── purchase-order-approval.json
│       ├── expense-approval.json
│       └── invoice-approval.json
│
├── tests/
│   ├── Unit/
│   │   ├── Core/
│   │   ├── Engine/
│   │   ├── Gateways/
│   │   ├── Plugins/
│   │   ├── Timers/
│   │   └── Storage/
│   ├── Feature/
│   │   ├── JsonWorkflowExecutionTest.php
│   │   ├── EscalationTest.php
│   │   ├── DelegationTest.php
│   │   ├── SlaManagementTest.php
│   │   ├── CompensationTest.php
│   │   ├── PluginSystemTest.php
│   │   ├── TimerSystemTest.php
│   │   └── StandaloneUsageTest.php       # Test without Nexus packages
│   └── Integration/
│       ├── LaravelIntegrationTest.php
│       ├── NexusTenancyIntegrationTest.php
│       └── NexusAuditLogIntegrationTest.php
│
├── docs/
│   ├── REQUIREMENTS.md (this file)
│   ├── JSON_ENGINE.md                 # JSON workflow documentation
│   ├── BPMN_ENGINE.md                 # BPMN 2.0 documentation
│   ├── PLUGIN_DEVELOPMENT.md          # Plugin development guide
│   ├── STANDALONE_USAGE.md            # Using without Nexus packages
│   ├── API_REFERENCE.md               # REST API documentation
│   └── EXAMPLES.md                    # Usage examples
│
├── examples/                          # Example implementations
│   ├── standalone/                    # Standalone usage
│   │   ├── simple-approval.php
│   │   └── custom-plugin.php
│   ├── laravel/                       # Laravel integration
│   │   ├── approval-workflow.php
│   │   └── bpmn-workflow.php
│   └── plugins/                       # Example plugins
│       ├── SlackNotificationPlugin/
│       └── SapIntegrationPlugin/
│
├── composer.json
├── phpunit.xml
└── README.md
```

### Development Phases

**Phase 1: Core Foundation (Week 1-2)**
- Framework-agnostic core contracts (ActivityContract, TriggerContract, etc.)
- Abstract workflow engine implementation
- State manager and execution context
- In-memory storage adapter for testing
- Core exception hierarchy

**Phase 2: JSON Engine (Week 3-4)**
- JSON workflow engine implementation
- Approval logic and routing
- **Escalation service** (superior to BPMN)
- **Delegation service** (beyond BPMN spec)
- **SLA management** (superior to BPMN)
- Condition evaluator and expression parser
- JSON schema validation

**Phase 3: Storage Layer (Week 5)**
- Database models (Eloquent)
- Repository implementations
- Migration files
- Transaction management
- Query optimization

**Phase 4: Plugin System (Week 6)**
- Plugin manager and registry
- Built-in activities (approval, notification, email, webhook)
- Built-in triggers (manual, schedule, webhook, event)
- Built-in conditions and timers
- Plugin lifecycle management

**Phase 5: BPMN Engine Foundation (Week 7-8)**
- BPMN XML parser and validator
- BPMN element models (Gateway, Task, Event, SequenceFlow)
- Token-based execution engine
- BPMN-specific services (import, export, rendering)

**Phase 6: BPMN Advanced Features (Week 9-10)**
- Gateway implementations (Exclusive, Parallel, Inclusive, Event-based)
- Event handlers (Timer, Message, Signal, Error)
- Sub-processes and call activities
- Multi-instance activities
- Compensation and transactions

**Phase 7: Laravel Integration (Week 11)**
- Laravel adapter and service provider
- Artisan commands (escalation processing, cleanup)
- Facades for easy access
- Queue integration for async execution
- Optional Nexus package integration (auto-detection)

**Phase 8: REST API (Week 12)**
- RESTful endpoints for workflow management
- Task management API
- BPMN import/export endpoints
- Plugin management API
- Webhook endpoints for external triggers

**Phase 9: Visual Designers (Week 13-14)**
- JSON workflow designer (simple drag-drop)
- BPMN 2.0 modeler integration (bpmn-io/bpmn-js)
- Template library
- Workflow inbox UI

**Phase 10: Testing & Documentation (Week 15-16)**
- Comprehensive unit tests
- Feature tests for both engines
- Integration tests (Laravel, Nexus packages)
- Standalone usage tests
- API documentation
- Plugin development guide
- Usage examples

---

## Extensibility & Plugin System

### Plugin Types

**1. Custom Activities**
```php
// User implements ActivityContract
class SendSlackNotificationActivity implements ActivityContract
{
    public function execute(ExecutionContext $context): ExecutionResult
    {
        // Custom logic to send Slack notification
        $slackClient = new SlackClient($context->getConfig('webhook_url'));
        $slackClient->send($context->getData('message'));
        
        return ExecutionResult::success();
    }
    
    public function getName(): string { return 'slack_notification'; }
    public function getSchema(): array { return ['webhook_url' => 'string', 'message' => 'string']; }
}

// Register plugin
$pluginManager->registerActivity(new SendSlackNotificationActivity());
```

**2. Custom Triggers**
```php
// User implements TriggerContract
class SapDocumentTrigger implements TriggerContract
{
    public function shouldTrigger(array $data): bool
    {
        // Custom logic to check SAP document status
        return $data['sap_status'] === 'PENDING_APPROVAL';
    }
    
    public function getTriggerData(array $data): array
    {
        return [
            'document_id' => $data['sap_document_id'],
            'amount' => $data['total_amount'],
            'requester' => $data['created_by'],
        ];
    }
}
```

**3. Custom Conditions**
```php
// User implements ConditionContract
class BusinessDayCondition implements ConditionContract
{
    public function evaluate(ExecutionContext $context): bool
    {
        $date = $context->getData('date');
        return $this->isBusinessDay($date) && !$this->isHoliday($date);
    }
    
    private function isBusinessDay(\DateTime $date): bool
    {
        return $date->format('N') < 6; // Monday-Friday
    }
    
    private function isHoliday(\DateTime $date): bool
    {
        // Check against holiday calendar
        return in_array($date->format('Y-m-d'), $this->holidays);
    }
}
```

**4. Custom Timers**
```php
// User implements TimerContract
class FiscalYearEndTimer implements TimerContract
{
    public function getNextExecution(\DateTime $from): \DateTime
    {
        // Calculate next fiscal year end
        $fiscalYearEnd = new \DateTime($from->format('Y') . '-03-31');
        if ($from > $fiscalYearEnd) {
            $fiscalYearEnd->modify('+1 year');
        }
        return $fiscalYearEnd;
    }
}
```

**5. Custom Event Listeners**
```php
// User implements EventContract
class AuditLogListener implements EventContract
{
    public function handle(WorkflowEvent $event): void
    {
        // Custom audit logging
        $this->auditLogger->log([
            'event' => $event->getName(),
            'workflow_id' => $event->getWorkflowId(),
            'user_id' => $event->getUserId(),
            'timestamp' => now(),
            'data' => $event->getData(),
        ]);
    }
}
```

### Plugin Registration

```php
// config/workflow.php
return [
    'plugins' => [
        'activities' => [
            \App\Workflows\Plugins\SendSlackNotificationActivity::class,
            \App\Workflows\Plugins\SapIntegrationActivity::class,
        ],
        'triggers' => [
            \App\Workflows\Plugins\SapDocumentTrigger::class,
        ],
        'conditions' => [
            \App\Workflows\Plugins\BusinessDayCondition::class,
        ],
        'timers' => [
            \App\Workflows\Plugins\FiscalYearEndTimer::class,
        ],
        'listeners' => [
            \App\Workflows\Plugins\AuditLogListener::class,
        ],
    ],
];
```

---

## Standalone Usage (Without Nexus Packages)

### Scenario 1: Simple Approval Workflow (No Laravel)

```php
<?php
use Nexus\Workflow\Core\Engine\JsonWorkflowEngine;
use Nexus\Workflow\Storage\InMemory\InMemoryStorageAdapter;

// Create engine with in-memory storage
$storage = new InMemoryStorageAdapter();
$engine = new JsonWorkflowEngine($storage);

// Define workflow
$definition = [
    'name' => 'Purchase Order Approval',
    'steps' => [
        ['name' => 'Manager Approval', 'approver_role' => 'manager'],
        ['name' => 'Director Approval', 'approver_role' => 'director'],
    ],
    'escalation' => [
        'timeout' => 48, // hours
        'action' => 'escalate_to_next_level',
    ],
];

// Create workflow instance
$instance = $engine->createWorkflow('purchase-order', $definition, [
    'po_number' => 'PO-2025-001',
    'amount' => 5000,
    'requester' => 'john.doe',
]);

// Approve step
$engine->approveTask($instance->getCurrentTask(), 'jane.manager', 'Approved for budget');

// Check status
echo $instance->getStatus(); // 'in_progress'
```

### Scenario 2: Laravel Integration (Without Nexus Packages)

```php
<?php
use Nexus\Workflow\Facades\Workflow;

// Create workflow using Laravel facade
$workflow = Workflow::create('expense-approval', [
    'steps' => [
        ['name' => 'Supervisor Approval', 'approver_role' => 'supervisor'],
        ['name' => 'Finance Approval', 'approver_role' => 'finance'],
    ],
], [
    'expense_id' => 123,
    'amount' => 1500,
    'submitter' => auth()->id(),
]);

// Approve in controller
public function approve(Request $request, $taskId)
{
    $task = Workflow::getTask($taskId);
    
    Workflow::approveTask($task, auth()->user(), $request->input('comments'));
    
    return response()->json(['status' => 'approved']);
}
```

### Scenario 3: Custom Notification (No nexus-notification)

```php
<?php
// Implement NotificationContract
class EmailNotificationAdapter implements NotificationContract
{
    public function send(string $recipient, string $subject, string $message): void
    {
        // Use any email library
        mail($recipient, $subject, $message);
    }
}

// Register with workflow engine
$engine->setNotificationAdapter(new EmailNotificationAdapter());
```

### Scenario 4: Custom Audit (No nexus-audit-log)

```php
<?php
// Implement AuditContract
class FileAuditAdapter implements AuditContract
{
    public function log(string $event, array $data): void
    {
        $log = date('Y-m-d H:i:s') . " - $event - " . json_encode($data) . PHP_EOL;
        file_put_contents('/var/log/workflow.log', $log, FILE_APPEND);
    }
}

// Register with workflow engine
$engine->setAuditAdapter(new FileAuditAdapter());
```

### Scenario 5: Multi-Tenant Without nexus-tenancy

```php
<?php
// Manually scope workflows to tenant
$tenantId = $request->header('X-Tenant-ID');

$workflow = Workflow::create('approval', $definition, [
    'tenant_id' => $tenantId, // Store in workflow data
    'entity_id' => $entityId,
]);

// Query workflows for tenant
$workflows = Workflow::query()
    ->where('data->tenant_id', $tenantId)
    ->get();
```

---

## Workflow Lifecycle

### JSON Engine Lifecycle

```
1. Definition Phase
   └── Create workflow definition (steps, rules, escalation, delegation, SLA)
   └── Validate JSON schema
   └── Store definition

2. Instantiation Phase
   └── Create workflow instance for entity
   └── Validate no duplicate instance exists
   └── Trigger 'WorkflowInstanceCreated' event
   └── Assign first task to appropriate user/role

3. Execution Phase
   └── Wait for task action (approve/reject/delegate)
   └── Evaluate escalation deadlines (if timeout exceeded → escalate)
   └── Check SLA thresholds (if breached → notify stakeholders)
   └── Process delegation if user is out-of-office

4. Transition Phase
   └── Process task action (approve/reject)
   └── Evaluate routing conditions for next step
   └── Create next task or complete workflow
   └── Dispatch events (TaskCompleted, WorkflowCompleted, etc.)
   └── Send notifications

5. Completion Phase
   └── Mark workflow as completed/rejected
   └── Calculate final metrics (duration, SLA compliance)
   └── Archive workflow instance
   └── Update entity status
```

### BPMN Engine Lifecycle

```
1. Definition Phase
   └── Import BPMN 2.0 XML or create via modeler
   └── Validate against BPMN 2.0 XSD schema
   └── Parse elements (tasks, gateways, events, flows)
   └── Store definition with metadata

2. Instantiation Phase
   └── Create workflow instance with initial token at start event
   └── Initialize process variables and data objects
   └── Dispatch 'WorkflowInstanceCreated' event
   └── Begin token execution

3. Token Execution Phase
   └── Move token along sequence flows
   └── Execute tasks when token arrives
   └── Evaluate gateway conditions
   └── Handle events (timer, message, signal)
   └── Create/destroy tokens at parallel gateways
   └── Wait for token synchronization

4. Event Handling Phase
   └── Listen for external events (messages, signals)
   └── Trigger timer events at scheduled times
   └── Correlate messages using correlation keys
   └── Handle error and escalation events

5. Completion Phase
   └── Token reaches end event
   └── All tokens consumed (for parallel paths)
   └── Execute compensation if needed
   └── Mark workflow as completed
   └── Store final state and variables
```

---

## Event-Driven Architecture

### Events Emitted (Both Engines)

**Core Workflow Events:**
- `WorkflowInstanceCreatedEvent` - New workflow instance started
- `WorkflowCompletedEvent` - Workflow completed successfully
- `WorkflowRejectedEvent` - Workflow rejected at any step
- `WorkflowCancelledEvent` - Workflow manually cancelled
- `WorkflowSuspendedEvent` - Workflow temporarily suspended
- `WorkflowResumedEvent` - Workflow resumed after suspension

**Task Events:**
- `TaskAssignedEvent` - Task assigned to user or role
- `TaskReassignedEvent` - Task reassigned to different user
- `TaskCompletedEvent` - Task completed (approved/rejected)
- `TaskDelegatedEvent` - Task delegated to another user
- `TaskClaimedEvent` - Task claimed by user from pool

**JSON Engine Specific Events (Superior Features):**
- `TaskEscalatedEvent` - Task escalated due to timeout
- `SlaBreachedEvent` - SLA threshold breached
- `SlaWarningEvent` - SLA approaching breach (80% threshold)
- `DelegationCreatedEvent` - Out-of-office delegation created
- `DelegationExpiredEvent` - Delegation period ended
- `ReminderSentEvent` - Reminder sent to task assignee

**BPMN Engine Specific Events:**
- `TokenCreatedEvent` - New token created (parallel gateway)
- `TokenConsumedEvent` - Token consumed at gateway
- `GatewayEvaluatedEvent` - Gateway condition evaluated
- `MessageReceivedEvent` - External message received
- `SignalBroadcastedEvent` - Signal broadcasted to workflows
- `TimerTriggeredEvent` - Timer event triggered
- `ErrorThrownEvent` - Error event thrown
- `CompensationTriggeredEvent` - Compensation triggered

**Plugin Events:**
- `PluginRegisteredEvent` - Plugin registered in system
- `PluginExecutedEvent` - Plugin activity executed
- `CustomEventOccurredEvent` - User-defined custom event

### Events Consumed

**From External Systems:**
- Transaction submission events (from ERP modules)
- User availability events (out-of-office, vacation)
- System events (maintenance, downtime)
- External webhook events (third-party systems)

**BPMN Message Events:**
- Message events from message queues (RabbitMQ, Kafka, Redis)
- Signal events for broadcast communication
- Error events for exception handling

**Plugin Events:**
- Custom events defined by user plugins
- Integration events from external systems

### Event Handlers

Users can register custom event handlers:

```php
// Register event listener
Workflow::onEvent('WorkflowCompleted', function($event) {
    // Send completion notification
    Notification::send($event->getInitiator(), new WorkflowCompletedNotification($event));
    
    // Update external system
    ExternalSystem::updateStatus($event->getEntityId(), 'approved');
});

// Register multiple events
Workflow::onEvents(['TaskEscalated', 'SlaBreached'], function($event) {
    // Alert management
    Alert::send('management@company.com', $event);
});
```

---

## Comparison: JSON Engine vs BPMN Engine

| Feature | JSON Engine | BPMN Engine | Winner |
|---------|------------|-------------|--------|
| **Learning Curve** | Easy (JSON format) | Moderate (BPMN spec knowledge) | JSON |
| **Escalation** | ✅ Built-in with configurable levels | ❌ Not in BPMN spec, must implement | **JSON** |
| **Delegation** | ✅ Out-of-office, time-bound delegation | ❌ Not in BPMN spec | **JSON** |
| **SLA Management** | ✅ Built-in tracking and breach alerts | ❌ Must implement separately | **JSON** |
| **Approval Reminders** | ✅ Configurable reminder schedule | ❌ Must use timer events | **JSON** |
| **Complex Gateways** | ❌ Limited to simple conditions | ✅ Full BPMN gateway support | **BPMN** |
| **Sub-Processes** | ❌ Limited support | ✅ Embedded, call activities, event | **BPMN** |
| **Event Handling** | ❌ Basic events only | ✅ Full BPMN event catalog | **BPMN** |
| **Interoperability** | ❌ Proprietary JSON format | ✅ OMG standard, tool compatible | **BPMN** |
| **Message Flows** | ❌ Not supported | ✅ Cross-process communication | **BPMN** |
| **Visual Modeling** | Simple drag-drop | Industry-standard BPMN modeler | **BPMN** |
| **Process Mining** | ❌ Not supported | ✅ Compatible with mining tools | **BPMN** |
| **Human Tasks** | ✅ Optimized for approvals | ✅ User tasks supported | **Tie** |
| **Performance** | Fast (simple evaluation) | Moderate (token engine overhead) | **JSON** |
| **Best For** | Approval workflows, document routing | Complex processes, orchestration | - |

**Recommendation:**
- **Use JSON Engine** for: Approval workflows, expense routing, simple sequential processes
- **Use BPMN Engine** for: Complex orchestration, multi-party processes, integration with BPMN tools
- **Use Both** when: Different workflows have different complexity levels

---

## Configuration Example

```php
// config/workflow.php
return [
    // Default engine: 'json' or 'bpmn'
    'default_engine' => env('WORKFLOW_DEFAULT_ENGINE', 'json'),
    
    // Enable BPMN engine (requires BPMN dependencies)
    'enable_bpmn' => env('WORKFLOW_ENABLE_BPMN', false),
    
    // Storage driver: 'database', 'redis', 'memory'
    'storage' => env('WORKFLOW_STORAGE', 'database'),
    
    // Database connection for workflow storage
    'database_connection' => env('WORKFLOW_DB_CONNECTION', 'mysql'),
    
    // Optional Nexus package integration (auto-detected)
    'integrations' => [
        'tenancy' => env('WORKFLOW_ENABLE_TENANCY', true), // Use nexus-tenancy if installed
        'audit_log' => env('WORKFLOW_ENABLE_AUDIT_LOG', true), // Use nexus-audit-log if installed
        'notification' => env('WORKFLOW_ENABLE_NOTIFICATION', true), // Use nexus-notification if installed
    ],
    
    // JSON Engine Configuration
    'json_engine' => [
        'escalation' => [
            'enabled' => true,
            'default_timeout' => 48, // hours
            'check_interval' => 15, // minutes
        ],
        'delegation' => [
            'enabled' => true,
            'max_chain_length' => 3,
        ],
        'sla' => [
            'enabled' => true,
            'warning_threshold' => 80, // percent
        ],
        'reminders' => [
            'enabled' => true,
            'frequency' => 24, // hours
        ],
    ],
    
    // BPMN Engine Configuration
    'bpmn_engine' => [
        'validation' => [
            'strict' => true, // Strict XSD validation
            'allow_custom_elements' => false,
        ],
        'token_engine' => [
            'max_parallel_tokens' => 100,
            'token_timeout' => 3600, // seconds
        ],
        'message_correlation' => [
            'driver' => 'redis', // redis, database
            'ttl' => 86400, // seconds
        ],
    ],
    
    // Plugin System
    'plugins' => [
        'enabled' => true,
        'auto_discover' => true, // Auto-discover plugins in app/Workflows/Plugins
        'activities' => [],
        'triggers' => [],
        'conditions' => [],
        'timers' => [],
        'listeners' => [],
    ],
    
    // API Configuration
    'api' => [
        'enabled' => true,
        'prefix' => 'api/workflows',
        'middleware' => ['api', 'auth:sanctum'],
        'rate_limit' => 60, // requests per minute
    ],
    
    // Performance
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // seconds
        'driver' => env('CACHE_DRIVER', 'redis'),
    ],
    
    // Queue for async execution
    'queue' => [
        'enabled' => true,
        'connection' => env('QUEUE_CONNECTION', 'redis'),
        'queue' => 'workflows',
    ],
];
```

---

## Testing Requirements

### Test Categories

**1. Unit Tests (Core Logic)**
- Abstract engine logic
- Condition evaluators
- Expression parsers
- Plugin system
- State management

**2. Feature Tests (Engine-Specific)**
- JSON workflow execution (approval, escalation, delegation, SLA)
- BPMN workflow execution (gateways, events, sub-processes)
- Workflow lifecycle (create, execute, complete, cancel)
- Plugin execution and lifecycle

**3. Integration Tests**
- Laravel framework integration
- Nexus package integration (when installed)
- Database storage and transactions
- Queue and async execution
- REST API endpoints

**4. Standalone Tests**
- Usage without Laravel
- Usage without Nexus packages
- Custom notification adapters
- Custom audit adapters
- Plugin development workflow

**5. Performance Tests**
- State transition speed
- Large workflow (100+ steps)
- Parallel execution (100+ concurrent workflows)
- Escalation checking (1000+ active workflows)
- BPMN token engine performance

**6. Conformance Tests**
- BPMN 2.0 specification compliance
- OMG test cases for BPMN gateways
- JSON schema validation

### Test Coverage Target

- **Minimum:** 80% code coverage
- **Target:** 90% code coverage
- **Critical paths:** 100% coverage (engine execution, state transitions)

---

## Migration Path

### From Custom JSON to BPMN

```php
use Nexus\Workflow\Services\MigrationService;

// Migrate existing JSON workflow to BPMN
$migrationService = new MigrationService();

$jsonDefinition = WorkflowDefinition::find($id);
$bpmnDefinition = $migrationService->jsonToBpmn($jsonDefinition);

// Export as BPMN XML
$xml = $bpmnDefinition->toXml();
file_put_contents('workflow.bpmn', $xml);
```

### From Other Workflow Systems

The package should provide importers for:
- Laravel Workflow packages
- Camunda BPMN files
- Generic JSON formats

---

## Success Criteria

**Package is considered successful when:**

1. **Standalone Capability:**
   - ✅ Can be installed and used without any Nexus packages
   - ✅ Works without Laravel (framework-agnostic core)
   - ✅ Provides all contracts for custom implementations

2. **Dual-Engine Support:**
   - ✅ JSON engine fully functional with superior features
   - ✅ BPMN 2.0 engine OMG specification compliant
   - ✅ Both engines can coexist in same application

3. **Extensibility:**
   - ✅ Users can define custom activities, triggers, conditions, timers
   - ✅ Plugin system is well-documented with examples
   - ✅ No modification of package code required for extensions

4. **Performance:**
   - ✅ All performance targets met (see PR-WF-* requirements)
   - ✅ Handles 1000+ concurrent workflows efficiently
   - ✅ Escalation checks complete within time budget

5. **Integration:**
   - ✅ Seamless Laravel integration (optional)
   - ✅ Auto-detects and integrates with Nexus packages when available
   - ✅ REST API functional for non-Laravel usage

6. **Testing:**
   - ✅ 90%+ code coverage achieved
   - ✅ All engines tested independently
   - ✅ Integration tests with/without dependencies pass

7. **Documentation:**
   - ✅ Complete API reference
   - ✅ Plugin development guide
   - ✅ Standalone usage examples
   - ✅ Both engines documented with examples

---

**Document Maintenance:**
- Update after each sprint or major feature completion
- Review during architectural changes
- Sync with implementation progress
- Add new requirements as needed (mark as FUTURE)

**Related Documents:**
- [JSON_ENGINE.md](./JSON_ENGINE.md) - JSON workflow documentation (to be created)
- [BPMN_ENGINE.md](./BPMN_ENGINE.md) - BPMN 2.0 documentation (to be created)
- [PLUGIN_DEVELOPMENT.md](./PLUGIN_DEVELOPMENT.md) - Plugin guide (to be created)
- [STANDALONE_USAGE.md](./STANDALONE_USAGE.md) - Standalone usage (to be created)
- [SYSTEM ARCHITECTURAL DOCUMENT](../../../docs/SYSTEM%20ARCHITECHTURAL%20DOCUMENT.md)
- [Master PRD](../../../docs/prd/PRD01-MVP.md)
- [PRD01-SUB21-WORKFLOW-ENGINE.md](../../../docs/prd/prd-01/PRD01-SUB21-WORKFLOW-ENGINE.md)
