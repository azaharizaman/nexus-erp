# nexus-workflow

**A Progressive Workflow & State Machine Engine for PHP/Laravel**

From 5-minute blog post state machines to enterprise-grade ERP approval automation—all in one atomic package.

---

## 🎯 The Story

Most workflow packages force an impossible choice:

- **Simple packages** like `spatie/laravel-model-states` handle basic state machines but hit a wall when you need approvals, SLA tracking, or escalations.
- **Complex BPM engines** like Camunda or Activiti are powerful but have steep learning curves, require dedicated infrastructure, and feel like overkill for 90% of use cases.

**What if there was a better way?**

What if you could start with a **5-minute state machine** on your Eloquent model, and then—as your needs grow—progressively add approval workflows, multi-user tasks, SLA tracking, escalation rules, and delegation... **without refactoring any code**?

That's **nexus-workflow**.

---

## ✨ The Progressive Journey

### Level 1: The 5-Minute State Machine (Mass Appeal)

**Who:** Every developer who needs simple status management  
**When:** Adding `draft` → `published` to a blog post, `pending` → `active` to a subscription  
**Time:** Under 5 minutes  
**Database Tables:** Zero

```php
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
                    'from' => ['draft'],
                    'to' => 'in_review',
                ],
                'approve' => [
                    'from' => ['in_review'],
                    'to' => 'published',
                ],
                'reject' => [
                    'from' => ['in_review'],
                    'to' => 'draft',
                ],
            ],
        ];
    }
}

// Usage - it's this simple
$post->workflow()->apply('submit');

if ($post->workflow()->can('approve')) {
    // Show approve button
}

$history = $post->workflow()->history(); // All transitions with timestamps
```

**That's it.** No config files. No database migrations. No service providers. Just add a trait and define your states.

---

### Level 2: Real Approval Workflows (In-House ERP)

**Who:** Backend developers building internal systems  
**When:** Purchase orders, expense reports, invoice approvals, HR workflows  
**Time:** ~1 hour to promote from Level 1  
**Database Tables:** Auto-migrated on first use

Now your business says: *"We need Finance AND HR to both approve high-value invoices."*

**Promote your workflow** to database-driven JSON:

```bash
php artisan workflow:promote Post
```

This generates `database/workflows/post-workflow.json`. Your **existing code doesn't change**. The same `$post->workflow()->apply()` now reads from the database.

**Add User Tasks & Conditional Routing:**

```json
{
  "states": {
    "pending_finance_approval": {
      "type": "task",
      "task": {
        "assignee": {
          "role": "finance"
        }
      }
    },
    "pending_hr_approval": {
      "type": "task",
      "task": {
        "assignee": {
          "role": "hr"
        }
      }
    }
  },
  
  "transitions": {
    "submit_low_value": {
      "from": ["draft"],
      "to": "pending_finance_approval",
      "condition": {
        "type": "expression",
        "expression": "data.amount <= 5000"
      }
    },
    "submit_high_value": {
      "from": ["draft"],
      "to": "pending_parallel_approval",
      "condition": {
        "type": "expression",
        "expression": "data.amount > 5000"
      }
    }
  }
}
```

**Task Inbox:**

```php
use Nexus\Workflow\Services\WorkflowInbox;

// Get all pending tasks for current user
$tasks = WorkflowInbox::forUser(auth()->user())
    ->pending()
    ->get();

// Approve a task
$task->approve('Looks good', ['attachment_id' => 123]);
```

---

### Level 3: ERP Automation (Production-Ready)

**Who:** Enterprise developers managing complex processes  
**When:** Your workflows need SLA tracking, automatic escalation, delegation, audit compliance  
**Time:** Add automation block to JSON  
**New Capabilities:** Everything enterprise workflows need

Now your business says: *"If a PO isn't approved in 48 hours, escalate to the director. Track SLA compliance. Support delegation when managers are on vacation."*

**Just add the `automation` block:**

```json
{
  "sla": {
    "duration": "3 business days",
    "onBreach": [
      {
        "type": "plugin",
        "plugin": "email_notification",
        "inputs": {
          "to": "director@example.com",
          "subject": "SLA BREACH: Purchase Order overdue"
        }
      }
    ]
  },
  
  "states": {
    "pending_approval": {
      "type": "task",
      "task": {
        "assignee": { "role": "manager" }
      },
      
      "automation": {
        "escalation": [
          {
            "after": "24 hours",
            "action": "notify",
            "plugin": "email_notification"
          },
          {
            "after": "48 hours",
            "action": "reassign",
            "to": { "role": "director" },
            "reason": "Escalated due to inactivity"
          }
        ]
      }
    }
  }
}
```

**Delegation:**

```php
// Manager delegates their inbox while on vacation
WorkflowDelegation::create([
    'delegator_id' => auth()->id(),
    'delegatee_id' => $assistant->id,
    'starts_at' => '2025-12-01',
    'ends_at' => '2025-12-15',
]);

// All tasks automatically route to assistant during this period
// Full audit trail: "Task assigned to User 42 (delegate for User 5)"
```

---

## 🚀 Why nexus-workflow Wins

### For the Mass Market (80% of Developers)

✅ **Fastest hello world** - Under 5 minutes from zero to working state machine  
✅ **Zero boilerplate** - No config files, migrations, or service providers for Level 1  
✅ **Learn as you grow** - Only learn Level 2 when you need it  
✅ **Works with existing models** - No refactoring required  
✅ **Familiar API** - If you know Eloquent, you know workflows

### For Enterprise/ERP (20% of Developers)

✅ **Battle-tested patterns** - Escalation, delegation, SLA tracking out of the box  
✅ **Multi-approver strategies** - Unison, majority, quorum, weighted voting  
✅ **Extensible everything** - Custom conditions, approval strategies, activities  
✅ **ACID compliance** - All state changes wrapped in transactions  
✅ **Full audit trail** - Immutable history for legal/compliance  
✅ **Scalable** - Event-driven timers, tested with 100,000+ concurrent workflows

---

## 📦 Installation

```bash
composer require nexus/workflow
```

That's it. Start using Level 1 immediately:

```php
use Nexus\Workflow\Traits\HasWorkflow;

class YourModel extends Model
{
    use HasWorkflow;
    
    public function workflow(): array
    {
        return [
            'initialState' => 'pending',
            'states' => [...],
            'transitions' => [...],
        ];
    }
}
```

---

## 📖 Core Concepts

### States

A **state** is a "place" your model can be. It can be:
- **Passive** - Just a status (e.g., `draft`, `published`)
- **Active (Task)** - Creates an actionable item in a user's inbox

```php
'states' => [
    'draft' => ['label' => 'Draft'],                    // Passive
    
    'pending_approval' => [                              // Active
        'type' => 'task',
        'task' => [
            'assignee' => ['role' => 'manager'],
        ],
    ],
]
```

### Transitions

A **transition** is an "action" that moves between states:

```php
'transitions' => [
    'submit' => [
        'from' => ['draft'],
        'to' => 'pending_approval',
        'guard' => fn($model) => $model->amount > 0,     // Optional condition
    ],
]
```

### Conditions

**Conditional routing** automatically selects the correct transition:

```json
{
  "transitions": {
    "submit_low_value": {
      "from": ["draft"],
      "to": "pending_manager",
      "condition": { "expression": "data.amount <= 1000" }
    },
    "submit_high_value": {
      "from": ["draft"],
      "to": "pending_director",
      "condition": { "expression": "data.amount > 1000" }
    }
  }
}
```

### Multi-Approver Strategies

When multiple people need to approve:

```json
{
  "task": {
    "assignee": {
      "users": [101, 102, 103]
    },
    "approvalStrategy": {
      "type": "majority",        // Options: unison, majority, quorum, weighted, first
      "minimumApprovals": 2
    }
  }
}
```

**Available strategies:**
- **unison** - ALL must approve (100% agreement)
- **majority** - More than 50% must approve
- **quorum** - Specific count (e.g., 3 of 5)
- **weighted** - Votes have different weights (e.g., VP = 2.0, Manager = 1.0)
- **first** - First approval wins

### Extensibility

**Custom Conditions:**

```php
use Nexus\Workflow\Contracts\ConditionEvaluatorContract;

class CustomCondition implements ConditionEvaluatorContract
{
    public function evaluate($context, $expression): bool
    {
        // Your custom logic
        return $context->data['department'] === 'Finance' 
            && $context->data['amount'] > 10000;
    }
}
```

**Custom Approval Strategies:**

```php
use Nexus\Workflow\Contracts\ApprovalStrategyContract;

class SeniorityBasedStrategy implements ApprovalStrategyContract
{
    public function canProceed($task, $approvals): bool
    {
        // Your custom voting logic
        $seniorApprovals = $approvals->filter(fn($a) => $a->user->seniority >= 5);
        return $seniorApprovals->count() >= 2;
    }
}
```

**Custom Activities (Plugins):**

```php
use Nexus\Workflow\Contracts\ActivityContract;

class SendToSAPActivity implements ActivityContract
{
    public function execute(ExecutionContext $context): ExecutionResult
    {
        // Your integration logic
        $result = SAP::createPurchaseOrder($context->data);
        
        return ExecutionResult::success([
            'sap_po_number' => $result->poNumber,
        ]);
    }
    
    public function compensate(ExecutionContext $context): CompensationResult
    {
        // Rollback logic if workflow fails
        SAP::cancelPurchaseOrder($context->activityResult['sap_po_number']);
        
        return CompensationResult::success();
    }
}
```

---

## 🎓 Complete Examples

### Example 1: Blog Post (Level 1)

**5-minute state machine for content approval:**

```php
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
                'archived' => ['label' => 'Archived'],
            ],
            
            'transitions' => [
                'submit' => [
                    'label' => 'Submit for Review',
                    'from' => ['draft'],
                    'to' => 'in_review',
                ],
                'approve' => [
                    'label' => 'Publish',
                    'from' => ['in_review'],
                    'to' => 'published',
                    'guard' => fn($post) => $post->author->can('publish-posts'),
                    'after' => fn($post) => $post->author->notify(new PostPublishedNotification($post)),
                ],
                'reject' => [
                    'label' => 'Request Changes',
                    'from' => ['in_review'],
                    'to' => 'draft',
                ],
                'archive' => [
                    'label' => 'Archive',
                    'from' => ['published'],
                    'to' => 'archived',
                ],
            ],
        ];
    }
}

// Usage
$post = Post::create(['title' => 'My Post', 'content' => '...']);
$post->workflow()->apply('submit');
$post->workflow()->apply('approve');  // Now published
```

### Example 2: Invoice Approval (Level 2)

**Database workflow with conditional routing and multi-approver:**

See `database/workflows/invoice-approval.json`:

```json
{
  "id": "invoice-approval",
  "label": "Invoice Approval Workflow",
  "initialState": "draft",
  
  "dataSchema": {
    "amount": { "type": "number", "required": true },
    "vendor_id": { "type": "number", "required": true },
    "department": { "type": "string", "required": true }
  },
  
  "states": {
    "draft": {
      "label": "Draft"
    },
    
    "pending_manager": {
      "label": "Pending Manager Approval",
      "type": "task",
      "task": {
        "assignee": {
          "role": "manager",
          "department": "{{data.department}}"
        }
      }
    },
    
    "pending_finance": {
      "label": "Pending Finance Committee",
      "type": "task",
      "task": {
        "assignee": {
          "role": "finance_committee"
        },
        "approvalStrategy": {
          "type": "quorum",
          "requiredApprovals": 3,
          "totalApprovers": 5
        }
      }
    },
    
    "approved": {
      "label": "Approved",
      "onEntry": [
        {
          "type": "plugin",
          "plugin": "accounting_system_create_entry",
          "inputs": {
            "invoice_id": "{{subject.id}}",
            "amount": "{{data.amount}}"
          }
        }
      ]
    }
  },
  
  "transitions": {
    "submit_low": {
      "from": ["draft"],
      "to": "pending_manager",
      "condition": {
        "type": "expression",
        "expression": "data.amount <= 5000"
      }
    },
    
    "submit_high": {
      "from": ["draft"],
      "to": "pending_finance",
      "condition": {
        "type": "expression",
        "expression": "data.amount > 5000"
      }
    },
    
    "approve": {
      "from": ["pending_manager", "pending_finance"],
      "to": "approved"
    }
  }
}
```

**Usage:**

```php
$invoice = Invoice::create([
    'amount' => 12000,
    'vendor_id' => 123,
    'department' => 'Marketing',
]);

$invoice->workflow()->start([
    'amount' => $invoice->amount,
    'vendor_id' => $invoice->vendor_id,
    'department' => $invoice->department,
]);

// Automatically routes to "pending_finance" because amount > 5000
// Creates task for 5 finance committee members
// Requires 3 of 5 to approve (quorum strategy)
```

### Example 3: Purchase Order (Level 3)

**Full automation with SLA, escalation, delegation:**

See `database/workflows/purchase-order.json`:

```json
{
  "id": "purchase-order",
  "label": "Purchase Order Processing",
  "initialState": "draft",
  
  "sla": {
    "duration": "5 business days",
    "warningThreshold": 0.8,
    "onBreach": [
      {
        "type": "plugin",
        "plugin": "email_notification",
        "inputs": {
          "to": "procurement_director@company.com",
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
      "label": "Pending Approval",
      "type": "task",
      "task": {
        "assignee": {
          "role": "procurement_manager"
        }
      },
      
      "automation": {
        "escalation": [
          {
            "after": "24 hours",
            "action": "notify",
            "plugin": "email_notification",
            "inputs": {
              "subject": "REMINDER: PO awaiting approval"
            }
          },
          {
            "after": "48 hours",
            "action": "reassign",
            "to": { "role": "procurement_director" },
            "notify": true,
            "reason": "Escalated due to 48-hour inactivity"
          },
          {
            "after": "72 hours",
            "action": "reassign",
            "to": { "role": "vp_operations" },
            "notify": true,
            "reason": "Critical escalation"
          }
        ],
        
        "reminders": [
          {
            "every": "12 hours",
            "until": "task_completed"
          }
        ]
      }
    },
    
    "approved": {
      "label": "Approved",
      "onEntry": [
        {
          "type": "plugin",
          "plugin": "erp_create_purchase_order",
          "inputs": {
            "vendor_id": "{{data.vendor_id}}",
            "amount": "{{data.amount}}"
          },
          "compensation": {
            "plugin": "erp_cancel_purchase_order",
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
      "from": ["draft"],
      "to": "pending_approval"
    },
    "approve": {
      "from": ["pending_approval"],
      "to": "approved"
    }
  }
}
```

---

## 🔧 Configuration

```php
// config/workflow.php
return [
    // Storage: 'database', 'redis', 'memory'
    'storage' => env('WORKFLOW_STORAGE', 'database'),
    
    // Optional Nexus package integration (auto-detected)
    'integrations' => [
        'tenancy' => true,       // Use nexus-tenancy if installed
        'audit_log' => true,     // Use nexus-audit-log if installed
        'notification' => true,  // Use nexus-notification if installed
    ],
    
    // Escalation & SLA
    'automation' => [
        'escalation_check_interval' => 15,  // minutes
        'sla_check_interval' => 15,          // minutes
        'business_hours' => [
            'timezone' => 'America/New_York',
            'workdays' => [1, 2, 3, 4, 5],   // Monday-Friday
            'start_time' => '09:00',
            'end_time' => '17:00',
        ],
    ],
    
    // Delegation
    'delegation' => [
        'max_chain_length' => 3,
    ],
    
    // Plugin discovery
    'plugins' => [
        'auto_discover' => true,
        'paths' => [
            app_path('Workflows/Activities'),
            app_path('Workflows/Conditions'),
            app_path('Workflows/Strategies'),
        ],
    ],
];
```

---

## 🧪 Testing

```bash
vendor/bin/pest
```

**Write tests for your workflows:**

```php
test('invoice approval workflow', function () {
    $invoice = Invoice::factory()->create(['amount' => 12000]);
    
    $invoice->workflow()->start(['amount' => $invoice->amount]);
    
    expect($invoice->workflow()->currentState())->toBe('pending_finance');
    
    $task = WorkflowInbox::forRole('finance_committee')->first();
    
    $task->approve(comment: 'Approved by Finance 1');
    $task->approve(comment: 'Approved by Finance 2');
    $task->approve(comment: 'Approved by Finance 3');  // Quorum reached
    
    expect($invoice->fresh()->workflow()->currentState())->toBe('approved');
});
```

---

## 📚 Documentation

- **[Full Requirements](./docs/REQUIREMENTS-V3.md)** - Complete technical specification
- **[API Reference](./docs/API.md)** - Method signatures and parameters
- **[JSON Schema](./docs/SCHEMA.md)** - Workflow definition format
- **[Extensibility Guide](./docs/EXTENSIBILITY.md)** - Building custom plugins
- **[Migration Guide](./docs/MIGRATION.md)** - Upgrading from other packages

---

## 🤝 Contributing

We welcome contributions! See [CONTRIBUTING.md](./CONTRIBUTING.md).

---

## 📄 License

MIT License. See [LICENSE](./LICENSE).

---

## 🎯 Success Metrics

**Target for 6 months:**
- ✅ 2,000+ installs (mass market validation)
- ✅ 10%+ promotion rate to Level 2 (workflow adoption)
- ✅ 5%+ using Level 3 features (ERP validation)
- ✅ < 5 critical bugs (reliability)
- ✅ < 5 minute time-to-hello-world (developer experience)

---

## 🙏 Credits

Built with ❤️ by the Nexus ERP team.

Inspired by:
- BPMN 2.0 best practices (without the XML complexity)
- Laravel's eloquent API design
- The progressive disclosure philosophy

**Not just another workflow package. The only workflow package you'll ever need.**

From your first blog post to your last purchase order. **Progressive. Powerful. PHP.**
