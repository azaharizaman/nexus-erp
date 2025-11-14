Your new, focused approach **solves this perfectly.**

You are no longer building a "simple" engine and a "complex" engine. You are building **one, progressively complex engine** that starts simple and grows with the user. This is the key to mass appeal *and* ERP-level capability.

Here is the analysis of what's lacking and how to structure the requirements for this new "atomic" vision.

-----

### 1\. The Core Missing Aspect: The "Progressive" User Journey

To appeal to the greater mass *and* serve the ERP user, the requirements must be built around a "progressive disclosure" model. The user's journey is what's missing.

  * **Level 1: The "State Machine" (Mass Appeal)**

      * **User:** A developer adding a `Post` model.
      * **Need:** `draft` -\> `published`.
      * **Key Missing Requirement:** A `HasWorkflow` trait that lets them define the state machine *inside their Eloquent model* with zero database tables. This is the **most critical** feature for mass adoption.

  * **Level 2: The "Workflow" (ERP Foundation)**

      * **User:** A developer building an invoice approval.
      * **Need:** `draft` -\> `pending_approval` -\> `approved`. This step must create a **task** in an inbox.
      * **Key Missing Requirement:** A "workflow definition" (e.g., your JSON format) that can be *promoted* from the model (Level 1) to a database table or file, adding **User Tasks** and **Conditional Routing**.

  * **Level 3: The "Automation Engine" (ERP Power-User)**

      * **User:** An ERP developer with a complex Purchase Order.
      * **Need:** The same `pending_approval` task from Level 2, but now with **Escalation**, **Delegation**, and **SLA tracking**.
      * **Key Missing Requirement:** The engine must be ableT to attach your "superior" automation features (SLA, Escalation) to the same states and tasks defined in Level 2.

This journey—from simple state machine to a full automation engine, **all within one atomic package**—is what will make it appeal to everyone. You're not forcing the "Level 1" user to understand the "Level 3" concepts.

-----

### 2\. New, Focused Requirement Sets

Here is a granular set of requirements for this new "Atomic State Machine & Approval Engine."

#### 2.1 Personas and User Stories

| Persona | Role | Primary Goal |
| :--- | :--- | :--- |
| **P1: The "Mass Market" Developer** | Full-stack Developer (e.g., at a small agency) | "I need to add a simple `draft` -\> `published` state to my `Post` model in under 5 minutes without reading a 50-page doc." |
| **P2: The "In-House" ERP Developer** | Back-end Developer (e.g., at a manufacturing company) | "I need to build a reliable, multi-step purchase order approval workflow that integrates with my existing `PurchaseOrder` model and our 'Roles' system." |
| **P3: The "End-User"** | Manager / Employee | "I need to see all my pending tasks in one place, approve/reject them, and delegate my inbox when I'm on vacation." |

| User Story ID | Persona | Story |
| :--- | :--- | :--- |
| **US-001** | P1 | As a "Mass Market" Developer, I want to add a `HasWorkflow` trait to my Eloquent model to manage its `status` column. |
| **US-002** | P1 | As a "Mass Market" Developer, I want to define a simple state machine (states and transitions) as a single array or method *inside* my Eloquent model. |
| **US-003** | P1 | As a "Mass Market" Developer, I want to call `$model->workflow()->apply('transition_name')` to execute a state change. |
| **US-004** | P1 | As a "Mass Market" Developer, I want to call `$model->workflow()->can('transition_name')` to check if a transition is allowed, so I can show/hide a button in the UI. |
| **US-005** | P2 | As an "In-House" Developer, I want to move my workflow definition from an in-model array to a database-driven JSON definition when it gets complex, without refactoring all my code. |
| **US-006** | P2 | As an "In-House" Developer, I want to define a "User Task" step that halts the workflow and assigns a task to a specific user or role (e.g., 'Finance-Manager'). |
| **US-007** | P2 | As an "In-House" Developer, I want to use conditional routing (e.g., "if amount \> 10,000, add 'Director' approval step"). |
| **US-008** | P2 | As an "In-House" Developer, I want the system to automatically **escalate** a pending task to a manager if it's not actioned within 48 hours. |
| **US-009** | P2 | As an "In-House" Developer, I want to define an **SLA** for the entire process (e.g., "PO must be approved in 3 days") and fire an event if it's breached. |
| **US-010** | P3 | As an End-User, I want to go to one "Inbox" and see all my pending tasks. |
| **US-011** | P3 | As a Manager, I want to **delegate** my task inbox to my assistant from July 1st to July 15th. |

-----

#### 2.2 Functional Requirements (FR)

| ID | Requirement | Persona | Priority |
| :--- | :--- | :--- | :--- |
| **FR-SM-001** | Provide a `HasWorkflow` trait for Eloquent models. | P1 | **High** |
| **FR-SM-002** | Support **in-model definitions** (e.g., `public function workflow(): array { ... }`) for simple state machines (states, transitions). | P1 | **High** |
| **FR-SM-003** | Provide core methods: `workflow()->apply()`, `workflow()->can()`, `workflow()->history()`. | P1 | **High** |
| **FR-SM-004** | Automatically manage a `status` column on the attached model (configurable). | P1 | **High** |
| **FR-WF-001** | Support **database-driven definitions** (JSON format) that override in-model definitions for "promoting" a simple state machine. | P2 | **High** |
| **FR-WF-002** | Support **User Task** definitions that pause the workflow and create a `TaskInstance` record. | P2 | **High** |
| **FR-WF-003** | Support task assignment to a specific User ID or a Role string. | P2 | **High** |
| **FR-WF-004** | Support **conditional routing** based on workflow data (e.g., `data.amount > 1000`). | P2 | **High** |
| **FR-WF-005** | Support **parallel and sequential** flows (e.g., "Finance AND HR must approve"). | P2 | Medium |
| **FR-WF-006** | Provide a `WorkflowInbox` service to query all pending tasks for a user. | P3 | **High** |
| **FR-WF-007** | Support **compensation logic** for failed workflows (e.g., defining a "rollback" activity). | P2 | Medium |
| **FR-WF-008** | Support **SLA tracking** (per-step and per-workflow) with breach notifications. | P2 | **High** |
| **FR-WF-009** | Support **task escalation rules** (e.g., "after 48 hours, re-assign to 'Director'"). | P2 | **High** |
| **FR-WF-010** | Support **user-level delegation** (e.g., "all my tasks from date X to Y go to User Z"). | P3 | **High** |
| **FR-EXT-001** | Provide a **plugin interface** for custom activities (e.g., `SendSlackNotificationActivity`). | P2 | **High** |
| **FR-EXT-002** | Support **custom triggers** (e.g., Webhook, Schedule) to *start* workflows. | P2 | Medium |
| **FR-EXT-003** | Allow **custom condition evaluators** for complex routing rules. | P2 | Low |

-----

#### 2.3 Non-Functional Requirements (NFR)

| ID | Type | Requirement |
| :--- | :--- | :--- |
| **PR-001** | Performance | State transition (`apply()`) execution time \< 100ms (excluding custom plugins). |
| **PR-002** | Performance | Task inbox query (1,000 pending tasks) \< 500ms. |
| **PR-003** | Performance | Escalation/SLA check job (10,000 active workflows) \< 2 seconds. |
| **SR-001** | Security | Prevent users from actioning tasks not assigned to them (enforced at engine level). |
| **SR-002** | Security | Sanitize all expression language inputs to prevent code injection. |
| **SR-003** | Security | All database queries must be tenant-aware if `nexus-tenancy` is installed (auto-detected). |
| **SR-004** | Reliability | All workflow state changes **MUST** be ACID-compliant (wrapped in a database transaction). |
| **SR-005** | Reliability | A failed plugin activity (e.g., `SendEmailActivity`) must not block the state transition unless explicitly configured to do so. It should be "fire and forget" or queued. |
| **SCR-001** | Scalability | The engine must support async/queued execution of custom activities (e.g., via Laravel Queue). |
| **SCR-002** | Maintainability | **Framework Agnostic Core:** The core engine (state logic, transitions, JSON parser) MUST NOT depend on Laravel. |
| **SCR-003** | Maintainability | **Laravel Adapter:** All Laravel-specific features (Eloquent trait, queue integration, `nexus-tenancy` integration) MUST be in an adapter. |

-----

#### 2.4 Business Rules (BR)

| ID | Rule | Engine |
| :--- | :--- | :--- |
| **BR-001** | A user cannot approve their own submission (configurable per workflow). | JSON |
| **BR-002** | Workflow state changes MUST be ACID-compliant (see SR-004). | JSON |
| **BR-003** | Escalations MUST occur automatically when task deadlines are exceeded. | JSON (Superior) |
| **BR-004** | Compensation MUST execute in reverse order of completed activities. | JSON MN |
| **BR-005** | Delegation chains MUST NOT exceed 3 levels to prevent infinite delegation. | JSON (Superior) |
| **BR-006** | A "Level 1" in-model state machine MUST be 100% compatible with the "Level 2" JSON definition schema. | Core |
| **BR-007** | A workflow instance MUST be tied to a single, specific model instance (e.g., one `PurchaseOrder`). | Core |

-----

#### 2.5 Acceptance Criteria

| Story | Acceptance Criteria |
| :--- | :--- |
| **US-001** | 1. Add `use HasWorkflow` to a `Post` model.<br>2. Add `public function workflow() { return ['states' => ...]; }`.<br>3. `php artisan migrate` is **not** required.<br>4. Calling `$post->workflow()->apply('publish')` changes `$post->status` to 'published'. |
| **US-005** | 1. The developer runs `php artisan workflow:publish-definition Post`.<br>2. This creates a `post-workflow.json` in the `database/workflows` folder (or similar).<br>3. The developer deletes the `workflow()` method from the `Post` model.<br>4. The *exact same* `$post->workflow()->apply('publish')` code continues to work, now reading from the JSON file. |
| **US-006** | 1. The developer edits the `post-workflow.json` file.<br>2. They change the `'published'` state to be a "User Task" assigned to the 'Editor' role.<br>3. `php artisan migrate` is run to create the `tasks` table.<br>4. When `$post->workflow()->apply('publish')` is called, the `Post` status becomes 'pending\_publish' and a new row is created in the `tasks` table assigned to 'Editor'. |
| **US-008** | 1. The developer adds an `escalation` rule to the "User Task" in the JSON file.<br>2. Rule: `{"after": "2 days", "escalate_to": "Head-Editor"}`.<br>3. A task is created and left for 3 days.<br>4. The `workflow:check-escalations` command is run.<br>5. The original task is marked 'escalated', and a new task is created for 'Head-Editor'. |
| **US-011** | 1. A manager (User ID 5) adds a delegation record: "Delegate to User ID 10 from 2025-11-15 to 2025-11-20".<br>2. A new workflow task is assigned to User ID 5 on 2025-11-16.<br>3. The `tasks` table shows the task is *immediately* and *automatically* assigned to User ID 10.<br>4. The task history logs: "Task assigned to User 10 (delegate for User 5)". |

-----

#### 2.6 Success Metrics

| Metric | Target | Why it Matters |
| :--- | :--- | :--- |
| **Adoption (Mass Appeal)** | \> 2,000 installs in 6 months | Proves the "Level 1" (State Machine) use case is attracting the mass market. |
| **Time-to-Hello-World** | \< 5 minutes | Measures the success of **US-001**. Can a new dev get a state machine working in 5 mins? |
| **"Promotion" Rate** | \> 10% of users ask questions about "Level 2" (User Tasks) | Proves that users are successfully "graduating" from simple state machines to real workflows. |
| **ERP Feature Usage** | \> 5% of users ask about "Level 3" (SLA, Escalation) | Proves the package is successfully serving the high-end ERP/manufacturing niche. |
| **Stability** | \< 5 "P0" (Critical) bugs related to state corruption or deadlocks in 6 months. | Proves the atomic, transactional core is reliable. |

This new plan is far more focused, achievable, and marketable. You are building a single, excellent product that *grows* with your user, from their first blog post to their full-scale manufacturing ERP.

Here is a proposed JSON schema and a set of examples that demonstrate the "progressive" journey, from a simple state machine to a full-blown ERP workflow.

The core philosophy is **Progressive Disclosure**. A developer only needs to learn the parts of the schema that are relevant to their current level of complexity.

-----

### 1\. The Core Schema Philosophy

The schema is built on two primary top-level keys:

  * `states`: This defines the "nouns" or "places" in your workflow (e.g., `draft`, `pending_approval`). A state can be a simple, passive status, or it can be an active "wait state" (like a User Task).
  * `transitions`: This defines the "verbs" or "actions" that move the workflow between states (e.g., `submit`, `approve`, `reject`). Transitions are where all conditional logic lives.

All other features (plugins, SLAs, escalations) are attached to these two core concepts.

-----

### 2\. Example 1: The "Level 1" (Mass Appeal) State Machine

This is the simplest possible use case, mapping to user story **US-001**. It's just a state machine for a blog post. This exact structure could be returned from a `workflow()` method in an Eloquent model, **requiring zero database tables or config files**.

**`blog-post.json` (Level 1: State Machine)**

```json
{
  "id": "blog-post",
  "label": "Simple Blog Post Workflow",
  "initialState": "draft",

  "states": {
    "draft": {
      "label": "Draft"
    },
    "in_review": {
      "label": "In Review"
    },
    "published": {
      "label": "Published"
    }
  },

  "transitions": {
    "submit": {
      "label": "Submit for Review",
      "from": ["draft"],
      "to": "in_review"
    },
    "approve": {
      "label": "Approve & Publish",
      "from": ["in_review"],
      "to": "published"
    },
    "reject": {
      "label": "Request Changes",
      "from": ["in_review"],
      "to": "draft"
    }
  }
}
```

**Key Features for Mass Appeal:**

  * It's human-readable and simple.
  * It can live in a model file.
  * The developer only needs to learn `states`, `transitions`, `from`, and `to`.
  * It solves the "80% use case" immediately.

-----

### 3\. Example 2: The "Level 2" (Workflow)

The developer now wants to "promote" their workflow. The `Post` model no longer has the `workflow()` method, and the engine now reads this JSON from the database.

This example adds **User Tasks**, **Conditional Routing**, and **Plugin Actions**, mapping to **US-005**, **US-006**, and **US-007**.

**`invoice-approval.json` (Level 2: Workflow)**

```json
{
  "id": "invoice-approval",
  "label": "Invoice Approval Workflow",
  "initialState": "draft",

  "dataSchema": {
    "amount": { "type": "number", "required": true },
    "department": { "type": "string" },
    "requester_id": { "type": "number", "required": true }
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
            "message": "Invoice has been approved."
          }
        },
        {
          "type": "plugin",
          "plugin": "email_notification",
          "inputs": {
            "to": "data.requester_email",
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
    "submit": {
      "label": "Submit for Approval",
      "from": ["draft"],
      "to": "pending_manager_approval",
      "condition": "data.amount <= 5000"
    },
    "submit_high_value": {
      "label": "Submit for Approval (High Value)",
      "from": ["draft"],
      "to": "pending_finance_approval",
      "condition": "data.amount > 5000"
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

**Key "Level 2" Additions:**

  * `dataSchema`: Defines the "context" data (like `data.amount`) for validation and conditions.
  * `states.type: "task"`: This tells the engine to **pause and create a task** in the inbox.
  * `states.task.assignee`: Defines *who* gets the task.
  * `states.onEntry`: A simple hook to fire "fire-and-forget" plugins, like notifications.
  * `transitions.condition`: This is the conditional routing. The engine checks `data.amount` and picks the *only* valid transition.

-----

### 4\. Example 3: The "Level 3" (ERP/Automation Engine)

This is the final, "power-user" form. It builds on Level 2 by adding the **Automation** block, which contains **SLA** and **Escalation** rules. This maps to **US-008** and **US-009**.

This is the *same* schema, just with a new, optional block. The "Level 1" developer never has to see or know this exists.

**`purchase-order.json` (Level 3: Automation Engine)**

```json
{
  "id": "purchase-order",
  "label": "Purchase Order Processing",
  "initialState": "draft",
  
  "dataSchema": {
    "amount": { "type": "number", "required": true },
    "vendor": { "type": "string" }
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
          "role": "manager"
        }
      },

      "automation": {
        "sla": {
          "duration": "3 days",
          "onBreach": [
            {
              "type": "plugin",
              "plugin": "email_notification",
              "inputs": {
                "to": "director@example.com",
                "subject": "SLA BREACH: PO approval is overdue by 3 days."
              }
            }
          ]
        },
        "escalation": [
          {
            "after": "24 hours",
            "action": "notify",
            "plugin": "email_notification",
            "inputs": {
              "subject": "REMINDER: A PO is awaiting your approval."
            }
          },
          {
            "after": "48 hours",
            "action": "reassign",
            "to": {
              "role": "director"
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
            "vendor": "data.vendor",
            "amount": "data.amount"
          }
        }
      ]
    }
  },

  "transitions": {
    "submit": {
      "label": "Submit",
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

**Key "Level 3" Additions:**

  * `automation` block: This is the container for all advanced, time-based logic.
  * `automation.sla`: A "process-level" timer. If the *entire* task state isn't completed in 3 days, it fires the `onBreach` action.
  * `automation.escalation`: A set of "step-by-step" rules.
      * `after: "24 hours"`: Sends a reminder.
      * `after: "48 hours"`: **Re-assigns** the task to the 'director' role. This is the core of escalation logic.

This progressive schema fulfills your new "atomic" vision perfectly. It's simple for the 80% and powerful for the 20%, without forcing the two to mix.

That's a great next step. The complexity of the new, atomic engine is largely contained in the database schema, as it needs to track every instance, task, and automation rule.

Here are the essential database tables required to support the "State Machine & Approval Engine" plan, grouping them logically based on their purpose.

***

## 1. Core Workflow Tables (State & History)

These tables track the lifecycle of the actual process, tying it to your Eloquent models (the "subjects").

| Table Name | Description | Key Columns (Field Type) | Role in Workflow |
| :--- | :--- | :--- | :--- |
| `workflow_definitions` | Stores the **Level 2/3 JSON schema** (e.g., `purchase-order.json`). | `id` (PK, string), `name` (string), **`schema` (JSON)**, `is_active` (boolean) | Allows dynamic, database-driven workflows. |
| `workflow_instances` | Tracks the state of a single workflow running on a subject. | `id` (PK), **`subject_type` (string)**, **`subject_id` (integer)**, `definition_id` (FK), `current_state` (string), **`context_data` (JSON)**, `started_at` (timestamp), `ended_at` (timestamp) | The master record for the running process. The `subject_type`/`subject_id` link to the `PurchaseOrder` or `Post` model. |
| `workflow_history` | The immutable audit log of everything that happened. | `id` (PK), `instance_id` (FK), `event_type` (string), `state_before` (string), `state_after` (string), `transition_name` (string), **`actor_id` (integer)**, `payload` (JSON), `created_at` (timestamp) | Supports **US-011** (Audit History). Critical for legal/compliance needs in an ERP. |

***

## 2. Task & Assignment Tables (Approval)

These tables support the "Approval Engine" part of the package, handling the human interaction.

| Table Name | Description | Key Columns (Field Type) | Role in Workflow |
| :--- | :--- | :--- | :--- |
| `workflow_tasks` | Stores the actual actionable item in the user's inbox. | `id` (PK), `instance_id` (FK), **`assigned_to_id` (integer)**, **`assigned_to_role` (string)**, `name` (string), `status` (string, e.g., 'pending', 'completed', 'escalated'), `due_at` (timestamp), `created_at` (timestamp) | Supports **FR-WF-002** (User Tasks) and **US-010** (Inbox). The engine queries this table to build the inbox. |
| `workflow_delegations` | Stores delegation rules set by managers. | `id` (PK), **`delegator_id` (integer)**, **`delegatee_id` (integer)**, `starts_at` (timestamp), `ends_at` (timestamp), `is_active` (boolean) | Supports **FR-WF-010** and **US-011** (Delegation). The engine checks this table *before* creating a record in `workflow_tasks`. |

***

## 3. Automation Tables (SLA & Escalation)

These tables allow the engine to monitor time-sensitive rules efficiently, instead of scanning the entire history repeatedly.

| Table Name | Description | Key Columns (Field Type) | Role in Workflow |
| :--- | :--- | :--- | :--- |
| `workflow_timers` | Stores all time-based events awaiting action (SLA, Escalation, Reminders). | `id` (PK), `instance_id` (FK), `task_id` (nullable FK), **`type` (string, e.g., 'sla_breach', 'escalation')**, **`trigger_at` (timestamp)**, `rule_payload` (JSON) | Supports **FR-WF-008** (SLA) and **FR-WF-009** (Escalation). A scheduled worker (`workflow:process-timers`) simply runs queries against this table looking for records where `trigger_at < now()`. |
| `workflow_lock` | A simple table to prevent concurrency issues (e.g., two users trying to approve the same task, or two scheduler jobs running the timer check simultaneously). | `id` (PK, unique), `lock_owner` (string), `expires_at` (timestamp) | Supports **PR-004** and **SR-004** (Concurrency & Reliability). |

---

### Conclusion

The key architectural design choice here is the **separation of the flow state (`workflow_instances`) from the human state (`workflow_tasks`) and the time state (`workflow_timers`)**.

This allows the engine to be fast and atomic:
1.  **To get the current state of a PO:** Query `workflow_instances`.
2.  **To get a manager's inbox:** Query `workflow_tasks` (checking `workflow_delegations` first).
3.  **To check for overdue items:** Query `workflow_timers`.

This structure will provide the necessary foundation for a highly reliable, high-performance ERP component.