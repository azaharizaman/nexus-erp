# Nexus CRM Package Requirements & Implementation Strategy

**Version:** 1.0.0  
**Last Updated:** November 16, 2025  
**Status:** Ready for Implementation  
**Package:** `nexus-crm`  
**Namespace:** `Nexus\Crm`

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Core Philosophy & Architectural Alignment](#core-philosophy--architectural-alignment)
3. [Personas & User Stories](#personas--user-stories)
4. [Functional Requirements](#functional-requirements)
5. [Non-Functional Requirements](#non-functional-requirements)
6. [Business Rules](#business-rules)
7. [Data Requirements](#data-requirements)
8. [JSON Schema Specification](#json-schema-specification)
9. [Complete Package Structure](#complete-package-structure)
10. [Requirements-to-Structure Mapping](#requirements-to-structure-mapping)
11. [Requirement to Package Component Mapping](#requirement-to-package-component-mapping)
12. [Implementation by Progressive Levels](#implementation-by-progressive-levels)
13. [Integration with Nexus ERP Ecosystem](#integration-with-nexus-erp-ecosystem)
13. [Success Metrics](#success-metrics)
14. [Development Phases](#development-phases)
15. [Testing Requirements](#testing-requirements)
16. [Dependencies](#dependencies)
17. [Glossary](#glossary)

---

## Executive Summary

**nexus-crm** is a progressive, headless CRM engine for Laravel that scales from basic contact tracking to enterprise customer relationship management. Built on the Nexus ERP **Maximum Atomicity** principle, it provides a completely independent, testable, and reusable customer management foundation that integrates seamlessly with the Nexus ERP ecosystem.

### The Problem We Solve

CRM packages typically force organizations into difficult trade-offs:

- **Simple tools** (like basic contact managers) lack the depth required for growth and scaling
- **Enterprise systems** (Salesforce, HubSpot, Microsoft Dynamics) are complex, costly, and create vendor lock-in
- **Tight coupling** to specific frameworks makes CRM logic difficult to reuse and test

### Our Solution: Progressive Disclosure Architecture

We solve these challenges through a **progressive disclosure model** that scales with your needs:

| Level | Time to Setup | Capability | Database Required |
|-------|--------------|------------|------------------|
| **Level 1** | 5 minutes | Basic CRM - Add `HasCrm` trait, manage contacts in-app | No (model attributes) |
| **Level 2** | 1 hour | Sales Automation - Database-driven leads, opportunities, pipelines, campaigns | Yes (dedicated tables) |
| **Level 3** | Production-ready | Enterprise CRM - AI insights, automation, integrations, compliance (GDPR) | Yes (advanced features) |

---

## Core Philosophy & Architectural Alignment

The nexus-crm package embodies core Nexus ERP architectural principles:

| Principle | Implementation in nexus-crm |
|-----------|---------------------------|
| **Maximum Atomicity** | Complete CRM domain in single package; zero dependencies on other Nexus packages |
| **Independent Testability** | Entire package testable in isolation with only Laravel framework dependencies |
| **Progressive Disclosure** | Start simple (Level 1), grow into complexity as needed (Level 2/3) |
| **Backwards Compatibility** | Level 1 code continues working after upgrading to Level 2/3 |
| **Headless Backend** | Pure service-level API; NO HTTP controllers, web interfaces, or frontend dependencies |
| **Framework Agnostic Core** | Core business logic (`src/Core/`) has zero Laravel dependencies |
| **Laravel Adapter Pattern** | Laravel-specific integrations isolated in `src/Laravel/` (models, migrations, commands) |
| **Nexus Adapter Pattern** | Nexus-specific adapter code (ERP orchestration) lives in `nexus-erp` not the atomic package |
| **Contract-Driven Design** | All integrations via contracts; can swap implementations (e.g., email providers) |
| **Event-Driven Architecture** | Emits domain events for cross-package communication (e.g., `LeadCreatedEvent`) |
| **Extensible by Design** | Plugin system for custom fields, integrations, automations, and conditions |

### Why This Approach Wins

**For Mass Market Users (80%):**
- Quick setup - get started in minutes
- No database overhead for basic usage
- Easy learning curve - progressive complexity
- Fits naturally with existing Laravel models

**For Enterprise Users (20%):**
- Lead scoring and qualification pipelines
- Rich integrations (email, webhooks, API)
- Advanced analytics and reporting
- GDPR compliance and data governance
- Scalable data handling for millions of records

---

## Personas & User Stories

### Personas

| ID | Persona | Role | Primary Goal |
|----|---------|------|--------------|
| **P1** | Mass Market Developer | Full-stack developer at startup | "Add contact management to my User model in 5 minutes" |
| **P2** | In-House CRM Developer | Backend developer at mid-size firm | "Build lead-to-sale pipeline integrated with existing data" |
| **P3** | End-User (Sales Rep/Manager) | Business user | "Track leads, opportunities, interactions in one place" |
| **P4** | System Administrator | IT/DevOps | "Configure custom fields, integrations without code changes" |

### User Stories

#### Level 1: Basic CRM (Mass Appeal)

| ID | Persona | User Story | Priority |
|----|---------|-----------|----------|
| **US-001** | P1 | As a developer, I want to add the `HasCrm` trait to my model to manage contacts without migrations | High |
| **US-002** | P1 | As a developer, I want to define contact fields as an array in my model without external dependencies | High |
| **US-003** | P1 | As a developer, I want to call `$model->crm()->addContact($data)` to create a new contact | High |
| **US-004** | P1 | As a developer, I want to call `$model->crm()->can('edit')` to check permissions declaratively | High |
| **US-005** | P1 | As a developer, I want to call `$model->crm()->history()` to view audit logs | Medium |

#### Level 2: Sales Automation

| ID | Persona | User Story | Priority |
|----|---------|-----------|----------|
| **US-010** | P2 | As a developer, I want to promote to database-driven CRM without changing Level 1 code | High |
| **US-011** | P2 | As a developer, I want to define leads and opportunities with customizable stages | High |
| **US-012** | P2 | As a developer, I want to use conditional pipelines (e.g., if score > 50, promote to qualified) | High |
| **US-013** | P2 | As a developer, I want to run parallel campaigns (email + phone calls simultaneously) | High |
| **US-014** | P2 | As a developer, I want multi-user assignments with approval strategies (unison, majority, quorum) | High |
| **US-015** | P3 | As a sales manager, I want a unified dashboard showing all pending leads and opportunities | High |
| **US-016** | P3 | As a sales rep, I want to log interactions with notes and file attachments | High |

#### Level 3: Enterprise CRM

| ID | Persona | User Story | Priority |
|----|---------|-----------|----------|
| **US-020** | P2 | As a sales manager, I want stale leads to auto-escalate after a configured time period | High |
| **US-021** | P2 | As a sales manager, I want SLA tracking for lead response times with breach notifications | High |
| **US-022** | P3 | As a sales rep, I want to delegate my leads to a colleague during vacation with auto-routing | High |
| **US-023** | P2 | As a developer, I want to rollback failed campaigns with compensation logic | Medium |
| **US-024** | P4 | As a system admin, I want to configure custom fields through an admin interface | Medium |
| **US-025** | P2 | As a sales manager, I want conversion rate reports by stage, user, and time period | Medium |

---

## Functional Requirements

### FR-L1: Level 1 - Basic CRM (Mass Appeal)

| ID | Requirement | Priority | Acceptance Criteria |
|----|------------|----------|-------------------|
| **FR-L1-001** | HasCrm trait for models | High | Add trait to any model; define `crm()` method returning array config; no migrations required; works instantly |
| **FR-L1-002** | In-model contact definitions | High | Define fields as array; store in JSON model column; no external tables or dependencies |
| **FR-L1-003** | `crm()->addContact($data)` method | High | Create contact; emit `ContactCreatedEvent`; validate data; run in transaction |
| **FR-L1-004** | `crm()->can($action)` method | High | Return boolean permission check; guard conditions evaluated; no side effects |
| **FR-L1-005** | `crm()->history()` method | Medium | Return collection of changes; include timestamps, actors, before/after values |
| **FR-L1-006** | Guard conditions on actions | Medium | Accept callable; e.g., `fn($contact) => $contact->status == 'active'`; evaluated before action |
| **FR-L1-007** | Hooks (before/after) | Medium | Register callbacks; e.g., notify after contact added; chainable |

### FR-L2: Level 2 - Sales Automation

| ID | Requirement | Priority | Acceptance Criteria |
|----|------------|----------|-------------------|
| **FR-L2-001** | Database-driven CRM definitions (JSON) | High | Table `crm_definitions` for schemas; same API as Level 1; override in-model config; hot-reload without code changes |
| **FR-L2-002** | Lead/Opportunity stages | High | Define entity type: "lead", "opportunity"; assign to users/roles; pause until user action |
| **FR-L2-003** | Conditional pipelines | High | Support expressions: `==`, `>`, `<`, `AND`, `OR`; access to `data.score`, `data.status`, etc. |
| **FR-L2-004** | Parallel campaigns | High | Define array of actions; execute simultaneously; wait for all to complete before proceeding |
| **FR-L2-005** | Inclusive gateways | Medium | Multiple conditions can be true; execute all true paths; synchronize at join point |
| **FR-L2-006** | Multi-user assignment strategies | High | Built-in strategies: unison (all approve), majority (>50%), quorum (custom threshold); extensible via contract |
| **FR-L2-007** | Dashboard API/Service | High | `CrmDashboard::forUser($id)->pending()` returns pending items; support filter/sort; paginated |
| **FR-L2-008** | Actions (convert, close, etc.) | High | Validate transition; log activity; support comments/attachments; trigger next stage automatically |
| **FR-L2-009** | Data validation | Medium | Schema validation in JSON definition; types: string, number, date, boolean, array; required/optional |
| **FR-L2-010** | Plugin integrations | High | Asynchronous execution; built-in: email, webhook; extensible via `IntegrationContract` |

### FR-L3: Level 3 - Enterprise CRM

| ID | Requirement | Priority | Acceptance Criteria |
|----|------------|----------|-------------------|
| **FR-L3-001** | Escalation rules | High | Trigger after configurable time; notify/reassign; record escalation history; scheduled execution |
| **FR-L3-002** | SLA tracking | High | Track duration from start; define breach actions; status: on_track, at_risk, breached |
| **FR-L3-003** | Delegation with date ranges | High | Table: delegator, delegatee, start_date, end_date; auto-route during delegation; max depth: 3 levels |
| **FR-L3-004** | Rollback logic | Medium | Compensation activities on failure; execute in reverse order; restore previous state |
| **FR-L3-005** | Custom fields configuration | Medium | Define in database; validated on entity creation; optional admin UI via Nexus ERP Core |
| **FR-L3-006** | Timer system | High | Table `crm_timers`; indexed `trigger_at`; workers poll and process; NOT cron-based |

### FR-EXT: Extensibility

| ID | Requirement | Priority | Acceptance Criteria |
|----|------------|----------|-------------------|
| **FR-EXT-001** | Custom integrations | High | Implement `IntegrationContract`: `execute()`, `compensate()` methods |
| **FR-EXT-002** | Custom conditions | High | Implement `ConditionEvaluatorContract`: `evaluate($context)` method; return boolean |
| **FR-EXT-003** | Custom strategies | High | Implement `ApprovalStrategyContract`: `canProceed($responses)` method |
| **FR-EXT-004** | Custom triggers | Medium | Implement `TriggerContract`: webhook, event-based, schedule-based |
| **FR-EXT-005** | Custom storage | Low | Implement `StorageContract`: support Eloquent (default), Redis, custom backends |

---

## Non-Functional Requirements

### Performance Requirements

| ID | Requirement | Target | Notes |
|----|------------|--------|-------|
| **PR-001** | Action execution time | < 100ms | Excluding async operations (emails, webhooks) |
| **PR-002** | Dashboard query (1,000 items) | < 500ms | With proper database indexing |
| **PR-003** | SLA check (10,000 active) | < 2s | Using timers table with indexed `trigger_at` |
| **PR-004** | CRM initialization | < 200ms | Including validation and schema loading |
| **PR-005** | Parallel gateway synchronization (10 branches) | < 100ms | Token-based coordination |

### Security Requirements

| ID | Requirement | Scope |
|----|------------|-------|
| **SR-001** | Unauthorized action prevention | Engine level - guard conditions evaluated before any state change |
| **SR-002** | Expression sanitization | Prevent code injection in conditional expressions |
| **SR-003** | Tenant isolation | Auto-scope all queries to current tenant (via `nexus-tenancy` integration) |
| **SR-004** | Plugin sandboxing | Prevent malicious plugin code execution; validate before registration |
| **SR-005** | Audit change tracking | Immutable audit log for all CRM entity changes |
| **SR-006** | RBAC integration | Permission checks via `nexus-identity-management` (if available) or Laravel gates |

### Reliability Requirements

| ID | Requirement | Notes |
|----|------------|-------|
| **REL-001** | ACID guarantees for state changes | All transitions wrapped in database transactions |
| **REL-002** | Failed integrations don't block progress | Queue async operations; retry with exponential backoff |
| **REL-003** | Concurrency control | Optimistic locking to prevent race conditions |
| **REL-004** | Data corruption protection | Schema validation before persistence |
| **REL-005** | Retry failed transient operations | Configurable retry policy with dead letter queue |

### Scalability Requirements

| ID | Requirement | Notes |
|----|------------|-------|
| **SCL-001** | Asynchronous integrations | Queue-based execution for email, webhooks, external API calls |
| **SCL-002** | Horizontal timer scaling | Multiple workers can process timers concurrently without conflicts |
| **SCL-003** | Efficient query performance | Proper indexes on `status`, `user_id`, `trigger_at`, `tenant_id` |
| **SCL-004** | Support 100,000+ active instances | Optimized queries and caching for large-scale deployments |

### Maintainability Requirements

| ID | Requirement | Notes |
|----|------------|-------|
| **MAINT-001** | Framework-agnostic core | No Laravel dependencies in `src/Core/` directory; Laravel dependencies permitted in `src/Laravel/` (models, commands, services) as per architectural guidelines |
| **MAINT-002** | Laravel adapter pattern | Framework-specific code in `src/Adapters/Laravel/` |
| **MAINT-003** | Orchestration policy | Atomic packages MUST NOT depend on `lorisleiva/laravel-actions`. Orchestration (multi-entrypoint actions) belongs in `nexus/erp` where `laravel-actions` may be used; in-package service classes should remain framework-agnostic and testable. |
| **MAINT-003** | Test coverage | >80% overall, >90% for core business logic |
| **MAINT-004** | Domain separation | Lead, opportunity, campaign logic independent and separately testable |

---

## Business Rules

| ID | Rule | Level |
|----|------|-------|
| **BR-001** | Users cannot self-assign leads (configurable) | Level 2 |
| **BR-002** | All state changes must be ACID-compliant | All Levels |
| **BR-003** | Stale leads auto-escalate after configured timeout | Level 3 |
| **BR-004** | Compensation activities execute in reverse order | Level 3 |
| **BR-005** | Delegation chain maximum depth: 3 levels | Level 3 |
| **BR-006** | Level 1 code remains compatible after Level 2/3 upgrade | All Levels |
| **BR-007** | One CRM instance per subject model | All Levels |
| **BR-008** | Parallel branches must all complete before proceeding | Level 2 |
| **BR-009** | Assignment checks delegation chain first | Level 3 |
| **BR-010** | Multi-user tasks resolved per configured strategy | Level 2 |

---

## Data Requirements

### Core CRM Tables

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| `crm_definitions` | JSON schemas for CRM workflows | `id`, `name`, `schema` (JSON), `active`, `version`, `created_at` |
| `crm_instances` | Running CRM processes | `id`, `subject_type`, `subject_id`, `definition_id`, `state`, `data` (JSON), `started_at`, `ended_at` |
| `crm_history` | Audit trail for all changes | `id`, `instance_id`, `event`, `before` (JSON), `after` (JSON), `actor_id`, `payload` (JSON), `created_at` |

### Entity Tables

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| `crm_contacts` | Contacts and leads | `id`, `instance_id`, `name`, `email`, `phone`, `status`, `score`, `tenant_id` |
| `crm_opportunities` | Sales deals | `id`, `contact_id`, `stage`, `value`, `currency`, `close_date`, `probability`, `tenant_id` |
| `crm_campaigns` | Marketing campaigns | `id`, `name`, `type`, `start_date`, `end_date`, `metrics` (JSON), `tenant_id` |

### Automation Tables

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| `crm_timers` | Scheduled events | `id`, `instance_id`, `type`, `trigger_at`, `payload` (JSON), `processed_at` |
| `crm_sla` | SLA tracking metrics | `id`, `instance_id`, `duration_minutes`, `started_at`, `breach_at`, `status` |
| `crm_escalations` | Escalation history | `id`, `entity_id`, `level`, `from_user_id`, `to_user_id`, `reason`, `escalated_at` |

**Database Portability:** All tables designed to work with MySQL 8+, PostgreSQL 18+, SQLite 3.35+, SQL Server (as per Nexus ERP standards). However PostgreSQL is the recommended production database for advanced features such as JSONB support and advanced indexing.

---

## JSON Schema Specification

### Level 1: In-Model CRM

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Crm\Traits\HasCrm;

class User extends Model
{
    use HasCrm;
    
    /**
     * Define CRM configuration for this model
     *
     * @return array<string, mixed>
     */
    public function crm(): array
    {
        return [
            'entities' => [
                'contact' => [
                    'fields' => ['name', 'email', 'phone', 'company'],
                ],
            ],
        ];
    }
}

// Usage
$user = User::find(1);
$user->crm()->addContact([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '+1-555-0100',
    'company' => 'Acme Corp',
]);

$contacts = $user->crm()->getContacts();
$history = $user->crm()->history();
```

### Level 2: Database-Driven CRM with Entities

```json
{
  "id": "sales-pipeline",
  "label": "Sales Pipeline CRM",
  "version": "1.0.0",
  "dataSchema": {
    "lead_score": { 
      "type": "number",
      "min": 0,
      "max": 100
    },
    "lead_source": {
      "type": "string",
      "enum": ["website", "referral", "cold_call", "event"]
    }
  },
  "entities": {
    "lead": {
      "stages": ["new", "contacted", "qualified", "opportunity", "closed_won", "closed_lost"]
    },
    "opportunity": {
      "stages": ["proposal", "negotiation", "closed_won", "closed_lost"]
    }
  },
  "transitions": {
    "qualify_lead": {
      "from": "contacted",
      "to": "qualified",
      "condition": "data.lead_score > 50",
      "actions": ["send_qualification_email", "notify_sales_manager"]
    },
    "convert_to_opportunity": {
      "from": "qualified",
      "to": "opportunity",
      "condition": "data.budget_confirmed == true",
      "actions": ["create_opportunity_record"]
    }
  }
}
```

### Level 3: Enterprise CRM with Automation

```json
{
  "id": "enterprise-crm",
  "label": "Enterprise CRM with SLA",
  "version": "1.0.0",
  "sla": {
    "duration": "2 days",
    "breach_actions": ["escalate_to_manager", "send_sla_breach_notification"]
  },
  "entities": {
    "lead": {
      "stages": ["new", "contacted", "qualified"],
      "automation": {
        "escalation": [
          {
            "after": "24 hours",
            "condition": "stage == 'new'",
            "action": "reassign_to_next_available"
          },
          {
            "after": "48 hours",
            "condition": "stage == 'contacted'",
            "action": "escalate_to_senior_manager"
          }
        ],
        "reminders": [
          {
            "before": "4 hours",
            "target": "sla_breach",
            "action": "send_reminder_notification"
          }
        ]
      }
    }
  },
  "integrations": {
    "email": {
      "provider": "sendgrid",
      "templates": {
        "new_lead": "template_001",
        "qualification": "template_002"
      }
    },
    "webhook": {
      "on_lead_created": "https://api.example.com/webhooks/lead-created",
      "on_opportunity_closed": "https://api.example.com/webhooks/opportunity-closed"
    }
  }
}
```

**Built-in Condition Types:**
- `expression` - Simple comparison expressions (==, >, <, >=, <=, !=)
- `role_check` - Verify user has specific role
- `permission_check` - Verify user has specific permission
- `custom` - Custom condition via `ConditionContract` implementation

**Built-in Approval Strategies:**
- `unison` - All assigned users must approve
- `majority` - >50% of assigned users must approve
- `quorum` - Configurable threshold (e.g., 3 out of 5)
- `first` - First response determines outcome
- `custom` - Custom strategy via `StrategyContract` implementation

---

## Complete Package Structure

The following structure provides a **production-ready, standalone composer package** that can be integrated into any PHP/Laravel project while maintaining the core **maximum atomicity** and **progressive disclosure** principles.

```
nexus-crm/
├── composer.json                    # Package metadata, autoloading, dependencies
├── README.md                        # Package overview and quick start
├── LICENSE                          # MIT/Apache license
├── CHANGELOG.md                     # Version history
├── SECURITY.md                      # Security policy
├── .gitignore                       # Git ignore rules
├── .editorconfig                    # Code style consistency
├── phpunit.xml                      # Test configuration
├── phpstan.neon                     # Static analysis configuration
├── rector.php                       # Automated refactoring rules
├── .php-cs-fixer.php               # Code formatting rules
├── Makefile                        # Common development tasks
│
├── src/                            # Main source code (PSR-4 autoloaded)
│   ├── Contracts/                  # Public API contracts
│   │   ├── Engine/
│   │   │   ├── CrmEngineInterface.php
│   │   │   ├── EntityManagerInterface.php
│   │   │   ├── TransitionManagerInterface.php
│   │   │   └── StateManagerInterface.php
│   │   ├── Integrations/
│   │   │   ├── IntegrationInterface.php
│   │   │   ├── ConditionEvaluatorInterface.php
│   │   │   ├── ApprovalStrategyInterface.php
│   │   │   ├── TimerInterface.php
│   │   │   └── StorageInterface.php
│   │   ├── Services/
│   │   │   ├── LeadServiceInterface.php
│   │   │   ├── OpportunityServiceInterface.php
│   │   │   ├── CampaignServiceInterface.php
│   │   │   └── DashboardServiceInterface.php
│   │   └── Events/
│   │       ├── DomainEventInterface.php
│   │       ├── LeadCreatedEventInterface.php
│   │       ├── LeadQualifiedEventInterface.php
│   │       ├── OpportunityCreatedEventInterface.php
│   │       └── SlaBreachedEventInterface.php
│   │
│   ├── Core/                       # Framework-agnostic business logic
│   │   ├── Engine/                 # Core state machine engine
│   │   │   ├── CrmEngine.php       # Main CRM orchestration engine
│   │   │   ├── EntityManager.php   # Entity lifecycle management
│   │   │   ├── TransitionManager.php # State transition logic
│   │   │   ├── StateManager.php    # State persistence logic
│   │   │   └── AutomationOrchestrator.php # Automation workflows
│   │   ├── Services/               # Domain services (framework-agnostic)
│   │   │   ├── LeadService.php     # Lead management service
│   │   │   ├── OpportunityService.php # Opportunity management
│   │   │   ├── CampaignService.php # Campaign management
│   │   │   ├── EscalationService.php # Escalation logic
│   │   │   ├── SlaService.php      # SLA tracking service
│   │   │   ├── TimerService.php    # Timer management
│   │   │   ├── IntegrationService.php # Integration management
│   │   │   └── DashboardService.php # Analytics dashboard
│   │   ├── Automations/            # Workflow automation
│   │   │   ├── PipelineBuilder.php # CRM pipeline creation
│   │   │   ├── GatewayCoordinator.php # Decision point coordinator
│   │   │   └── CampaignCoordinator.php # Campaign execution
│   │   ├── Rules/                  # Business rule engines
│   │   │   ├── GuardRule.php       # Action permission rules
│   │   │   ├── EscalationRule.php  # Escalation logic
│   │   │   ├── SlaRule.php         # SLA violation rules
│   │   │   ├── DelegationRule.php  # Assignment delegation
│   │   │   └── CompensationRule.php # Rollback logic
│   │   ├── Strategies/             # Approval decision strategies
│   │   │   ├── ApprovalStrategy.php # Base strategy interface
│   │   │   ├── UnisonStrategy.php  # All must approve
│   │   │   ├── MajorityStrategy.php # >50% approval
│   │   │   ├── QuorumStrategy.php  # Custom threshold
│   │   │   └── FirstResponseStrategy.php # First response wins
│   │   ├── Conditions/             # Conditional logic evaluators
│   │   │   ├── ConditionEvaluator.php # Base condition evaluator
│   │   │   ├── ExpressionCondition.php # Expression parser/evaluator
│   │   │   ├── RoleCheckCondition.php # Role-based conditions
│   │   │   ├── PermissionCheckCondition.php # Permission checks
│   │   │   └── CustomCondition.php # Custom business logic
│   │   ├── Integrations/           # External system integrations
│   │   │   ├── IntegrationManager.php # Integration registry
│   │   │   ├── EmailIntegration.php # Email provider integration
│   │   │   ├── WebhookIntegration.php # Webhook integration
│   │   │   ├── SlackIntegration.php # Slack notifications
│   │   │   └── CustomIntegration.php # Custom integration base
│   │   ├── Timers/                 # Time-based automation
│   │   │   ├── TimerQueue.php      # Timer scheduling queue
│   │   │   ├── TimerScheduler.php  # Timer scheduling logic
│   │   │   └── TimerProcessor.php  # Timer execution
│   │   ├── DTOs/                   # Data transfer objects
│   │   │   ├── CrmDefinition.php   # CRM workflow definition
│   │   │   ├── CrmInstance.php     # Running CRM instance
│   │   │   ├── Contact.php         # Contact entity DTO
│   │   │   ├── Lead.php            # Lead entity DTO
│   │   │   ├── Opportunity.php     # Opportunity entity DTO
│   │   │   ├── Campaign.php        # Campaign entity DTO
│   │   │   ├── Transition.php      # State transition DTO
│   │   │   └── Escalation.php      # Escalation event DTO
│   │   ├── Events/                 # Domain events (framework-agnostic)
│   │   │   ├── AbstractDomainEvent.php # Base event class
│   │   │   ├── LeadCreatedEvent.php # Lead created event
│   │   │   ├── LeadQualifiedEvent.php # Lead qualified event
│   │   │   ├── OpportunityCreatedEvent.php # Opportunity created
│   │   │   ├── OpportunityClosedEvent.php # Opportunity closed
│   │   │   ├── CampaignStartedEvent.php # Campaign started
│   │   │   ├── SlaBreachedEvent.php # SLA breach event
│   │   │   └── EscalationTriggeredEvent.php # Escalation event
│   │   └── Exceptions/             # Domain-specific exceptions
│   │       ├── CrmException.php    # Base CRM exception
│   │       ├── InvalidTransitionException.php # Invalid state transition
│   │       ├── SlaBreachException.php # SLA violation
│   │       ├── EscalationException.php # Escalation error
│   │       ├── IntegrationException.php # Integration failure
│   │       └── ConfigurationException.php # Configuration error
│   │
│   ├── Laravel/                    # Laravel framework adapter
│   │   ├── Traits/                 # Laravel-specific traits
│   │   │   ├── HasCrm.php          # Level 1: Trait for models
│   │   │   ├── HasContacts.php     # Contact management trait
│   │   │   └── HasLeads.php        # Lead management trait
│   │   ├── Models/                 # Eloquent ORM models
│   │   │   ├── CrmDefinition.php   # CRM workflow definition
│   │   │   ├── CrmInstance.php     # Running CRM instance
│   │   │   ├── CrmContact.php      # Contact model
│   │   │   ├── CrmLead.php         # Lead model
│   │   │   ├── CrmOpportunity.php  # Opportunity model
│   │   │   ├── CrmCampaign.php     # Campaign model
│   │   │   ├── CrmTimer.php        # Timer model
│   │   │   ├── CrmSla.php          # SLA tracking model
│   │   │   └── CrmEscalation.php   # Escalation model
│   │   ├── Services/               # Laravel-specific services
│   │   │   ├── CrmServiceProvider.php # Package service provider
│   │   │   ├── EloquentStorage.php # Eloquent-based storage
│   │   │   ├── LaravelEventDispatcher.php # Event dispatcher
│   │   │   ├── TimerProcessorService.php # Laravel timer processor
│   │   │   ├── EscalationProcessorService.php # Escalation processor
│   │   │   └── DashboardService.php # Laravel dashboard service
│   │   ├── Commands/               # Artisan console commands
│   │   │   ├── ProcessTimersCommand.php # Process scheduled timers
│   │   │   ├── ProcessEscalationsCommand.php # Process escalations
│   │   │   ├── CrmInstallCommand.php # Install CRM package
│   │   │   └── CrmStatusCommand.php # Show CRM status
│   │   ├── Notifications/          # Laravel notifications
│   │   │   ├── EscalationNotification.php # Escalation notification
│   │   │   ├── SlaBreachNotification.php # SLA breach notification
│   │   │   ├── LeadAssignmentNotification.php # Lead assignment
│   │   │   └── OpportunityClosedNotification.php # Opportunity closure
│   │   ├── Facades/                # Laravel facades
│   │   │   ├── CrmFacade.php       # Main CRM facade
│   │   │   ├── LeadFacade.php      # Lead management facade
│   │   │   ├── OpportunityFacade.php # Opportunity facade
│   │   │   └── DashboardFacade.php # Dashboard facade
│   │   └── Jobs/                   # Laravel queue jobs
│   │       ├── ProcessTimerJob.php # Process scheduled timer
│   │       ├── ProcessEscalationJob.php # Process escalation
│   │       ├── SendEmailJob.php # Send notification email
│   │       └── WebhookCallJob.php # Call webhook URL
│   │
│   └── Utils/                      # Shared utilities
│       ├── ExpressionParser.php    # Parse conditional expressions
│       ├── DateUtils.php          # Date/time utilities
│       ├── ValidationUtils.php    # Validation helpers
│       └── SerializationUtils.php # DTO serialization
│
├── config/                         # Configuration files (publishable)
│   ├── crm.php                    # Main CRM configuration
│   ├── integrations.php           # Integration configurations
│   ├── notifications.php          # Notification settings
│   ├── scheduler.php              # Timer/scheduler settings
│   ├── security.php               # Security and permissions
│   └── performance.php            # Performance tuning
│
├── database/                       # Database schema and seeders
│   ├── migrations/                # Laravel migrations
│   │   ├── 2025_11_16_000001_create_crm_definitions_table.php
│   │   ├── 2025_11_16_000002_create_crm_instances_table.php
│   │   ├── 2025_11_16_000003_create_crm_history_table.php
│   │   ├── 2025_11_16_000004_create_crm_contacts_table.php
│   │   ├── 2025_11_16_000005_create_crm_leads_table.php
│   │   ├── 2025_11_16_000006_create_crm_opportunities_table.php
│   │   ├── 2025_11_16_000007_create_crm_campaigns_table.php
│   │   ├── 2025_11_16_000008_create_crm_timers_table.php
│   │   ├── 2025_11_16_000009_create_crm_sla_table.php
│   │   └── 2025_11_16_000010_create_crm_escalations_table.php
│   ├── factories/                 # Model factories for testing
│   │   ├── CrmDefinitionFactory.php
│   │   ├── CrmContactFactory.php
│   │   ├── CrmLeadFactory.php
│   │   ├── CrmOpportunityFactory.php
│   │   ├── CrmCampaignFactory.php
│   │   ├── CrmTimerFactory.php
│   │   └── CrmEscalationFactory.php
│   └── seeders/                   # Database seeders
│       ├── CrmSeeder.php          # Main CRM seeder
│       ├── SampleDataSeeder.php   # Sample data for testing
│       └── DemoDefinitionSeeder.php # Demo CRM workflows
│
├── resources/                     # Package resources
│   ├── lang/                      # Localization files
│   │   ├── en/                    # English translations
│   │   │   ├── crm.php            # General CRM translations
│   │   │   ├── leads.php          # Lead-related translations
│   │   │   ├── opportunities.php  # Opportunity translations
│   │   │   └── campaigns.php      # Campaign translations
│   │   └── es/                    # Spanish translations (example)
│   │       └── crm.php
│   ├── views/                     # Blade templates (if needed for notifications)
│   │   ├── emails/                # Email templates
│   │   │   ├── escalation.blade.php
│   │   │   ├── sla_breach.blade.php
│   │   │   └── lead_assignment.blade.php
│   │   └── notifications/         # Notification templates
│   │       ├── escalation.php
│   │       └── sla_breach.php
│   ├── definitions/               # JSON workflow definitions
│   │   ├── basic_pipeline.json    # Basic sales pipeline
│   │   ├── enterprise_pipeline.json # Enterprise CRM workflow
│   │   ├── marketing_campaign.json # Marketing automation
│   │   └── customer_support.json  # Support ticket workflow
│   └── examples/                  # Usage examples
│       ├── basic_usage.php        # Level 1 basic usage
│       ├── sales_pipeline.php     # Level 2 database-driven
│       ├── enterprise_usage.php   # Level 3 enterprise features
│       ├── custom_conditions.php  # Custom condition examples
│       └── integrations.php       # Integration examples
│
├── tests/                         # Test suite
│   ├── bootstrap.php              # Test bootstrap
│   ├── Pest.php                   # Pest testing framework setup
│   ├── Unit/                      # Unit tests for core logic
│   │   ├── Core/
│   │   │   ├── Engine/
│   │   │   │   ├── CrmEngineTest.php
│   │   │   │   ├── EntityManagerTest.php
│   │   │   │   └── TransitionManagerTest.php
│   │   │   ├── Services/
│   │   │   │   ├── LeadServiceTest.php
│   │   │   │   ├── OpportunityServiceTest.php
│   │   │   │   └── EscalationServiceTest.php
│   │   │   ├── Rules/
│   │   │   │   ├── GuardRuleTest.php
│   │   │   │   └── EscalationRuleTest.php
│   │   │   ├── Strategies/
│   │   │   │   ├── UnisonStrategyTest.php
│   │   │   │   ├── MajorityStrategyTest.php
│   │   │   │   └── QuorumStrategyTest.php
│   │   │   ├── Conditions/
│   │   │   │   ├── ExpressionConditionTest.php
│   │   │   │   └── RoleCheckConditionTest.php
│   │   │   └── Automations/
│   │   │       ├── PipelineBuilderTest.php
│   │   │       └── GatewayCoordinatorTest.php
│   │   └── DTOs/
│   │       ├── CrmDefinitionTest.php
│   │       ├── LeadTest.php
│   │       └── OpportunityTest.php
│   ├── Feature/                   # Feature tests (Laravel-specific)
│   │   ├── Level1CrmTest.php      # Level 1 trait-based CRM
│   │   ├── Level2AutomationTest.php # Level 2 database-driven CRM
│   │   ├── Level3EnterpriseTest.php # Level 3 enterprise features
│   │   ├── LeadManagementTest.php # Lead CRUD operations
│   │   ├── OpportunityManagementTest.php # Opportunity operations
│   │   ├── CampaignExecutionTest.php # Campaign automation
│   │   └── ServiceIntegrationTest.php   # Service integration tests
│   │   └── NotificationsTest.php # Notification delivery
│   ├── Integration/               # Integration tests
│   │   ├── DatabaseTest.php       # Database operations
│   │   ├── QueueTest.php          # Queue job processing
│   │   ├── EventDispatchingTest.php # Event system
│   │   └── StorageTest.php        # Storage layer tests
│   ├── Stubs/                     # Test fixtures and stubs
│   │   ├── sample_definitions/    # Sample CRM definitions
│   │   ├── mock_responses/        # Mock external responses
│   │   └── test_data/             # Test data fixtures
│   └── Support/                   # Test support classes
│       ├── TestCase.php           # Base test case
│       ├── Mocks/                 # Mock implementations
│       │   ├── MockStorage.php
│       │   ├── MockEventDispatcher.php
│       │   └── MockTimer.php
│       ├── Factories/             # Test-specific factories
│       └── Helpers/               # Test helper methods
│
├── docs/                          # Documentation
│   ├── README.md                  # Main package documentation
│   ├── SERVICE_API.md             # Service-level API reference
│   ├── ARCHITECTURE.md            # Technical architecture
│   ├── QUICKSTART.md              # Quick start guide
│   ├── LEVELS.md                  # Progressive disclosure guide
│   ├── INTEGRATIONS.md            # Integration documentation
│   ├── CUSTOMIZATION.md           # Customization guide
│   ├── TROUBLESHOOTING.md         # Common issues and solutions
│   ├── MIGRATION.md               # Migration from other CRMs
│   ├── DEPLOYMENT.md              # Production deployment guide
│   ├── PERFORMANCE.md             # Performance optimization
│   ├── SECURITY.md                # Security best practices
│   └── EXAMPLES/                  # Code examples
│       ├── basic_setup.md
│       ├── sales_pipeline.md
│       ├── enterprise_features.md
│       └── custom_integrations.md
│
├── stubs/                         # Laravel publishable stubs
│   ├── migration.stub             # Base migration template
│   ├── model.stub                 # Base model template
│   ├── controller.stub            # Base controller template
│   ├── service.stub               # Base service template
│   └── integration.stub           # Custom integration template
│
├── scripts/                       # Development and deployment scripts
│   ├── install.sh                # Package installation script
│   ├── test.sh                   # Comprehensive test runner
│   ├── quality-check.sh          # Code quality checks
│   ├── benchmark.sh              # Performance benchmarking
│   └── release.sh                # Package release script
│
├── .github/                       # GitHub workflows and templates
│   └── workflows/
│       ├── ci.yml                 # Continuous integration
│       ├── tests.yml              # Test execution
│       ├── quality.yml            # Code quality checks
│       └── release.yml            # Release automation
│
├── .vscode/                       # VS Code configuration
│   ├── settings.json
│   ├── extensions.json
│   └── launch.json
│
└── .phpstorm/                     # PhpStorm configuration
    ├── codeStyleSettings.xml
    └── inspections.xml
```

---

## Requirements-to-Structure Mapping

### LEVEL 1 - Basic CRM (Mass Appeal) Requirements

| Requirement | Package Component | Implementation Details |
|-------------|------------------|----------------------|
| **FR-L1-001**: HasCrm trait for models | `src/Laravel/Traits/HasCrm.php` | Trait provides `crm()` method returning array config |
| **FR-L1-002**: In-model contact definitions | `src/Laravel/Traits/HasCrm.php` + `src/Core/DTOs/Contact.php` | Array config parsed and stored in model attributes |
| **FR-L1-003**: `crm()->addContact($data)` method | `src/Laravel/Traits/HasCrm.php` + `src/Core/Services/LeadService.php` | Creates contact, emits `ContactCreatedEvent` |
| **FR-L1-004**: `crm()->can($action)` method | `src/Core/Rules/GuardRule.php` | Permission checking with guard conditions |
| **FR-L1-005**: `crm()->history()` method | `src/Laravel/Models/CrmHistory.php` + `src/Core/Services/DashboardService.php` | Returns collection of audit changes |
| **FR-L1-006**: Guard conditions on actions | `src/Core/Rules/GuardRule.php` + `src/Core/Conditions/` | Callable conditions evaluated before action |
| **FR-L1-007**: Hooks (before/after) | `src/Core/Events/AbstractDomainEvent.php` | Event system for before/after callbacks |

### LEVEL 2 - Sales Automation Requirements

| Requirement | Package Component | Implementation Details |
|-------------|------------------|----------------------|
| **FR-L2-001**: Database-driven CRM definitions | `database/migrations/` + `src/Core/DTOs/CrmDefinition.php` | Table `crm_definitions` with JSON schema |
| **FR-L2-002**: Lead/Opportunity stages | `src/Laravel/Models/CrmLead.php` + `src/Core/DTOs/Lead.php` | Stage assignment with user/role management |
| **FR-L2-003**: Conditional pipelines | `src/Core/Automations/PipelineBuilder.php` + `src/Core/Conditions/ExpressionCondition.php` | Expression parser with `data.score > 50` syntax |
| **FR-L2-004**: Parallel campaigns | `src/Core/Automations/CampaignCoordinator.php` | Array of actions executed simultaneously |
| **FR-L2-005**: Inclusive gateways | `src/Core/Automations/GatewayCoordinator.php` | Multiple conditions can be true |
| **FR-L2-006**: Multi-user assignment strategies | `src/Core/Strategies/` + `src/Contracts/Integrations/ApprovalStrategyInterface.php` | Built-in: unison, majority, quorum strategies |
| **FR-L2-007**: Dashboard API/Service | `src/Laravel/Services/DashboardService.php` | Service-level API for dashboard data; consumed by frontend via Laravel routes |
| **FR-L2-008**: Actions (convert, close, etc.) | `src/Core/Services/LeadService.php` + `src/Core/Engine/TransitionManager.php` | Validate transition, log activity, auto-advance |
| **FR-L2-009**: Data validation | `src/Utils/ValidationUtils.php` | JSON schema validation with types |
| **FR-L2-010**: Plugin integrations | `src/Core/Integrations/IntegrationManager.php` + `src/Contracts/Integrations/IntegrationInterface.php` | Async execution via queue jobs |

### LEVEL 3 - Enterprise CRM Requirements

| Requirement | Package Component | Implementation Details |
|-------------|------------------|----------------------|
| **FR-L3-001**: Escalation rules | `src/Core/Rules/EscalationRule.php` + `src/Core/Services/EscalationService.php` | Timer-based escalation with notifications |
| **FR-L3-002**: SLA tracking | `src/Core/Rules/SlaRule.php` + `src/Laravel/Models/CrmSla.php` | Duration tracking with breach detection |
| **FR-L3-003**: Delegation with date ranges | `src/Core/Rules/DelegationRule.php` | Table with date ranges, max 3-level chain |
| **FR-L3-004**: Rollback logic | `src/Core/Rules/CompensationRule.php` | Reverse execution order with state restoration |
| **FR-L3-005**: Custom fields configuration | `src/Laravel/Models/CrmDefinition.php` | Database-defined fields with validation |
| **FR-L3-006**: Timer system | `src/Core/Timers/` + `src/Laravel/Commands/ProcessTimersCommand.php` | Table `crm_timers` with indexed `trigger_at` |

### Extensibility Requirements

| Requirement | Package Component | Implementation Details |
|-------------|------------------|----------------------|
| **FR-EXT-001**: Custom integrations | `src/Contracts/Integrations/IntegrationInterface.php` + `stubs/integration.stub` | Implement `execute()` and `compensate()` methods |
| **FR-EXT-002**: Custom conditions | `src/Contracts/Integrations/ConditionEvaluatorInterface.php` + `resources/examples/custom_conditions.php` | Implement `evaluate($context)` method |
| **FR-EXT-003**: Custom strategies | `src/Contracts/Integrations/ApprovalStrategyInterface.php` | Implement `canProceed($responses)` method |
| **FR-EXT-004**: Custom triggers | `src/Contracts/Integrations/TimerInterface.php` | Webhook, event-based, schedule-based triggers |
| **FR-EXT-005**: Custom storage | `src/Contracts/Integrations/StorageInterface.php` | Eloquent (default), Redis, custom backends |

### Non-Functional Requirements Mapping

#### Performance Requirements

| Requirement | Package Component | Implementation Details |
|-------------|------------------|----------------------|
| **PR-001**: Action execution <100ms | `src/Core/Engine/CrmEngine.php` | Optimized state machine execution |
| **PR-002**: Dashboard query <500ms | `database/migrations/` + indexes | Indexed queries on `status`, `user_id`, `tenant_id` |
| **PR-003**: SLA check <2s | `src/Core/Timers/TimerQueue.php` | Efficient timer processing with batching |
| **PR-004**: CRM initialization <200ms | `src/Core/DTOs/CrmDefinition.php` | Lazy loading and caching |
| **PR-005**: Parallel gateway sync <100ms | `src/Core/Automations/GatewayCoordinator.php` | Token-based coordination |

#### Security Requirements

| Requirement | Package Component | Implementation Details |
|-------------|------------------|----------------------|
| **SR-001**: Unauthorized action prevention | `src/Core/Rules/GuardRule.php` | Guard conditions evaluated before state changes |
| **SR-002**: Expression sanitization | `src/Utils/ExpressionParser.php` | Safe expression evaluation without code injection |
| **SR-003**: Tenant isolation | `src/Laravel/Middleware/TenantScopeMiddleware.php` | Auto-scope queries to current tenant |
| **SR-004**: Plugin sandboxing | `src/Core/Integrations/IntegrationManager.php` | Validate plugins before registration |
| **SR-005**: Audit change tracking | `src/Laravel/Models/CrmHistory.php` | Immutable audit log for all changes |
| **SR-006**: RBAC integration | `src/Core/Conditions/PermissionCheckCondition.php` | Permission checks via interfaces |

#### Reliability Requirements

| Requirement | Package Component | Implementation Details |
|-------------|------------------|----------------------|
| **REL-001**: ACID guarantees | `src/Laravel/Models/` + transactions | All transitions wrapped in DB transactions |
| **REL-002**: Failed integrations don't block | `src/Laravel/Jobs/` + queue | Async execution with retry policies |
| **REL-003**: Concurrency control | `src/Core/Engine/StateManager.php` | Optimistic locking for race conditions |
| **REL-004**: Data corruption protection | `src/Utils/ValidationUtils.php` | Schema validation before persistence |
| **REL-005**: Retry failed operations | `src/Laravel/Jobs/ProcessTimerJob.php` | Exponential backoff with dead letter queue |

#### Scalability Requirements

| Requirement | Package Component | Implementation Details |
|-------------|------------------|----------------------|
| **SCL-001**: Asynchronous integrations | `src/Laravel/Jobs/` | Queue-based execution for external calls |
| **SCL-002**: Horizontal timer scaling | `src/Core/Timers/TimerQueue.php` | Multiple workers with conflict prevention |
| **SCL-003**: Efficient query performance | `database/migrations/` | Proper indexing on critical columns |
| **SCL-004**: Support 100,000+ instances | `config/performance.php` + caching | Optimized queries and result caching |

#### Maintainability Requirements

| Requirement | Package Component | Implementation Details |
|-------------|------------------|----------------------|
| **MAINT-001**: Framework-agnostic core | `src/Core/` | Zero Laravel dependencies in core logic |
| **MAINT-002**: Laravel adapter pattern | `src/Laravel/` | Framework-specific code isolated |
| **MAINT-003**: Test coverage >80% | `tests/` | Comprehensive unit, feature, integration tests |
| **MAINT-004**: Domain separation | `src/Core/Services/` | Independent, separately testable domains |

---

## Requirement to Package Component Mapping

This section provides a comprehensive mapping of each requirement to its specific package implementation. This serves as the definitive guide for developers implementing the CRM package.

### LEVEL 1 Requirements → Package Files

| Requirement ID | Requirement Description | Package Component File(s) | Lines of Code |
|---------------|------------------------|---------------------------|---------------|
| **FR-L1-001** | HasCrm trait for models | `src/Laravel/Traits/HasCrm.php` | ~60 |
| **FR-L1-002** | In-model contact definitions | `src/Core/DTOs/Contact.php` + `config/crm.php` | ~80 |
| **FR-L1-003** | crm()->addContact($data) method | `src/Laravel/Traits/HasCrm.php` + `src/Core/Services/LeadService.php` | ~40 |
| **FR-L1-004** | crm()->can($action) method | `src/Core/Rules/GuardRule.php` + `src/Core/Conditions/` | ~50 |
| **FR-L1-005** | crm()->history() method | `src/Laravel/Models/CrmHistory.php` + `src/Core/Services/DashboardService.php` | ~35 |
| **FR-L1-006** | Guard conditions on actions | `src/Core/Rules/GuardRule.php` + `src/Core/Conditions/ExpressionCondition.php` | ~45 |
| **FR-L1-007** | Hooks (before/after events) | `src/Core/Events/AbstractDomainEvent.php` + `src/Core/Events/ContactCreatedEvent.php` | ~30 |
| **FR-L1-008** | Basic validation | `src/Utils/ValidationUtils.php` + `src/Core/DTOs/Contact.php` | ~25 |

### LEVEL 2 Requirements → Package Files

| Requirement ID | Requirement Description | Package Component File(s) | Lines of Code |
|---------------|------------------------|---------------------------|---------------|
| **FR-L2-001** | Database-driven CRM definitions | `database/migrations/2025_11_16_000001_create_crm_definitions_table.php` + `src/Core/DTOs/CrmDefinition.php` | ~70 |
| **FR-L2-002** | Lead/Opportunity stages | `src/Laravel/Models/CrmLead.php` + `src/Core/DTOs/Lead.php` + `src/Core/Services/LeadService.php` | ~120 |
| **FR-L2-003** | Conditional pipelines | `src/Core/Automations/PipelineBuilder.php` + `src/Core/Conditions/ExpressionCondition.php` | ~80 |
| **FR-L2-004** | Parallel campaigns | `src/Core/Automations/CampaignCoordinator.php` | ~60 |
| **FR-L2-005** | Inclusive gateways | `src/Core/Automations/GatewayCoordinator.php` | ~55 |
| **FR-L2-006** | Multi-user assignment strategies | `src/Core/Strategies/` + `src/Contracts/Integrations/ApprovalStrategyInterface.php` | ~100 |
| **FR-L2-007** | Dashboard API/Service | `src/Laravel/Services/DashboardService.php` | ~80 |
| **FR-L2-008** | Actions (convert, close, etc.) | `src/Core/Services/LeadService.php` + `src/Core/Engine/TransitionManager.php` | ~90 |
| **FR-L2-009** | Data validation | `src/Utils/ValidationUtils.php` | ~40 |
| **FR-L2-010** | Plugin integrations | `src/Core/Integrations/IntegrationManager.php` + `src/Contracts/Integrations/IntegrationInterface.php` | ~85 |

### LEVEL 3 Requirements → Package Files

| Requirement ID | Requirement Description | Package Component File(s) | Lines of Code |
|---------------|------------------------|---------------------------|---------------|
| **FR-L3-001** | Escalation rules | `src/Core/Rules/EscalationRule.php` + `src/Core/Services/EscalationService.php` | ~70 |
| **FR-L3-002** | SLA tracking | `src/Core/Rules/SlaRule.php` + `src/Laravel/Models/CrmSla.php` | ~60 |
| **FR-L3-003** | Delegation with date ranges | `src/Core/Rules/DelegationRule.php` + `src/Laravel/Models/CrmDelegation.php` | ~55 |
| **FR-L3-004** | Rollback logic | `src/Core/Rules/CompensationRule.php` | ~65 |
| **FR-L3-005** | Custom fields configuration | `src/Laravel/Models/CrmDefinition.php` + `src/Core/DTOs/CustomField.php` | ~50 |
| **FR-L3-006** | Timer system | `src/Core/Timers/` + `src/Laravel/Commands/ProcessTimersCommand.php` | ~90 |

### NON-FUNCTIONAL Requirements → Package Files

| Requirement Category | Requirement Description | Package Component File(s) | Implementation Strategy |
|--------------------|------------------------|---------------------------|------------------------|
| **Performance** | Action execution <100ms | `src/Core/Engine/CrmEngine.php` | Optimized state machine execution with caching |
| **Performance** | Dashboard query <500ms | `database/migrations/` + indexes | Indexed queries on critical columns |
| **Performance** | SLA check <2s | `src/Core/Timers/TimerQueue.php` | Efficient timer processing with batching |
| **Security** | Unauthorized action prevention | `src/Core/Rules/GuardRule.php` | Guard conditions evaluated before state changes |
| **Security** | Expression sanitization | `src/Utils/ExpressionParser.php` | Safe expression evaluation without code injection |
| **Security** | Tenant isolation | `src/Laravel/Middleware/TenantScopeMiddleware.php` | Auto-scope queries to current tenant |
| **Reliability** | ACID guarantees | `src/Laravel/Models/` + transactions | All transitions wrapped in DB transactions |
| **Reliability** | Failed integrations don't block | `src/Laravel/Jobs/` + queue | Async execution with retry policies |
| **Scalability** | Asynchronous integrations | `src/Laravel/Jobs/` | Queue-based execution for external calls |
| **Maintainability** | Framework-agnostic core | `src/Core/` | Zero Laravel dependencies in core logic |
| **Maintainability** | Laravel adapter pattern | `src/Laravel/` | Framework-specific code isolated |

### Extensibility Requirements → Package Files

| Requirement ID | Requirement Description | Package Component File(s) | Implementation Contract |
|---------------|------------------------|---------------------------|------------------------|
| **FR-EXT-001** | Custom integrations | `src/Contracts/Integrations/IntegrationInterface.php` + `stubs/integration.stub` | Implement `execute()` and `compensate()` methods |
| **FR-EXT-002** | Custom conditions | `src/Contracts/Integrations/ConditionEvaluatorInterface.php` + `resources/examples/custom_conditions.php` | Implement `evaluate($context)` method |
| **FR-EXT-003** | Custom strategies | `src/Contracts/Integrations/ApprovalStrategyInterface.php` | Implement `canProceed($responses)` method |
| **FR-EXT-004** | Custom triggers | `src/Contracts/Integrations/TimerInterface.php` | Webhook, event-based, schedule-based triggers |
| **FR-EXT-005** | Custom storage | `src/Contracts/Integrations/StorageInterface.php` | Eloquent (default), Redis, custom backends |

### File Organization Summary by Package Section

| Package Section | Primary Responsibilities | Key Files |
|----------------|--------------------------|-----------|
| **src/Core/** | Framework-agnostic business logic, state machines, automations | Engine/, Services/, Rules/, Strategies/, Conditions/, Automations/ |
| **src/Contracts/** | Public API contracts for extensibility | All `*Interface.php` files |
| **src/Laravel/** | Laravel-specific adapters (models, migrations, commands, services) | Traits/, Models/, Services/, Commands/ |
| **database/** | Database schema and migrations | All migration files |
| **config/** | Package configuration | crm.php, performance.php |
| **resources/** | Examples, stubs, templates | examples/, stubs/ |
| **tests/** | Comprehensive test suite | Unit, Feature, Integration tests |

### Critical Implementation Notes

1. **Headless Architecture**: NO HTTP controllers, web views, or frontend dependencies
2. **Service-Level APIs Only**: All interactions via service classes and contracts
3. **Laravel Integration**: Models, migrations, commands, and facades only
4. **Progressive Disclosure**: Each level adds complexity without breaking previous levels
5. **Contract-Driven**: All extensibility via interfaces, not inheritance

### HTTP Layer Decision & Rationale

**❌ REMOVED: HTTP Controllers from Laravel Adapter**

The initial package structure incorrectly included HTTP controllers (`src/Laravel/Http/Controllers/`) which violates the core **Headless Backend** principle. 

**Why HTTP Controllers Were Removed:**
- **Headless Principle Violation**: The package explicitly states "Pure service-level API; NO HTTP controllers, web interfaces, or frontend dependencies"
- **Framework Coupling**: HTTP controllers create tight coupling to Laravel's HTTP stack
- **Consumer Freedom**: Frontend developers should build their own APIs using the service classes
- **Maintainability**: Service-level APIs are more maintainable and testable than controller-based APIs

**✅ CORRECT APPROACH: Service-Level APIs Only**

| Component Type | Package Location | Purpose |
|----------------|------------------|---------|
| **Services** | `src/Laravel/Services/` | Business logic and data operations |
| **Contracts** | `src/Contracts/` | Public APIs for consumption |
| **Models** | `src/Laravel/Models/` | Eloquent ORM integration |
| **Commands** | `src/Laravel/Commands/` | CLI operations and automation |

**Example Service Usage (Correct Approach):**
```php
// Frontend developers build their own controllers using our services
class MyAppController extends Controller 
{
    public function getDashboard(CrmDashboardService $dashboard) 
    {
        return $dashboard->forUser(auth()->id())->pending();
    }
}
```

**Consumer Integration Options:**
1. **Vue/React Frontend**: Build your own API endpoints consuming our services
2. **Mobile App**: Direct service integration via Laravel API routes you create
3. **Third-party Systems**: API integrations via services exposed through your application's API
4. **CLI Tools**: Artisan commands for automation and batch operations

This approach maintains the headless, framework-agnostic nature while providing maximum flexibility to consumers.

---

## Implementation by Progressive Levels

### LEVEL 1 - Basic CRM (5 Minutes to Setup)

#### Core Entry Point
```php
<?php
// src/Laravel/Traits/HasCrm.php
namespace Nexus\Crm\Laravel\Traits;

use Nexus\Crm\Core\DTOs\Contact;
use Nexus\Crm\Core\Rules\GuardRule;
use Nexus\Crm\Core\Events\ContactCreatedEvent;

trait HasCrm
{
    public function crm(): array
    {
        return $this->crmConfig ?? [
            'entities' => [
                'contact' => [
                    'fields' => ['name', 'email', 'phone'],
                ],
            ],
        ];
    }
    
    public function addContact(array $data): Contact
    {
        // Validate with guard rules
        $guard = new GuardRule();
        if (!$guard->can('create', $this, $data)) {
            throw new UnauthorizedActionException();
        }
        
        // Create contact in memory (no database)
        $contact = new Contact($data);
        
        // Emit event for hooks
        event(new ContactCreatedEvent($this, $contact));
        
        return $contact;
    }
    
    public function getContacts(): array
    {
        // Return contacts stored in model attributes
        return $this->contacts ?? [];
    }
    
    public function history(): array
    {
        // Return audit log from model attributes
        return $this->audit_log ?? [];
    }
}
```

#### Supporting Files for Level 1
| File | Purpose | Lines of Code |
|------|---------|---------------|
| `src/Core/DTOs/Contact.php` | Contact data structure | ~50 |
| `src/Core/Rules/GuardRule.php` | Permission checking | ~30 |
| `src/Core/Events/ContactCreatedEvent.php` | Domain event | ~20 |
| `resources/examples/basic_usage.php` | Quick start example | ~25 |
| `config/crm.php` | Basic configuration | ~50 |

**Total Level 1 Code: ~175 lines** - Perfect for 5-minute setup!

#### Developer Experience Level 1 (5 minutes)
```php
// 1. Add trait
use HasCrm;

// 2. Define basic config
public function crm(): array {
    return ['entities' => ['contact' => ['fields' => ['name', 'email']]]];
}

// 3. Use it!
$user->crm()->addContact(['name' => 'John', 'email' => 'john@example.com']);
```

### LEVEL 2 - Sales Automation (1 Hour to Setup)

#### Database Migration Example
```php
<?php
// database/migrations/2025_11_16_000001_create_crm_definitions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('schema'); // Workflow definition
            $table->boolean('active')->default(true);
            $table->string('version')->default('1.0.0');
            $table->timestamps();
            
            $table->index(['active', 'version']);
        });
        
        Schema::create('crm_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('status')->default('new');
            $table->integer('score')->default(0);
            $table->json('data')->nullable(); // Custom fields
            $table->timestamps();
            
            $table->index(['status', 'score']);
        });
    }
};
```

#### CRM Engine Core
```php
<?php
// src/Core/Engine/CrmEngine.php
namespace Nexus\Crm\Core\Engine;

use Nexus\Crm\Core\DTOs\CrmDefinition;
use Nexus\Crm\Core\DTOs\Lead;
use Nexus\Crm\Core\Services\LeadService;

class CrmEngine
{
    public function __construct(
        private LeadService $leadService,
        private TransitionManager $transitionManager,
        private StateManager $stateManager
    ) {}
    
    public function createLead(CrmDefinition $definition, array $data): Lead
    {
        // Create lead entity
        $lead = $this->leadService->create($data);
        
        // Initialize CRM instance
        $instance = $this->stateManager->initialize($definition, $lead);
        
        // Trigger initial events
        $this->executeTransitions($instance, 'lead_created');
        
        return $lead;
    }
    
    public function executeTransition(Lead $lead, string $transitionName): void
    {
        $instance = $this->stateManager->getInstance($lead);
        
        // Validate transition
        $transition = $this->transitionManager->getTransition($instance, $transitionName);
        
        // Execute with conditions
        if ($this->evaluateConditions($transition->conditions)) {
            $this->transitionManager->apply($instance, $transition);
            
            // Run actions
            $this->executeActions($transition->actions);
        }
    }
    
    private function evaluateConditions(array $conditions): bool
    {
        foreach ($conditions as $condition) {
            if (!$condition->evaluate($this->getContext())) {
                return false;
            }
        }
        return true;
    }
}
```

#### Supporting Files for Level 2
| File | Purpose | Lines of Code |
|------|---------|---------------|
| `database/migrations/*.php` | 10 database tables | ~300 |
| `src/Core/Engine/CrmEngine.php` | Main orchestration | ~100 |
| `src/Core/Automations/PipelineBuilder.php` | Workflow builder | ~80 |
| `src/Core/Conditions/ExpressionCondition.php` | Expression parser | ~60 |
| `src/Core/Strategies/UnisonStrategy.php` | Approval strategies | ~40 |
| `resources/examples/sales_pipeline.php` | Database-driven example | ~50 |

**Total Level 2 Additional Code: ~630 lines** - Database-powered CRM with full automation!

#### Developer Experience Level 2 (1 hour)
```bash
# 1. Run migrations
php artisan migrate

# 2. Install CRM
php artisan crm:install

# 3. Define workflow
$definition = CrmDefinition::create([
    'name' => 'Sales Pipeline',
    'schema' => json_decode(file_get_contents('enterprise-pipeline.json'))
]);

# 4. Create leads
$lead = app(CrmEngine::class)->createLead($definition, $data);
```

### LEVEL 3 - Enterprise CRM (Production-Ready)

#### Timer System for SLA
```php
<?php
// src/Core/Timers/TimerQueue.php
namespace Nexus\Crm\Core\Timers;

use Nexus\Crm\Core\DTOs\CrmInstance;
use Nexus\Crm\Core\Rules\SlaRule;

class TimerQueue
{
    public function schedule(CrmInstance $instance, string $action, \DateTime $triggerAt, array $payload = []): void
    {
        $timer = new CrmTimer([
            'instance_id' => $instance->getId(),
            'type' => $action,
            'trigger_at' => $triggerAt,
            'payload' => json_encode($payload),
        ]);
        
        $timer->save();
        
        // Index for efficient querying
        $this->createIndex($timer->trigger_at);
    }
    
    public function processDueTimers(): array
    {
        $dueTimers = CrmTimer::where('trigger_at', '<=', now())
            ->whereNull('processed_at')
            ->orderBy('trigger_at')
            ->limit(100) // Process in batches
            ->get();
            
        $processed = [];
        
        foreach ($dueTimers as $timer) {
            try {
                $this->processTimer($timer);
                $processed[] = $timer->id;
            } catch (\Exception $e) {
                $this->handleTimerError($timer, $e);
            }
        }
        
        return $processed;
    }
}
```

#### Escalation Engine
```php
<?php
// src/Core/Rules/EscalationRule.php
namespace Nexus\Crm\Core\Rules;

use Nexus\Crm\Core\DTOs\Lead;
use Nexus\Crm\Core\Timers\TimerQueue;
use Nexus\Crm\Core\Events\EscalationTriggeredEvent;

class EscalationRule
{
    public function __construct(
        private TimerQueue $timerQueue,
        private EscalationService $escalationService
    ) {}
    
    public function setupEscalation(Lead $lead, array $rules): void
    {
        foreach ($rules as $rule) {
            if ($this->matchesConditions($lead, $rule['conditions'])) {
                $triggerAt = $this->calculateTriggerTime($lead, $rule['after']);
                
                $this->timerQueue->schedule(
                    $lead->getInstance(),
                    'escalate',
                    $triggerAt,
                    [
                        'escalation_level' => $rule['level'],
                        'from_user_id' => $lead->assigned_to,
                        'to_user_id' => $rule['assign_to'],
                        'reason' => $rule['reason'] ?? 'Timeout',
                    ]
                );
            }
        }
    }
    
    public function executeEscalation(array $timerData): void
    {
        $instance = CrmInstance::find($timerData['instance_id']);
        $lead = $instance->getEntity();
        
        // Escalate to next user
        $this->escalationService->reassign(
            $lead,
            $timerData['from_user_id'],
            $timerData['to_user_id'],
            $timerData['reason']
        );
        
        // Emit event
        event(new EscalationTriggeredEvent($lead, $timerData));
        
        // Log escalation
        $this->logEscalation($lead, $timerData);
    }
}
```

#### SLA Breach Detection
```php
<?php
// src/Core/Rules/SlaRule.php
namespace Nexus\Crm\Core\Rules;

use Nexus\Crm\Core\DTOs\CrmInstance;
use Nexus\Crm\Core\Events\SlaBreachedEvent;

class SlaRule
{
    public function checkBreach(CrmInstance $instance): bool
    {
        $sla = $instance->getSla();
        
        if (!$sla) {
            return false;
        }
        
        $duration = $sla->getDurationInMinutes();
        $elapsed = $instance->getElapsedTimeInMinutes();
        
        if ($elapsed > $duration) {
            $breachLevel = $this->calculateBreachLevel($elapsed, $duration);
            
            $this->triggerBreachActions($instance, $breachLevel);
            
            event(new SlaBreachedEvent($instance, $breachLevel));
            
            return true;
        }
        
        return false;
    }
    
    private function triggerBreachActions(CrmInstance $instance, string $level): void
    {
        $actions = config("crm.sla.breach_actions.{$level}", []);
        
        foreach ($actions as $action) {
            match($action) {
                'escalate_to_manager' => $this->escalateToManager($instance),
                'send_sla_breach_notification' => $this->sendNotification($instance),
                'pause_processing' => $this->pauseProcessing($instance),
                default => null
            };
        }
    }
}
```

#### Supporting Files for Level 3
| File | Purpose | Lines of Code |
|------|---------|---------------|
| `src/Core/Timers/TimerQueue.php` | Timer scheduling system | ~80 |
| `src/Core/Rules/EscalationRule.php` | Escalation logic | ~100 |
| `src/Core/Rules/SlaRule.php` | SLA breach detection | ~90 |
| `src/Laravel/Commands/ProcessTimersCommand.php` | Timer processor | ~50 |
| `src/Laravel/Commands/ProcessEscalationsCommand.php` | Escalation processor | ~40 |
| `resources/examples/enterprise_usage.php` | Enterprise examples | ~60 |

**Total Level 3 Additional Code: ~420 lines** - Production automation with SLA and escalation!

#### Developer Experience Level 3 (Production)
```php
// 1. Configure SLA
config(['crm.sla.default_duration' => '2 days']);

// 2. Setup escalation
$lead->escalateAfter('24 hours');

// 3. Setup delegation
$lead->delegateTo($userId, now()->addWeek());

// 4. Custom conditions
class CustomCondition implements ConditionEvaluatorInterface {
    public function evaluate(array $context): bool {
        return $context['score'] > 75 && $context['budget'] > 10000;
    }
}
```

### Total Package Size Summary

| Level | Core Files | Additional Lines | Cumulative |
|-------|------------|------------------|------------|
| **Level 1** | 5 files | ~175 lines | **~175 lines** |
| **Level 2** | +15 files | ~630 lines | **~805 lines** |
| **Level 3** | +6 files | ~420 lines | **~1,225 lines** |

**Total Production Package: ~1,200 lines of business logic** - Highly maintainable!

---

## Integration with Nexus ERP Ecosystem

The nexus-crm package integrates seamlessly with other Nexus ERP packages while maintaining atomic independence:

### Integration with nexus-tenancy

**Purpose:** Multi-tenant data isolation for CRM entities

**Integration Points:**
- All CRM tables include `tenant_id` foreign key
- `BelongsToTenant` trait applied to all CRM models
- Global scope automatically filters by current tenant
- Tenant context set via `TenantManager` contract

```php
use Nexus\Tenancy\Contracts\TenantManagerContract;
use Nexus\Tenancy\Traits\BelongsToTenant;
use Nexus\Crm\Traits\HasCrm;

class Lead extends Model
{
    use HasCrm, BelongsToTenant;
    
    // Automatically scoped to current tenant
    // No manual tenant_id filtering needed
}
```

### Integration with nexus-audit-log

**Purpose:** Comprehensive audit trail for all CRM activities

**Integration Points:**
- `HasActivityLogging` trait on all CRM models
- Automatic logging of state changes, transitions, assignments
- Custom log names per entity type: `crm_lead`, `crm_opportunity`
- Audit data accessible via `$instance->getActivityLogs()`

```php
use Nexus\AuditLog\Traits\HasActivityLogging;

class CrmInstance extends Model
{
    use HasActivityLogging;
    
    protected function configureActivityLogging(): array
    {
        return [
            'log_name' => 'crm_instance',
            'log_attributes' => ['state', 'stage', 'assigned_to'],
            'log_only_dirty' => true,
        ];
    }
}
```

### Integration with nexus-settings

**Purpose:** Configurable CRM behavior per tenant/user

**Integration Points:**
- CRM configuration stored in settings system
- Per-tenant lead assignment rules
- Per-user notification preferences
- Global CRM feature toggles

```php
use Nexus\Settings\Facades\Settings;

// Get tenant-specific lead assignment rule
$assignmentRule = Settings::get('crm.lead_assignment_rule', $tenantId);

// Get user-specific notification preferences
$notifyOnNewLead = Settings::get('crm.notify_on_new_lead', $tenantId, $userId);
```

### Integration with nexus-sequencing

**Purpose:** Automatic numbering for leads, opportunities, quotes

**Integration Points:**
- Lead numbers: `LEAD-{YYYY}-{0000}`
- Opportunity numbers: `OPP-{YYYY}-{0000}`
- Quote numbers: `QUOTE-{YYYY}-{0000}`

```php
use Nexus\Sequencing\Actions\GenerateSerialNumberAction;

// Generate lead number
$leadNumber = GenerateSerialNumberAction::run('LEAD-{YYYY}-{0000}');
// Result: "LEAD-2025-0001"
```

### Integration with nexus-workflow

**Purpose:** Advanced workflow automation for complex CRM processes

**Integration Points:**
- CRM stages can trigger workflow instances
- Workflow approvals can advance CRM stages
- Shared workflow engine for state management
- Event synchronization between packages

**Note:** Both nexus-crm and nexus-workflow are independent but can interoperate via events and contracts.

### Atomic Package Principles Applied

**Zero Hard Dependencies:**
- nexus-crm does NOT require any other Nexus package to function
- nexus-crm defines contracts it needs (e.g., `TenantManagerContract`, `ActivityLoggerContract`)
- The Nexus ERP orchestrator (`nexus/erp`) binds concrete implementations when packages are present

### Nexus Adapter Strategy

To preserve the atomicity of `nexus-crm` while still allowing deep integration inside the Nexus ERP ecosystem, all Nexus-specific adapters and bindings must be implemented inside `nexus-erp` (or a dedicated adapter package under the `Nexus` organization). This keeps `nexus-crm` generic and attractive to third-party adopters while enabling `Nexus\Erp` to provide opinionated behaviour for ERP deployments.

Key responsibilities that belong in `nexus-erp`:
- Tenancy-specific wiring and tenant-aware services (`Nexus\Erp\Crm\Adapters`) that call into `nexus-crm` core.
- Cross-package orchestration (sequencing, audit-log, settings) using contracts defined in `nexus-crm` but bound inside the orchestrator.
- Integration tests that exercise multiple packages together (marked `@group orchestrator` or guarded by `CRM_ORCHESTRATOR_TESTS` environment variable).

Example adapter service provider (in `nexus-erp`):

```php
// packages/nexus-erp/src/Crm/Providers/CrmAdapterServiceProvider.php
public function register()
{
    $this->app->bind(
        \Nexus\Erp\Crm\Contracts\NexusCrmAdapterInterface::class,
        \Nexus\Erp\Crm\Adapters\NexusCrmAdapter::class
    );
}
```
- nexus-crm uses contracts without knowledge of concrete implementations from other packages

**Contract-Based Communication:**
```php
// In CrmServiceProvider
public function register(): void
{
    // Define contracts that nexus-crm needs
    // NOTE: Do NOT bind concrete implementations from other packages here.
    // The orchestrator (nexus/erp) is responsible for binding contracts to implementations.
    
    // Example: Use contracts if available
    if (interface_exists(TenantManagerContract::class)) {
        // The orchestrator will have bound the concrete implementation
        // We simply use the contract interface
        $tenantManager = $this->app->make(TenantManagerContract::class);
        // Use $tenantManager as needed, without knowledge of its concrete class
    }
    
    // Optional integration with audit logging
    if (interface_exists(ActivityLoggerContract::class)) {
        $activityLogger = $this->app->make(ActivityLoggerContract::class);
        $this->enableActivityLogging($activityLogger);
    }
}
```

---

## Success Metrics

| Metric | Target | Time Period | Why It Matters |
|--------|--------|-------------|----------------|
| **Package Adoption** | >2,000 installations | 6 months | Validates mass market appeal |
| **Hello World Time** | <5 minutes | Ongoing | Measures developer experience quality |
| **Promotion Rate** | >10% upgrade to Level 2 | 6 months | Shows growth path effectiveness |
| **Enterprise Usage** | >5% using SLA features | 6 months | Validates niche value proposition |
| **Critical Bugs** | <5 P0 bugs | 6 months | Ensures production quality |
| **Test Coverage** | >85% overall, >90% core | Ongoing | Maintains engine reliability |
| **Documentation Quality** | <10 support questions/week | 3 months | Measures documentation clarity |
| **Performance SLA** | 95% of actions <100ms | Ongoing | Validates performance targets |

---

## Development Phases

### Phase 1: Level 1 - Basic CRM (Weeks 1-3)

**Goal:** Deliver fully functional trait-based CRM with zero database migrations

**Deliverables:**
- `HasCrm` trait implementation
- In-model CRM configuration parser
- Basic CRM engine for contact management
- Unit and feature tests for Level 1
- Documentation with "5-minute quickstart"

**Acceptance Criteria:**
- Developer can add `HasCrm` trait and define contacts in <5 minutes
- All contact CRUD operations work without migrations
- Full test coverage (>90%)

### Phase 2: Level 2 - Sales Automation (Weeks 4-8)

**Goal:** Database-driven CRM with leads, opportunities, and pipelines

**Deliverables:**
- Database migrations for CRM tables
- CRM definition JSON schema parser
- Lead and opportunity entity management
- Conditional pipeline engine
- Multi-user assignment strategies
- Dashboard API service
- Integration plugins (email, webhook)
- Feature tests for Level 2

**Acceptance Criteria:**
- Level 1 code continues working without changes
- Developers can define CRM workflows in JSON
- Conditional transitions work correctly
- Multi-user assignments resolve per strategy
- Dashboard provides real-time data

### Phase 3: Level 3 - Enterprise Features (Weeks 9-12)

**Goal:** Production-ready enterprise CRM with SLA, escalation, delegation

**Deliverables:**
- Timer system implementation
- SLA tracking and breach detection
- Escalation rules engine
- Delegation with date ranges
- Rollback/compensation logic
- Custom field configuration system
- Advanced reporting queries
- Enterprise feature tests

**Acceptance Criteria:**
- SLA breaches detected and actions triggered
- Escalations occur automatically per rules
- Delegations route correctly during absences
- Failed processes can rollback with compensation
- Custom fields validated and applied

### Phase 4: Extensibility & Polish (Weeks 13-14)

**Goal:** Enable ecosystem extensions and polish package

**Deliverables:**
- Custom condition evaluator API
- Custom integration plugin API
- Custom approval strategy API
- Performance optimization
- Security audit
- Code review and refactoring

**Acceptance Criteria:**
- Developers can create custom conditions
- Custom integrations register correctly
- Performance targets met (see NFRs)
- No critical security vulnerabilities

### Phase 5: Documentation & Launch (Weeks 15-16)

**Goal:** Comprehensive documentation and package release

**Deliverables:**
- Service-level API documentation
- Tutorial series (Level 1, 2, 3)
- Video quickstart guides
- Migration guides from other CRMs
- Performance tuning guide
- Beta release and feedback collection
- Public v1.0.0 release

**Acceptance Criteria:**
- Documentation covers all use cases
- Tutorials enable <10-minute setup
- Beta users provide positive feedback
- All success metrics on track
- Package published to Packagist

---

## Testing Requirements

### Package Test Harness

- Package-level configuration lives in `phpunit.xml`, which loads `tests/bootstrap.php` to wire up `Orchestra\Testbench`, register service providers, and prepare package contracts without the rest of the Nexus ecosystem.
- Tests use Pest (`pestphp/pest`) for human-friendly syntax; `composer test` runs `vendor/bin/pest` at the package level.
- `tests/Support/TestCase.php` extends that bootstrap to provide shared helpers, in-memory database setup, and stub implementations for `TenantManager`, `ActivityLogger`, `Sequencing`, and `Settings` contracts located under `tests/Support/Mocks/`.
- Factories in `database/factories/` and `tests/Factories/` build leads, opportunities, campaigns, timers, and escalations for the feature tests and the `CrmSeeder`, ensuring acceptance criteria (US-001 through US-025) have deterministic data.
- `composer.json` exposes `scripts.test` (`phpunit --configuration phpunit.xml`) and `scripts.test-coverage` (`phpunit --configuration phpunit.xml --coverage-text --ansi`) so local development, package CI, and orchestrator automation can run the same harness. CI in `.github/workflows/ci.yml` simply runs `composer test` plus the summary checks referenced in the System Architectural Document.

**Orchestrator Integration Tests**

Integration tests that require concrete Nexus package implementations must only be executed within the Nexus orchestrator (`nexus-erp`) where concrete bindings are available. The recommended pattern is to mark these tests with the PHPUnit/Pest group `orchestrator` and protect them with an environment variable (for CI and local runs):

```bash
# In CI: run orchestrator tests
CRM_ORCHESTRATOR_TESTS=1 composer test -- --group orchestrator

# Locally: run only package tests
composer test
```

This avoids adding Nexus-specific dependencies to `nexus-crm` while providing full integration coverage in the orchestrator.

### Unit Tests

**Scope:** Individual classes and methods in isolation

**Coverage Targets:**
- Transition logic: 100%
- Approval strategies: 100%
- Condition evaluators: 100%
- Timer scheduling: 100%
- Delegation routing: 100%

**Example Tests:**
```php
test('unison strategy requires all approvals', function () {
    $strategy = new UnisonStrategy();
    $responses = [
        ['user_id' => 1, 'approved' => true],
        ['user_id' => 2, 'approved' => false],
    ];
    
    expect($strategy->canProceed($responses))->toBeFalse();
});

test('majority strategy requires >50% approval', function () {
    $strategy = new MajorityStrategy();
    $responses = [
        ['user_id' => 1, 'approved' => true],
        ['user_id' => 2, 'approved' => true],
        ['user_id' => 3, 'approved' => false],
    ];
    
    expect($strategy->canProceed($responses))->toBeTrue();
});
```

### Feature Tests

**Scope:** Package features working within Laravel context

**Test Cases:**
- Level 1 complete lifecycle (add, update, delete contacts)
- Level 2 entity management (leads, opportunities, campaigns)
- Level 3 escalation and SLA tracking
- Multi-user assignment and approval
- Custom condition and strategy registration

**Example Tests:**
```php
test('lead transitions from new to qualified based on score', function () {
    $definition = CrmDefinition::factory()->create([
        'schema' => [
            'transitions' => [
                'qualify' => [
                    'from' => 'new',
                    'to' => 'qualified',
                    'condition' => 'data.score > 50',
                ],
            ],
        ],
    ]);
    
    $lead = CrmContact::factory()->create([
        'stage' => 'new',
        'score' => 60,
    ]);
    
    $engine = app(CrmEngine::class);
    $engine->executeTransition($lead, 'qualify');
    
    expect($lead->refresh()->stage)->toBe('qualified');
});
```

### Integration Tests

**Scope:** Interaction with other Nexus packages and external systems

**Test Cases:**
- Tenancy integration (queries scoped to tenant)
- Audit logging integration (activities logged correctly)
- Settings integration (configuration applied per tenant/user)
- Email integration (emails queued and sent)
- Webhook integration (HTTP requests sent correctly)

Integration tests that require concrete Nexus package implementations must only execute in the orchestrator layer (`nexus/erp` / Edward). The package-level CI honors this by guarding these tests with an environment flag (e.g., `CRM_ORCHESTRATOR_TESTS=1`) or by tagging them `@group orchestrator`, so `composer test` skips them when the orchestrator and its bindings are unavailable. This aligns with the System Architectural Document's guidance that cross-package verification runs under the orchestrator's umbrella.

### Acceptance Tests

**Scope:** User stories from requirements validated end-to-end

**Test Cases:**
- All user stories (US-001 through US-025)
- "Hello World" time <5 minutes verified
- Level 1 to Level 2 promotion without code changes
- All success metrics validated

---

## Dependencies

### Required Dependencies
**Composer "suggest" recommendations (optional integrations)**

We recommend adding optional `composer` suggestions in `nexus-crm` so users are aware of integrations that the orchestrator or host application can provide. These should be non-blocking and not required for the package to function.

Example `composer.json` additions:

```json
"suggest": {
    "nexus/tenancy": "Optional tenancy integration for tenant-scoped CRM",
    "nexus/audit-log": "Optional activity logging integration",
    "nexus/sequencing": "Optional numbering integration",
    "nexus/erp": "Optional Nexus orchestrator (binds Nexus adapters)"
}
```

This keeps `nexus-crm` usable outside the Nexus ecosystem while still advertising the available optional, enterprise integrations.

| Dependency | Version | Purpose |
|------------|---------|---------|
| **PHP** | ≥8.3 | Aligns with the repository-wide standard and delivers the latest language features (enums, readonly properties) |
| **Database** | MySQL 8+, PostgreSQL 12+, SQLite 3.35+, SQL Server | Data persistence |

### Optional Dependencies (Laravel Integration)

| Dependency | Version | Purpose |
|------------|---------|---------|
| **Laravel Framework** | ≥12.0 | Framework adapter features |
| **nexus-tenancy** | ≥1.0 | Multi-tenant data isolation |
| **nexus-audit-log** | ≥1.0 | Activity logging and audit trail |
| **nexus-settings** | ≥1.0 | Configuration management |
| **nexus-sequencing** | ≥1.0 | Automatic numbering for entities |
| **Redis** | ≥6.0 | Caching and queue backend (optional) |

### Dev Dependencies

| Dependency | Version | Purpose |
|------------|---------|---------|
| **phpunit/phpunit** | ^10.0 | PHPUnit runner for `composer test` and coverage analysis |
| **orchestra/testbench** | ^9.0 | Boots Laravel services for isolated package tests via `tests/bootstrap.php` |
| **mockery/mockery** | ^1.6 | Simplifies contract/mocking helpers inside `tests/Support/Mocks` |
| **pestphp/pest** | ^3.0 | Human-friendly testing DSL used in unit and feature tests |

### Package Installation

```bash
# Basic installation
composer require nexus/crm

# With optional integrations
composer require nexus/crm nexus/tenancy nexus/audit-log nexus/settings nexus/sequencing
```

---

## Document Updates Summary

This updated REQUIREMENTS_AND_STRATEGY.md document includes the following critical corrections:

### ✅ Key Changes Made

1. **HTTP Layer Removed from Laravel Adapter**
   - Removed `src/Laravel/Http/Controllers/` directory and all API controllers
   - Removed `src/Laravel/Http/Requests/` and `src/Laravel/Http/Middleware/`
   - Updated Dashboard API requirement to service-level only
   - Changed API endpoint tests to service integration tests

2. **Added Requirement to Package Component Mapping**
   - Comprehensive mapping of each requirement to specific files
   - Line count estimates for each component
   - Clear organization by Level 1, Level 2, Level 3, and Non-Functional requirements
   - Extensibility requirements mapped to contracts and interfaces

3. **Headless Architecture Clarified**
   - Updated core philosophy to emphasize service-level APIs only
   - Clarified Laravel adapter pattern (models, migrations, commands, services)
   - Added rationale section explaining why HTTP controllers violate headless principles
   - Provided examples of proper service consumption patterns

4. **Package Structure Corrections**
   - Removed HTTP layer from tree structure
   - Updated test file references
   - Changed API documentation to SERVICE_API.md
   - Maintained framework-agnostic core with Laravel adapters

### 🎯 Result: True Headless Package

The package now properly adheres to the **headless backend** principle:
- **Service-level APIs only** - Consumers build their own HTTP interfaces
- **Framework-agnostic core** - Business logic independent of Laravel
- **Laravel adapter pattern** - Clean separation of framework-specific code
- **Maximum flexibility** - Works with any frontend (Vue, React, mobile, CLI)

---

## Glossary

| Term | Definition |
|------|------------|
| **Level 1** | Basic trait-based CRM for contact management without database migrations |
| **Level 2** | Database-driven CRM with leads, opportunities, pipelines, and automation |
| **Level 3** | Enterprise CRM with SLA tracking, escalation, delegation, and advanced features |
| **Lead** | Prospect entity representing a potential customer or sales opportunity |
| **Opportunity** | Sales deal stage representing a qualified lead with revenue potential |
| **Contact** | Person or entity with contact information (name, email, phone) |
| **Campaign** | Marketing or sales initiative targeting multiple leads simultaneously |
| **Stage** | Current state in a CRM entity lifecycle (e.g., "new", "qualified", "closed") |
| **Transition** | Movement from one stage to another based on conditions and rules |
| **Pipeline** | Series of stages that an entity progresses through (e.g., sales pipeline) |
| **Escalation** | Automatic reassignment of stale entities after timeout period |
| **SLA** | Service Level Agreement - time constraint for completing a stage |
| **Delegation** | Temporary routing of assignments to another user during absence |
| **Compensation** | Rollback activity executed when a process fails to restore previous state |
| **Gateway** | Decision point in a workflow where conditions determine next path |
| **Approval Strategy** | Method for resolving multi-user assignments (unison, majority, quorum) |
| **Integration** | External system connection (email, webhook, API) for automation |
| **Guard Condition** | Boolean check that determines if an action can proceed |
| **Hook** | Callback function executed before or after an action (before/after hooks) |

---

## Document Metadata

**Document Version:** 1.0.0  
**Last Updated:** November 16, 2025  
**Status:** Ready for Implementation  
**Author:** Nexus ERP Development Team  
**Related Documents:**
- [Nexus ERP System Architecture Document](../../docs/SYSTEM%20ARCHITECTURAL%20DOCUMENT.md)
- [Nexus ERP Coding Guidelines](../../CODING_GUIDELINES.md)
- [nexus-tenancy Requirements](../nexus-tenancy/REQUIREMENTS.md)
- [nexus-audit-log Requirements](../nexus-audit-log/REQUIREMENTS.md)

---

## Key Architectural Decisions

### 1. Core vs Adapter Separation
- **`src/Core/`**: 100% framework-agnostic business logic
- **`src/Laravel/`**: Laravel-specific adapters and integrations
- **`src/Contracts/`**: Public API contracts that users implement

### 2. Progressive Disclosure Levels
```
Level 1 (Basic):  Use HasCrm trait → No database needed
Level 2 (Advanced): Database migrations → Full CRM features  
Level 3 (Enterprise): Timers/SLA → Production automation
```

### 3. Integration Points
```php
// Users implement contracts for custom functionality
use Nexus\Crm\Contracts\Integrations\ConditionEvaluatorInterface;

class CustomCondition implements ConditionEvaluatorInterface
{
    public function evaluate(array $context): bool
    {
        // Custom business logic
        return $context['score'] > 75;
    }
}
```

### 4. Package Distribution Ready
- **`composer.json`**: Proper PSR-4 autoloading, semantic versioning
- **`config/`**: Publishable configuration files
- **`stubs/`**: Laravel publishable stubs
- **`resources/`**: Localizable resources and examples
- **`docs/`**: Comprehensive documentation

---

## Package Distribution Assets

| Asset | Purpose | Location |
|-------|---------|----------|
| **composer.json** | Package metadata and autoloading | Root |
| **README.md** | Quick start and overview | Root |
| **config/crm.php** | Publishable configuration | `config/` |
| **stubs/** | Laravel publishable templates | `stubs/` |
| **resources/definitions/** | JSON workflow examples | `resources/definitions/` |
| **resources/examples/** | Usage code examples | `resources/examples/` |
| **docs/** | Comprehensive documentation | `docs/` |
| **phpunit.xml** | Test configuration | Root |
| **scripts/** | Development automation | `scripts/` |

---

## Development Workflow

### Installation
```bash
composer require nexus/crm

# Laravel users can publish configuration
php artisan vendor:publish --provider="Nexus\Crm\Laravel\Services\CrmServiceProvider"
```

### Usage Examples

**Level 1 - Basic CRM:**
```php
use Nexus\Crm\Laravel\Traits\HasCrm;

class User extends Model
{
    use HasCrm;
    
    public function crm(): array
    {
        return [
            'entities' => [
                'contact' => [
                    'fields' => ['name', 'email', 'phone'],
                ],
            ],
        ];
    }
}

// Usage
$user = User::find(1);
$user->crm()->addContact([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);
```

**Level 2 - Database-Driven:**
```php
// Publish migration and configure CRM definition
php artisan migrate
php artisan crm:install

// Define workflow in JSON
$definition = CrmDefinition::create([
    'name' => 'Sales Pipeline',
    'schema' => json_decode(file_get_contents('resources/definitions/sales_pipeline.json'))
]);

// Create leads programmatically
$lead = app(CrmEngine::class)->createLead($definition, $data);
```

**Level 3 - Enterprise:**
```php
// Configure SLA and escalation rules
$config = config('crm.sla.default_duration', '2 days');

// Setup automatic escalation
$lead->escalateAfter('24 hours');

// Setup delegation
$lead->delegateTo($userId, now()->addWeek());
```

---

**Next Steps:**
1. Review this requirements document with stakeholders
2. Confirm success metrics and targets
3. Validate technical feasibility of progressive disclosure model
4. Begin Phase 1 implementation (Level 1 - Basic CRM)
5. Establish CI/CD pipeline with automated testing
6. Create project board for task tracking

This comprehensive document provides **every requirement** from the specification with a **clear implementation path** in the package structure, while maintaining the core principles of **maximum atomicity**, **progressive disclosure**, and **standalone distribution**.
