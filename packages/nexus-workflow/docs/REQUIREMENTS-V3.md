# nexus-workflow Package Requirements

**Version:** 3.0.0  
**Last Updated:** November 14, 2025  
**Status:** Architecture Rewrite - Progressive Disclosure Model

---

## Executive Summary

**nexus-workflow** is a **progressive workflow & state machine engine** for PHP/Laravel that grows with your application—from a 5-minute blog post state machine to a full-scale ERP approval automation system.

### The Problem We Solve

Most workflow packages force you to choose:
- **Simple packages** (like `spatie/laravel-model-states`) handle basic state machines but can't scale to complex approvals
- **Complex BPM engines** (Camunda, Activiti) are powerful but have steep learning curves and require dedicated infrastructure

**We solve both** with a single, atomic package that uses **progressive disclosure**:

1. **Level 1: State Machine** (5 minutes) - Add a `HasWorkflow` trait to your Eloquent model. Define states in your model file. Zero database tables required.
2. **Level 2: Approval Workflows** (1 hour) - Promote your workflow to database-driven JSON. Add User Tasks, conditional routing, task inbox.
3. **Level 3: ERP Automation** (Production-ready) - Layer on SLA tracking, escalation rules, delegation, multi-approver strategies, and audit compliance.

### Core Philosophy

1. **Progressive Disclosure** - Developers only learn what they need, when they need it
2. **Backwards Compatible** - Level 1 code works identically after promoting to Level 2/3
3. **Headless Backend** - Pure API-driven, no UI components
4. **Framework Agnostic Core** - Zero Laravel dependencies in core engine
5. **Extensible Everything** - Plugin system for activities, conditions, approval strategies, timers

### Why This Approach Wins

**For Mass Market (80%):**
- Fastest "hello world" in the ecosystem (5 minutes)
- No database tables for simple cases
- Gentle learning curve
- Works with existing models without refactoring

**For ERP/Enterprise (20%):**
- Battle-tested approval patterns (escalation, delegation, SLA)
- Multi-approver strategies (unison, majority, quorum, weighted)
- Extensible condition system
- ACID-compliant state transitions
- Full audit trail

---

## Personas & User Stories

### Personas

| ID | Persona | Role | Primary Goal |
|-----|---------|------|--------------|
| **P1** | Mass Market Developer | Full-stack dev at small agency | "Add `draft` → `published` state to my `Post` model in 5 minutes without reading docs" |
| **P2** | In-House ERP Developer | Backend dev at manufacturing company | "Build reliable purchase order approval workflow integrated with existing models and roles" |
| **P3** | End-User (Manager/Employee) | Business user | "See all pending tasks in one inbox, approve/reject, delegate when on vacation" |
| **P4** | System Administrator | IT/DevOps | "Configure approval matrices, SLA policies, escalation rules without touching code" |

### User Stories

#### Level 1: State Machine (Mass Appeal)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-001** | P1 | As a developer, I want to add a `HasWorkflow` trait to my Eloquent model to manage its `status` column | **High** |
| **US-002** | P1 | As a developer, I want to define a state machine (states and transitions) as an array inside my model, requiring zero database tables | **High** |
| **US-003** | P1 | As a developer, I want to call `$model->workflow()->apply('transition')` to execute a state change | **High** |
| **US-004** | P1 | As a developer, I want to call `$model->workflow()->can('transition')` to check if a transition is allowed for UI logic | **High** |
| **US-005** | P1 | As a developer, I want to call `$model->workflow()->history()` to see all state transitions | Medium |

#### Level 2: Approval Workflows

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-010** | P2 | As a developer, I want to promote my in-model workflow to a database-driven JSON definition without refactoring code | **High** |
| **US-011** | P2 | As a developer, I want to define "User Task" steps that halt the workflow and assign tasks to specific users or roles | **High** |
| **US-012** | P2 | As a developer, I want to use conditional routing (e.g., "if amount > 10,000, add Director approval") | **High** |
| **US-013** | P2 | As a developer, I want parallel approval flows (e.g., "Finance AND HR must both approve") | **High** |
| **US-014** | P2 | As a developer, I want to configure multi-approver strategies (unison vote, majority vote, quorum) for a single step | **High** |
| **US-015** | P3 | As an end-user, I want one inbox showing all my pending tasks across all workflows | **High** |
| **US-016** | P3 | As an end-user, I want to approve/reject tasks with comments and attachments | **High** |

#### Level 3: ERP Automation

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-020** | P2 | As a developer, I want to automatically escalate overdue tasks to a manager after 48 hours | **High** |
| **US-021** | P2 | As a developer, I want to define SLA policies for entire workflows with breach notifications | **High** |
| **US-022** | P3 | As a manager, I want to delegate my task inbox to an assistant for specific date ranges | **High** |
| **US-023** | P2 | As a developer, I want to define compensation/rollback logic for failed workflows | Medium |
| **US-024** | P4 | As an admin, I want to configure approval matrices based on amount thresholds without code changes | Medium |
| **US-025** | P2 | As a developer, I want to track and report on SLA compliance rates per workflow type | Medium |

---

## Functional Requirements

### FR-L1: Level 1 - State Machine (Mass Appeal)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L1-001** | Provide `HasWorkflow` trait for Eloquent models | **High** | • Add `use HasWorkflow` to model<br>• Define `workflow()` method returning array<br>• No `php artisan migrate` required<br>• Works immediately |
| **FR-L1-002** | Support in-model workflow definitions with zero database dependencies | **High** | • Define states as simple array<br>• Define transitions as simple array<br>• Store current state in model's `status` column (configurable)<br>• No external files or tables required |
| **FR-L1-003** | Provide `workflow()->apply($transition)` method | **High** | • Execute state transition<br>• Fire Laravel events (`TransitionStarted`, `TransitionCompleted`)<br>• Validate transition is allowed<br>• Wrap in database transaction |
| **FR-L1-004** | Provide `workflow()->can($transition)` method | **High** | • Return boolean for UI logic<br>• Check current state allows transition<br>• Check custom guard conditions<br>• No side effects |
| **FR-L1-005** | Provide `workflow()->history()` method | Medium | • Return collection of state changes<br>• Include timestamps, actors, comments<br>• Store in `workflow_history` table (auto-migrated on first use) |
| **FR-L1-006** | Support guard conditions on transitions | Medium | • Define `guard` callable in transition array<br>• Return `false` to block transition<br>• Example: `'guard' => fn($model) => $model->amount < 1000` |
| **FR-L1-007** | Support transition hooks (`before`, `after`) | Medium | • Execute callbacks before/after transition<br>• Example: `'after' => fn($model) => $model->notify(...)` |

### FR-L2: Level 2 - Approval Workflows

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L2-001** | Support database-driven workflow definitions (JSON format) | **High** | • Store definitions in `workflow_definitions` table<br>• Same API as Level 1 (`apply()`, `can()`)<br>• Override in-model definitions when DB definition exists<br>• Hot-reload on definition changes |
| **FR-L2-002** | Support User Task states that create inbox tasks | **High** | • Define `type: "task"` in state definition<br>• Automatically create record in `workflow_tasks` table<br>• Support assignment to `user_id` or `role` string<br>• Pause workflow until task is completed |
| **FR-L2-003** | Support conditional routing based on workflow data | **High** | • Evaluate `condition` expression on transitions<br>• Access workflow data via `data.field_name`<br>• Support operators: `==`, `!=`, `>`, `<`, `>=`, `<=`, `AND`, `OR`, `NOT`, `IN`<br>• Automatically select valid transition |
| **FR-L2-004** | Support parallel approval flows (AND gateways) | **High** | • Define multiple tasks in `parallel` array<br>• Create all tasks simultaneously<br>• Wait for ALL tasks to complete before proceeding<br>• Track completion status per task |
| **FR-L2-005** | Support inclusive gateways (OR routing) | Medium | • Evaluate multiple condition expressions<br>• Activate ALL paths where condition is true<br>• Synchronize at join point |
| **FR-L2-006** | Support multi-approver strategies on single task | **High** | • `strategy: "unison"` - ALL assignees must approve<br>• `strategy: "majority"` - >50% must approve<br>• `strategy: "quorum"` - Configurable threshold (e.g., 3 of 5)<br>• `strategy: "weighted"` - Votes have different weights<br>• `strategy: "first"` - First approval wins<br>• Extensible via `ApprovalStrategyContract` |
| **FR-L2-007** | Provide task inbox API/service | **High** | • Query: `WorkflowInbox::forUser($userId)->pending()`<br>• Support filtering by workflow type, priority, due date<br>• Support sorting<br>• Auto-check delegation rules |
| **FR-L2-008** | Support task actions (approve, reject, request changes) | **High** | • Validate user has permission to act<br>• Store action in `workflow_history`<br>• Support comments and attachments<br>• Trigger next workflow transition |
| **FR-L2-009** | Support workflow data schema validation | Medium | • Define `dataSchema` in JSON definition<br>• Validate on workflow instantiation<br>• Validate on data updates<br>• Type support: string, number, boolean, date, array, object |
| **FR-L2-010** | Support plugin activities via `onEntry`/`onExit` hooks | **High** | • Fire-and-forget execution (async via queue)<br>• Access workflow data as inputs<br>• Built-in plugins: email, Slack, webhook, database update<br>• Extensible via `ActivityContract` |

### FR-L3: Level 3 - ERP Automation

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L3-001** | Support task escalation rules | **High** | • Define `automation.escalation` array in state<br>• `after: "48 hours"` - Time threshold<br>• `action: "notify"` - Send reminder<br>• `action: "reassign"` - Change task assignee<br>• Store escalation history<br>• Process via scheduled command |
| **FR-L3-002** | Support SLA tracking per workflow instance | **High** | • Define `automation.sla` in state or workflow root<br>• `duration: "3 days"` - Total allowed time<br>• `onBreach` - Actions to fire (notifications, etc.)<br>• Track SLA status: `on_track`, `at_risk`, `breached`<br>• Calculate based on business hours (configurable) |
| **FR-L3-003** | Support user delegation with date ranges | **High** | • Store delegation in `workflow_delegations` table<br>• Fields: `delegator_id`, `delegatee_id`, `starts_at`, `ends_at`<br>• Automatically route new tasks to delegatee<br>• Log delegation in task history<br>• Max delegation chain depth: 3 levels |
| **FR-L3-004** | Support compensation/rollback logic | Medium | • Define `compensation` array on activities<br>• Execute in reverse order on failure<br>• Example: Delete created records, send cancellation emails<br>• Implement `ActivityContract::compensate()` method |
| **FR-L3-005** | Support approval matrix configuration | Medium | • Store threshold-based routing rules in database<br>• Example: Amount $0-$5K → Manager, $5K-$50K → Director, $50K+ → VP<br>• Apply automatically during workflow instantiation<br>• Admin UI for configuration (optional) |
| **FR-L3-006** | Support event-driven timer system | **High** | • Store timers in `workflow_timers` table<br>• Index on `trigger_at` timestamp<br>• Scheduled worker processes due timers<br>• Support: SLA checks, escalations, reminders, scheduled tasks<br>• NOT cron-based (event-driven for scalability) |

### FR-EXT: Extensibility

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-EXT-001** | Provide plugin interface for custom activities | **High** | • Implement `ActivityContract` interface<br>• Methods: `execute()`, `compensate()`, `getName()`, `getSchema()`<br>• Auto-discover in configured directories<br>• Register via service provider or config |
| **FR-EXT-002** | Provide plugin interface for custom conditions | **High** | • Implement `ConditionEvaluatorContract`<br>• Method: `evaluate($context, $expression): bool`<br>• Built-in: amount, role, attribute, date, custom<br>• Register custom evaluators via config |
| **FR-EXT-003** | Provide plugin interface for approval strategies | **High** | • Implement `ApprovalStrategyContract`<br>• Methods: `canProceed($task, $approvals): bool`<br>• Built-in: unison, majority, quorum, weighted, first<br>• Register custom strategies via config |
| **FR-EXT-004** | Provide plugin interface for custom triggers | Medium | • Implement `TriggerContract`<br>• Types: webhook, schedule, event, manual<br>• Auto-start workflows based on trigger rules |
| **FR-EXT-005** | Support custom storage backends | Low | • Implement `StorageContract` for different databases<br>• Built-in: Eloquent (MySQL, PostgreSQL, SQLite, SQL Server)<br>• Optional: Redis, MongoDB adapters |

---

## Non-Functional Requirements

### Performance Requirements

| ID | Requirement | Target | Notes |
|----|-------------|--------|-------|
| **PR-001** | State transition execution time | < 100ms | Excluding async plugin activities |
| **PR-002** | Task inbox query (1,000 pending tasks) | < 500ms | With proper database indexes |
| **PR-003** | Escalation/SLA check job (10,000 active workflows) | < 2 seconds | Indexed `workflow_timers` table |
| **PR-004** | Workflow instantiation | < 200ms | Including validation |
| **PR-005** | Parallel gateway synchronization (10 tasks) | < 100ms | Token-based coordination |

### Security Requirements

| ID | Requirement | Scope |
|----|-------------|-------|
| **SR-001** | Prevent unauthorized task actions | Validate at engine level, not just API |
| **SR-002** | Sanitize condition expressions | Prevent code injection in conditions |
| **SR-003** | Tenant isolation | Auto-scope queries when `nexus-tenancy` detected |
| **SR-004** | Plugin sandboxing | Prevent malicious plugin code execution |
| **SR-005** | Audit all state changes | Immutable history log |
| **SR-006** | Role-based access control | Integration with permission systems |

### Reliability Requirements

| ID | Requirement | Notes |
|----|-------------|-------|
| **REL-001** | All state changes MUST be ACID-compliant | Wrapped in database transactions |
| **REL-002** | Failed plugin activities must not block transitions | Fire-and-forget or queue-based execution |
| **REL-003** | Concurrency control for task actions | Prevent duplicate approvals via database locking |
| **REL-004** | State corruption protection | Validate state machine integrity before transitions |
| **REL-005** | Automatic retry for transient failures | Configurable retry policy for queued activities |

### Scalability Requirements

| ID | Requirement | Notes |
|----|-------------|-------|
| **SCL-001** | Support async/queued execution of activities | Laravel Queue integration |
| **SCL-002** | Horizontal scaling of timer workers | Multiple workers can process timers concurrently |
| **SCL-003** | Efficient database queries | Proper indexes on state, assignee, trigger_at columns |
| **SCL-004** | Support for 100,000+ concurrent workflow instances | Optimized for large-scale ERP deployments |

### Maintainability Requirements

| ID | Requirement | Notes |
|----|-------------|-------|
| **MAINT-001** | Framework-agnostic core | Zero Laravel dependencies in `src/Core/` |
| **MAINT-002** | Laravel adapter pattern | All framework-specific code in `src/Adapters/Laravel/` |
| **MAINT-003** | Comprehensive test coverage | > 80% code coverage, > 90% for core engine |
| **MAINT-004** | Clear separation of concerns | State management, task management, timer management are independent |

---

## Business Rules

| ID | Rule | Level |
|----|------|-------|
| **BR-001** | A user cannot approve their own submission | Configurable per workflow (Level 2) |
| **BR-002** | All state changes MUST be ACID-compliant | All levels |
| **BR-003** | Escalations occur automatically when deadlines exceeded | Level 3 |
| **BR-004** | Compensation executes in reverse order of activities | Level 3 |
| **BR-005** | Delegation chains cannot exceed 3 levels | Level 3 |
| **BR-006** | Level 1 definitions MUST be 100% compatible with Level 2/3 | All levels |
| **BR-007** | Workflow instance tied to single model instance | All levels |
| **BR-008** | Parallel tasks must ALL complete before proceeding | Level 2 |
| **BR-009** | Task assignment checked against delegation rules | Level 3 |
| **BR-010** | Multi-approver tasks follow configured strategy | Level 2 |

---

## Data Requirements

### Core Workflow Tables

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| `workflow_definitions` | Store Level 2/3 JSON schemas | `id`, `name`, `schema` (JSON), `is_active`, `version` |
| `workflow_instances` | Track state of running workflows | `id`, `subject_type`, `subject_id`, `definition_id`, `current_state`, `context_data` (JSON), `started_at`, `ended_at`, `sla_status` |
| `workflow_history` | Immutable audit log | `id`, `instance_id`, `event_type`, `state_before`, `state_after`, `transition_name`, `actor_id`, `payload` (JSON), `created_at` |

### Task & Assignment Tables

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| `workflow_tasks` | Actionable items in user inbox | `id`, `instance_id`, `assigned_to_id`, `assigned_to_role`, `name`, `status`, `due_at`, `approval_strategy`, `required_approvals`, `created_at` |
| `workflow_task_approvals` | Track individual approvals for multi-approver tasks | `id`, `task_id`, `user_id`, `action` (approved/rejected), `weight`, `comment`, `created_at` |
| `workflow_delegations` | User delegation rules | `id`, `delegator_id`, `delegatee_id`, `starts_at`, `ends_at`, `is_active` |

### Automation Tables

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| `workflow_timers` | Time-based events (SLA, escalation, reminders) | `id`, `instance_id`, `task_id`, `type`, `trigger_at`, `rule_payload` (JSON), `status` |
| `workflow_sla_tracking` | SLA metrics per workflow instance | `id`, `instance_id`, `target_duration`, `started_at`, `warning_threshold_at`, `breach_at`, `status` |
| `workflow_escalations` | Escalation history | `id`, `task_id`, `level`, `from_user_id`, `to_user_id`, `reason`, `created_at` |

---

## JSON Schema Specification

### Level 1: In-Model State Machine

```php
// In your Eloquent model
use Nexus\Workflow\Traits\HasWorkflow;

class Post extends Model
{
    use HasWorkflow;
    
    public function workflow(): array
    {
        return [
            'initialState' => 'draft',
            
            'states' => [
                'draft' => ['label' => 'Draft'],
                'in_review' => ['label' => 'In Review'],
                'published' => ['label' => 'Published'],
            ],
            
            'transitions' => [
                'submit' => [
                    'label' => 'Submit for Review',
                    'from' => ['draft'],
                    'to' => 'in_review',
                ],
                'approve' => [
                    'label' => 'Approve & Publish',
                    'from' => ['in_review'],
                    'to' => 'published',
                    'guard' => fn($post) => $post->author->can('publish-posts'),
                ],
                'reject' => [
                    'label' => 'Request Changes',
                    'from' => ['in_review'],
                    'to' => 'draft',
                ],
            ],
        ];
    }
}

// Usage
$post->workflow()->apply('submit');
if ($post->workflow()->can('approve')) {
    // Show approve button
}
```

### Level 2: Database Workflow with User Tasks

```json
{
  "id": "invoice-approval",
  "label": "Invoice Approval Workflow",
  "version": "1.0.0",
  "initialState": "draft",
  
  "dataSchema": {
    "amount": { "type": "number", "required": true },
    "department": { "type": "string" },
    "requester_id": { "type": "number", "required": true },
    "vendor_id": { "type": "number", "required": true }
  },
  
  "states": {
    "draft": {
      "label": "Draft"
    },
    
    "pending_manager_approval": {
      "label": "Pending Manager Approval",
      "type": "task",
      "task": {
        "assignee": {
          "role": "manager"
        }
      }
    },
    
    "pending_finance_approval": {
      "label": "Pending Finance Approval",
      "type": "task",
      "task": {
        "assignee": {
          "role": "finance"
        },
        "approvalStrategy": {
          "type": "quorum",
          "requiredApprovals": 2,
          "totalApprovers": 3
        }
      }
    },
    
    "approved": {
      "label": "Approved",
      "onEntry": [
        {
          "type": "plugin",
          "plugin": "slack_notification",
          "inputs": {
            "channel": "#finance",
            "message": "Invoice #{{data.invoice_number}} approved."
          }
        },
        {
          "type": "plugin",
          "plugin": "email_notification",
          "inputs": {
            "to": "{{data.requester_email}}",
            "subject": "Your invoice was approved"
          }
        }
      ]
    },
    
    "rejected": {
      "label": "Rejected"
    }
  },
  
  "transitions": {
    "submit_low_value": {
      "label": "Submit for Approval",
      "from": ["draft"],
      "to": "pending_manager_approval",
      "condition": {
        "type": "expression",
        "expression": "data.amount <= 5000"
      }
    },
    
    "submit_high_value": {
      "label": "Submit for Approval (High Value)",
      "from": ["draft"],
      "to": "pending_finance_approval",
      "condition": {
        "type": "expression",
        "expression": "data.amount > 5000"
      }
    },
    
    "manager_approve": {
      "label": "Approve",
      "from": ["pending_manager_approval"],
      "to": "approved"
    },
    
    "finance_approve": {
      "label": "Approve",
      "from": ["pending_finance_approval"],
      "to": "approved"
    },
    
    "reject": {
      "label": "Reject",
      "from": ["pending_manager_approval", "pending_finance_approval"],
      "to": "rejected"
    }
  }
}
```

### Level 3: Full Automation with SLA & Escalation

```json
{
  "id": "purchase-order",
  "label": "Purchase Order Processing",
  "version": "2.1.0",
  "initialState": "draft",
  
  "sla": {
    "duration": "5 business days",
    "warningThreshold": 0.8,
    "onWarning": [
      {
        "type": "plugin",
        "plugin": "email_notification",
        "inputs": {
          "to": "{{task.assignee.email}}",
          "subject": "SLA WARNING: PO approval nearing deadline"
        }
      }
    ],
    "onBreach": [
      {
        "type": "plugin",
        "plugin": "email_notification",
        "inputs": {
          "to": "director@example.com",
          "subject": "SLA BREACH: PO {{data.po_number}} overdue"
        }
      }
    ]
  },
  
  "states": {
    "draft": {
      "label": "Draft"
    },
    
    "pending_approval": {
      "label": "Pending Manager Approval",
      "type": "task",
      "task": {
        "assignee": {
          "role": "manager",
          "department": "{{data.department}}"
        },
        "approvalStrategy": {
          "type": "majority",
          "minimumApprovals": 2
        }
      },
      
      "automation": {
        "escalation": [
          {
            "after": "24 hours",
            "action": "notify",
            "plugin": "email_notification",
            "inputs": {
              "subject": "REMINDER: PO {{data.po_number}} awaits approval"
            }
          },
          {
            "after": "48 hours",
            "action": "reassign",
            "to": {
              "role": "director",
              "department": "{{data.department}}"
            },
            "notify": true,
            "reason": "Escalated due to 48-hour inactivity"
          },
          {
            "after": "72 hours",
            "action": "reassign",
            "to": {
              "role": "vp_operations"
            },
            "notify": true,
            "reason": "Critical escalation - 72 hours overdue"
          }
        ],
        
        "reminders": [
          {
            "every": "12 hours",
            "until": "task_completed",
            "plugin": "slack_notification",
            "inputs": {
              "channel": "@{{task.assignee.username}}",
              "message": "You have pending PO approval: {{data.po_number}}"
            }
          }
        ]
      }
    },
    
    "approved": {
      "label": "Approved",
      "onEntry": [
        {
          "type": "plugin",
          "plugin": "sap_create_po",
          "inputs": {
            "vendor": "{{data.vendor_id}}",
            "amount": "{{data.amount}}",
            "items": "{{data.line_items}}"
          },
          "compensation": {
            "plugin": "sap_cancel_po",
            "inputs": {
              "po_number": "{{activity_result.po_number}}"
            }
          }
        }
      ]
    }
  },
  
  "transitions": {
    "submit": {
      "label": "Submit for Approval",
      "from": ["draft"],
      "to": "pending_approval"
    },
    
    "approve": {
      "label": "Approve",
      "from": ["pending_approval"],
      "to": "approved"
    }
  }
}
```

### Extensible Condition System

```json
{
  "transitions": {
    "submit_to_manager": {
      "from": ["draft"],
      "to": "pending_manager",
      "condition": {
        "type": "expression",
        "expression": "data.amount < 5000"
      }
    },
    
    "submit_to_finance": {
      "from": ["draft"],
      "to": "pending_finance",
      "condition": {
        "type": "role_check",
        "requiredRole": "financial_controller",
        "user": "{{data.requester_id}}"
      }
    },
    
    "submit_urgent": {
      "from": ["draft"],
      "to": "pending_urgent_approval",
      "condition": {
        "type": "and",
        "conditions": [
          {
            "type": "expression",
            "expression": "data.priority == 'urgent'"
          },
          {
            "type": "date_range",
            "field": "data.required_by",
            "operator": "within",
            "value": "48 hours"
          }
        ]
      }
    },
    
    "submit_complex": {
      "from": ["draft"],
      "to": "pending_complex_approval",
      "condition": {
        "type": "custom",
        "evaluator": "App\\Workflows\\Conditions\\ComplexPOCondition",
        "parameters": {
          "threshold": 10000,
          "departmentRules": "strict"
        }
      }
    }
  }
}
```

**Built-in Condition Types:**
- `expression` - Simple expressions: `data.field operator value`
- `role_check` - User has specific role
- `attribute_comparison` - Compare model attributes
- `date_range` - Date/time comparisons
- `and` - All conditions must pass
- `or` - At least one condition must pass
- `not` - Negate condition result
- `custom` - Call custom PHP class implementing `ConditionEvaluatorContract`

### Multi-Approver Strategies

```json
{
  "states": {
    "pending_committee_approval": {
      "type": "task",
      "task": {
        "assignee": {
          "users": [101, 102, 103, 104, 105]
        },
        "approvalStrategy": {
          "type": "weighted",
          "weights": {
            "101": 2.0,
            "102": 2.0,
            "103": 1.5,
            "104": 1.0,
            "105": 1.0
          },
          "requiredWeight": 4.0
        }
      }
    },
    
    "pending_board_approval": {
      "type": "task",
      "task": {
        "assignee": {
          "role": "board_member"
        },
        "approvalStrategy": {
          "type": "quorum",
          "requiredApprovals": 5,
          "totalApprovers": 7,
          "allowAbstention": true
        }
      }
    },
    
    "pending_dual_control": {
      "type": "task",
      "task": {
        "assignee": {
          "users": [201, 202]
        },
        "approvalStrategy": {
          "type": "unison",
          "requireAllApprovals": true
        }
      }
    }
  }
}
```

**Built-in Approval Strategies:**
- `unison` - ALL assignees must approve (100% agreement)
- `majority` - More than 50% must approve
- `quorum` - Specific number/percentage must approve (e.g., 3 of 5)
- `weighted` - Votes have different weights, reach threshold weight
- `first` - First approval wins (race condition)
- `custom` - Custom PHP class implementing `ApprovalStrategyContract`

---

## Package Structure

```
packages/nexus-workflow/
├── src/
│   ├── Core/                          # Framework-agnostic (NO framework dependencies)
│   │   ├── Contracts/
│   │   │   ├── WorkflowEngineContract.php
│   │   │   ├── ActivityContract.php           # execute() + compensate()
│   │   │   ├── ConditionEvaluatorContract.php # Extensible conditions
│   │   │   ├── ApprovalStrategyContract.php   # Extensible approval logic
│   │   │   ├── TriggerContract.php
│   │   │   ├── TimerContract.php
│   │   │   ├── StorageContract.php
│   │   │   └── NotificationContract.php
│   │   │
│   │   ├── Engine/
│   │   │   ├── WorkflowEngine.php
│   │   │   ├── StateManager.php
│   │   │   ├── TokenEngine.php                # BPMN-inspired token concept
│   │   │   └── ExecutionContext.php
│   │   │
│   │   ├── Services/
│   │   │   ├── StateTransitionService.php
│   │   │   ├── ConditionEvaluator.php         # Built-in condition types
│   │   │   ├── ApprovalStrategyManager.php    # Built-in strategies
│   │   │   ├── EscalationService.php
│   │   │   ├── DelegationService.php
│   │   │   ├── SlaManagementService.php
│   │   │   └── CompensationService.php
│   │   │
│   │   └── DTOs/
│   │       ├── WorkflowDefinition.php
│   │       ├── WorkflowInstance.php
│   │       └── TaskInstance.php
│   │
│   ├── Strategies/                    # Built-in approval strategies
│   │   ├── UnisonStrategy.php
│   │   ├── MajorityStrategy.php
│   │   ├── QuorumStrategy.php
│   │   ├── WeightedStrategy.php
│   │   └── FirstStrategy.php
│   │
│   ├── Conditions/                    # Built-in condition evaluators
│   │   ├── ExpressionCondition.php
│   │   ├── RoleCheckCondition.php
│   │   ├── AttributeCondition.php
│   │   ├── DateRangeCondition.php
│   │   ├── AndCondition.php
│   │   ├── OrCondition.php
│   │   └── NotCondition.php
│   │
│   ├── Gateways/                      # BPMN-inspired patterns
│   │   ├── ExclusiveGateway.php
│   │   ├── ParallelGateway.php
│   │   ├── InclusiveGateway.php
│   │   └── EventBasedGateway.php
│   │
│   ├── Plugins/                       # Built-in activities
│   │   ├── Activities/
│   │   │   ├── EmailNotificationActivity.php
│   │   │   ├── SlackNotificationActivity.php
│   │   │   ├── WebhookActivity.php
│   │   │   └── DatabaseUpdateActivity.php
│   │   ├── Triggers/
│   │   │   ├── ManualTrigger.php
│   │   │   ├── ScheduleTrigger.php
│   │   │   └── WebhookTrigger.php
│   │   └── PluginManager.php
│   │
│   ├── Timers/                        # Event-driven timer system
│   │   ├── TimerQueue.php
│   │   ├── TimerWorker.php
│   │   └── Adapters/
│   │       ├── RedisTimerAdapter.php
│   │       └── DatabaseTimerAdapter.php
│   │
│   ├── Http/                          # REST API
│   │   └── Controllers/
│   │       ├── WorkflowController.php
│   │       ├── TaskController.php
│   │       └── InboxController.php
│   │
│   ├── Adapters/                      # Framework adapters
│   │   └── Laravel/
│   │       ├── Traits/
│   │       │   └── HasWorkflow.php    # Level 1 trait
│   │       ├── Models/                # Eloquent HERE (not Core)
│   │       │   ├── WorkflowDefinition.php
│   │       │   ├── WorkflowInstance.php
│   │       │   ├── WorkflowTask.php
│   │       │   ├── WorkflowTaskApproval.php
│   │       │   ├── WorkflowDelegation.php
│   │       │   └── WorkflowTimer.php
│   │       ├── Services/
│   │       │   ├── WorkflowInbox.php
│   │       │   └── WorkflowPromoter.php
│   │       ├── Commands/
│   │       │   ├── ProcessTimersCommand.php
│   │       │   ├── CheckEscalationsCommand.php
│   │       │   └── MonitorSlaCommand.php
│   │       └── WorkflowServiceProvider.php
│   │
│   └── Events/                        # Domain events
│       ├── TransitionStarted.php
│       ├── TransitionCompleted.php
│       ├── TaskAssigned.php
│       ├── TaskCompleted.php
│       └── SlaBreached.php
│
├── database/
│   └── migrations/
│       ├── 2025_11_01_000001_create_workflow_definitions_table.php
│       ├── 2025_11_01_000002_create_workflow_instances_table.php
│       ├── 2025_11_01_000003_create_workflow_history_table.php
│       ├── 2025_11_01_000004_create_workflow_tasks_table.php
│       ├── 2025_11_01_000005_create_workflow_task_approvals_table.php
│       ├── 2025_11_01_000006_create_workflow_delegations_table.php
│       ├── 2025_11_01_000007_create_workflow_timers_table.php
│       └── 2025_11_01_000008_create_workflow_sla_tracking_table.php
│
└── tests/
    ├── Unit/
    │   ├── StateTransitionTest.php
    │   ├── ApprovalStrategies/
    │   │   ├── UnisonStrategyTest.php
    │   │   ├── MajorityStrategyTest.php
    │   │   └── WeightedStrategyTest.php
    │   └── Conditions/
    │       ├── ExpressionConditionTest.php
    │       └── CustomConditionTest.php
    └── Feature/
        ├── Level1StateMachineTest.php
        ├── Level2WorkflowTest.php
        ├── Level3AutomationTest.php
        ├── MultiApproverTest.php
        └── EscalationTest.php
```

---

## Success Metrics

| Metric | Target | Measurement Period | Why It Matters |
|--------|--------|-------------------|----------------|
| **Adoption Rate** | > 2,000 installs | 6 months | Validates mass market appeal (Level 1) |
| **Time to Hello World** | < 5 minutes | Ongoing | Measures Level 1 developer experience |
| **Promotion Rate** | > 10% users ask about Level 2 | 6 months | Shows users graduating to workflows |
| **ERP Feature Usage** | > 5% use SLA/Escalation | 6 months | Validates high-end ERP niche |
| **Critical Bugs** | < 5 P0 bugs | 6 months | State corruption, deadlocks, data loss |
| **Test Coverage** | > 85% code coverage | Ongoing | Core engine quality |
| **Documentation Quality** | < 10 support questions/week | After 3 months | Clarity of progressive disclosure |

---

## Development Phases

### Phase 1: Level 1 Foundation (Weeks 1-3)
- Implement `HasWorkflow` trait
- In-model workflow definition parser
- State transition engine (ACID-compliant)
- Basic history tracking
- Unit tests for state machine logic

### Phase 2: Level 2 Workflows (Weeks 4-8)
- Database-driven workflow definitions
- User Task implementation
- Conditional routing engine
- Task inbox service
- Multi-approver strategies (unison, majority, quorum)
- Plugin activity system
- Feature tests for approval workflows

### Phase 3: Level 3 Automation (Weeks 9-12)
- Event-driven timer system
- Integration tests

### Phase 4: Extensibility (Weeks 13-14)
- Custom condition evaluators
- Custom approval strategies
- Plugin discovery and registration
- Documentation for extension points

### Phase 5: Polish & Launch (Weeks 15-16)
- Comprehensive documentation
- Video tutorials (5-minute quickstart, Level 2 promotion, Level 3 automation)
- Performance optimization
- Security audit
- Beta testing with ERP users

---

## Testing Requirements

### Unit Tests
- State machine logic (all transition combinations)
- Approval strategy algorithms
- Condition evaluation (all operators)
- Timer queue operations
- Delegation logic

### Feature Tests
- Level 1: In-model workflow complete lifecycle
- Level 2: Database workflow with user tasks
- Level 3: Escalation, SLA, delegation scenarios
- Multi-approver: All strategies (unison, majority, quorum, weighted)
- Extensibility: Custom conditions, custom strategies

### Integration Tests
- Laravel integration (Eloquent, Queue, Events)
- nexus-tenancy integration (auto-scoping)
- nexus-audit-log integration
- Performance under load (10,000 concurrent workflows)

### Acceptance Tests
- US-001 through US-025 (all user stories)
- Time-to-hello-world < 5 minutes
- Promotion from Level 1 to Level 2 without code changes

---

## Dependencies

### Required
- PHP ≥ 8.2
- Database: MySQL 8.0+, PostgreSQL 12+, SQLite 3.35+, SQL Server 2019+

### Optional
- Laravel ≥ 12.x (for Laravel adapter)
- nexus-tenancy (auto-detected for multi-tenancy)
- nexus-audit-log (auto-detected for enhanced audit)
- Redis (for timer queue and caching)

---

## Glossary

- **Level 1**: Simple state machine using `HasWorkflow` trait with in-model definitions
- **Level 2**: Database-driven workflows with User Tasks, conditional routing, task inbox
- **Level 3**: Full automation with SLA tracking, escalation, delegation
- **User Task**: A workflow state that creates an actionable task in user's inbox
- **Approval Strategy**: Algorithm determining when multi-approver task can proceed
- **Escalation**: Automatic reassignment of overdue task to higher authority
- **SLA**: Service Level Agreement - time constraint on workflow completion
- **Delegation**: Temporary routing of user's tasks to another user
- **Compensation**: Rollback logic executed when workflow fails
- **Token**: BPMN-inspired concept tracking execution path in parallel flows
- **Gateway**: Decision point in workflow (exclusive, parallel, inclusive)

---

**Document Version:** 3.0.0  
**Last Updated:** November 14, 2025  
**Status:** Ready for Implementation
