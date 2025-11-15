# Nexus Marketing Package - Requirements Document

**Package Name:** `nexus-marketing`  
**Version:** 1.0.0  
**Last Updated:** November 15, 2025  
**Status:** Initial Draft - Progressive Disclosure Model  
**License:** MIT

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Core Philosophy & Principles](#core-philosophy--principles)
3. [Problem Statement](#problem-statement)
4. [Solution Overview](#solution-overview)
5. [Personas & User Stories](#personas--user-stories)
6. [Functional Requirements](#functional-requirements)
7. [Non-Functional Requirements](#non-functional-requirements)
8. [Data Model & Architecture](#data-model--architecture)
9. [API Specifications](#api-specifications)
10. [Development Phases](#development-phases)
11. [Testing Strategy](#testing-strategy)
12. [Dependencies](#dependencies)
13. [Success Metrics](#success-metrics)
14. [Glossary](#glossary)

---

## Executive Summary

**nexus-marketing** is a **progressive marketing automation engine** for PHP/Laravel that scales from basic campaign tracking to enterprise-grade marketing orchestration. It is designed as a **self-contained, framework-agnostic** package that can be used independently in any PHP project or as part of the Nexus ERP ecosystem.

### Key Highlights

- **Progressive Disclosure Model**: Start simple (5 minutes), scale to enterprise (production-ready)
- **Marketing-Focused**: Campaigns, leads, channels, analytics, and automation
- **Independent & Reusable**: Works standalone outside Nexus ERP
- **Headless API**: No UI components, pure backend logic
- **Framework Agnostic Core**: Core logic independent of Laravel
- **Extensible Architecture**: Plugin system for channels, conditions, and integrations

### What This Package Does

✅ **Marketing automation** (campaigns, email sequences, lead nurturing)  
✅ **Multi-channel orchestration** (email, SMS, social media, webhooks)  
✅ **Lead management** (scoring, segmentation, tracking)  
✅ **Campaign analytics** (metrics, ROI, engagement tracking)  
✅ **A/B testing** (variant testing, conversion optimization)  
✅ **Audience targeting** (conditional segmentation, behavioral triggers)

### What This Package Does NOT Do

❌ **Sales operations** (quotes, orders, invoicing) → Use `nexus-sales` package  
❌ **CRM functionality** (customer management, opportunities) → Use `nexus-crm` package  
❌ **E-commerce** (product catalog, shopping cart) → Use appropriate e-commerce package  
❌ **UI components** (frontend, dashboards) → Build separately using APIs

---

## Core Philosophy & Principles

### 1. Progressive Disclosure

The package implements a **three-phase progression model** that allows developers to adopt features incrementally:

| Phase | Description | Setup Time | Use Case |
|-------|-------------|------------|----------|
| **Phase 1** | Basic Campaign Tracking | 5 minutes | Simple marketing needs, MVP projects |
| **Phase 2** | Marketing Automation | 1-2 hours | Growing businesses, multi-channel campaigns |
| **Phase 3** | Enterprise Marketing | Production-ready | Large-scale operations, compliance requirements |

### 2. Architectural Atomicity

Following Nexus ERP's **Maximum Atomicity** principle:

- ✅ **Self-contained**: All marketing logic within this package
- ✅ **Independently testable**: Complete test suite without external dependencies
- ✅ **Zero cross-package coupling**: No direct dependencies on other Nexus packages
- ✅ **Contract-driven communication**: Events and interfaces for integration

### 3. Headless & Framework Agnostic

- **Core logic** (`src/Core/`) is pure PHP, no framework dependencies
- **Laravel adapter** (`src/Adapters/Laravel/`) provides framework integration
- **API-first design**: All functionality exposed via REST/GraphQL APIs
- **No UI components**: Frontend implementation is consumer's responsibility

### 4. Extensibility First

- **Plugin architecture** for custom channels (Slack, Discord, custom APIs)
- **Extensible conditions** for audience segmentation
- **Custom metrics** for business-specific KPIs
- **Webhook integrations** for third-party systems

### 5. Compliance & Privacy

- **GDPR-ready**: Consent tracking, data portability, right to deletion
- **Audit logging**: Complete history of all marketing actions
- **Multi-tenancy support**: Isolated data for multi-tenant systems
- **Configurable retention**: Automatic data cleanup per regulations

---

## Problem Statement

### The Marketing Automation Dilemma

Modern businesses face a difficult choice when selecting marketing automation tools:

#### Option 1: Simple Tools (Basic Campaign Managers)

**Pros:**
- Quick to implement
- Low learning curve
- Affordable or free

**Cons:**
- Limited scalability
- No advanced segmentation
- Weak analytics
- Manual processes
- Cannot handle complex campaigns

#### Option 2: Enterprise Platforms (Marketo, HubSpot, ActiveCampaign)

**Pros:**
- Feature-rich
- Advanced automation
- Professional support

**Cons:**
- Expensive ($1,000-$10,000+/month)
- Vendor lock-in
- Steep learning curve
- Over-engineered for most needs
- Data portability issues
- Limited customization

### The Gap

**80% of businesses** need more than basic tracking but **cannot justify** enterprise platform costs. They need:

1. **Incremental adoption**: Start simple, scale as needed
2. **Cost-effective**: Open-source with enterprise features
3. **Customizable**: Adapt to unique business processes
4. **Portable**: Own and control marketing data
5. **Integrable**: Connect with existing systems

---

## Solution Overview

**nexus-marketing** bridges this gap with a **progressive, open-source marketing automation engine** that provides:

### For Mass Market (80% of Users)

✅ **5-minute setup**: Add a trait, define campaigns in code  
✅ **No database overhead**: Store configurations in model attributes  
✅ **Simple API**: `$model->marketing()->launchCampaign($data)`  
✅ **Immediate value**: Start tracking campaigns right away  
✅ **Zero infrastructure**: Works with existing application setup

### For Growing Businesses (15% of Users)

✅ **Database-driven campaigns**: Store campaigns, leads, analytics  
✅ **Multi-channel automation**: Email + SMS + social media orchestration  
✅ **Lead scoring**: Automatic lead qualification and prioritization  
✅ **Segmentation**: Conditional audience targeting  
✅ **Campaign templates**: Reusable campaign workflows  
✅ **A/B testing**: Built-in variant testing and optimization

### For Enterprise (5% of Users)

✅ **Advanced automation**: Behavioral triggers, drip campaigns  
✅ **AI-powered insights**: Predictive analytics, recommendation engine  
✅ **Compliance tools**: GDPR, CAN-SPAM, CASL compliance  
✅ **Custom integrations**: Webhook plugins, API extensions  
✅ **Scalability**: Handle millions of contacts and campaigns  
✅ **Role-based access**: Team management and permissions

### Backwards Compatibility

**Critical**: Each phase builds upon the previous without breaking changes:

- Phase 1 code continues working after upgrading to Phase 2/3
- API contracts remain stable across versions
- Configuration changes are additive, never subtractive
- Migration guides provided for major version upgrades

---

## Personas & User Stories

### Personas

| ID | Persona | Role | Primary Goal |
|----|---------|------|--------------|
| **P1** | Mass Market Developer | Full-stack dev at startup | "Add marketing campaigns to my Product model in 5 minutes" |
| **P2** | Marketing Automation Engineer | Backend dev at mid-size company | "Build automated drip campaigns with multi-channel delivery" |
| **P3** | Marketing Manager | Business user | "Launch campaigns, track leads, analyze performance without coding" |
| **P4** | System Administrator | IT/DevOps | "Configure channels, metrics, integrations without developer help" |
| **P5** | Compliance Officer | Legal/Privacy team | "Ensure GDPR/CAN-SPAM compliance and audit trail" |

### User Stories

#### Phase 1: Basic Campaign Tracking (Mass Appeal)

| ID | Persona | User Story | Priority | Acceptance Criteria |
|----|---------|------------|----------|---------------------|
| **US-001** | P1 | As a developer, I want to add `HasMarketing` trait to my model to enable campaign tracking | High | Trait added, `marketing()` method available, no migration required |
| **US-002** | P1 | As a developer, I want to define campaigns as an array in my model | High | Campaign definitions stored as JSON in model column, no external tables |
| **US-003** | P1 | As a developer, I want to call `$model->marketing()->launchCampaign($data)` to start a campaign | High | Campaign launched, events dispatched, data validated, transaction-safe |
| **US-004** | P1 | As a developer, I want to call `$model->marketing()->can($action)` to check permissions | High | Returns boolean, uses guards, no side effects |
| **US-005** | P1 | As a developer, I want to call `$model->marketing()->history()` to view campaign history | Medium | Returns collection of changes with timestamps and actors |
| **US-006** | P1 | As a developer, I want to define guard conditions on actions | Medium | Callable conditions, e.g., `fn($campaign) => $campaign->budget > 0` |
| **US-007** | P1 | As a developer, I want hooks (before/after) for campaign lifecycle events | Medium | Callbacks executed, e.g., notify team after campaign launch |

#### Phase 2: Marketing Automation

| ID | Persona | User Story | Priority | Acceptance Criteria |
|----|---------|------------|----------|---------------------|
| **US-010** | P2 | As a developer, I want to promote to database-driven campaigns without code changes | High | Run migration, same API works, campaigns stored in DB, hot-reloadable |
| **US-011** | P2 | As a marketer, I want to define campaign stages (draft → active → paused → completed) | High | State machine enforced, transitions validated, events dispatched |
| **US-012** | P2 | As a marketer, I want conditional audience targeting (e.g., age > 25, location = 'US') | High | Expression evaluator supports ==, >, <, AND, OR, NOT operators |
| **US-013** | P2 | As a marketer, I want parallel channel execution (email + SMS + social) | High | Channels execute concurrently, wait for all to complete, retry on failure |
| **US-014** | P2 | As a marketer, I want multi-team campaign approval workflows | High | Support approval strategies: unanimous, majority, quorum (configurable) |
| **US-015** | P3 | As a marketer, I want a unified dashboard to view active campaigns and metrics | High | API endpoint: `MarketingDashboard::forUser($id)->activeCampaigns()` with filters |
| **US-016** | P3 | As a marketer, I want to log engagements (opens, clicks, conversions) with notes | High | Engagement tracking with metadata, attachments, searchable |
| **US-017** | P2 | As a developer, I want to validate campaign data against JSON schema | Medium | Schema validation for required fields, types: string, number, date, enum |
| **US-018** | P2 | As a developer, I want plugin system for custom channels | High | Async channel execution, built-in: email, SMS, webhook; extensible via plugins |

#### Phase 3: Enterprise Marketing

| ID | Persona | User Story | Priority | Acceptance Criteria |
|----|---------|------------|----------|---------------------|
| **US-020** | P2 | As a marketer, I want automatic escalation for underperforming campaigns | High | After configurable time, notify/reassign, history logged, scheduled check |
| **US-021** | P2 | As a marketer, I want ROI tracking with budget constraints | High | Track spend vs. revenue, breach actions trigger, status: on_track, over_budget |
| **US-022** | P3 | As a marketer, I want to delegate campaigns during absences | High | Delegation table: delegator, delegatee, date ranges; auto-route, max depth: 3 |
| **US-023** | P2 | As a developer, I want rollback logic for failed campaigns | Medium | Compensation actions on failure, reverse order execution |
| **US-024** | P4 | As an admin, I want to configure custom metrics via admin panel | Medium | Database-driven rules, applied on initialization, no code deployment |
| **US-025** | P3 | As a marketer, I want reports on conversion rates and engagement | Medium | Reporting API with filters: date range, channel, segment, export formats |
| **US-026** | P5 | As a compliance officer, I want GDPR compliance features | High | Consent tracking, data export, deletion workflows, audit trail |
| **US-027** | P2 | As a developer, I want A/B testing for campaign variants | High | Split testing with statistical significance calculation, auto-winner selection |

---

## Functional Requirements

### FR-P1: Phase 1 - Basic Campaign Tracking (Mass Appeal)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-P1-001** | `HasMarketing` trait for models | High | Add trait; define `marketing()` array; no migration needed; works instantly |
| **FR-P1-002** | In-model campaign definitions | High | Array-based configuration; stored in model JSON column; no external tables |
| **FR-P1-003** | `marketing()->launchCampaign($data)` method | High | Launches campaign; dispatches events; validates data; transaction-safe |
| **FR-P1-004** | `marketing()->can($action)` permission check | High | Returns boolean; uses guard conditions; read-only operation |
| **FR-P1-005** | `marketing()->history()` audit trail | Medium | Returns collection of changes with timestamps, actors, and metadata |
| **FR-P1-006** | Guard conditions on actions | Medium | Supports callable guards: `fn($campaign) => $campaign->isActive()` |
| **FR-P1-007** | Lifecycle hooks (before/after) | Medium | Callbacks for events: `beforeLaunch`, `afterLaunch`, `beforePause`, etc. |
| **FR-P1-008** | Basic validation rules | High | Required fields, type checking, custom validators |

### FR-P2: Phase 2 - Marketing Automation

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-P2-001** | Database-driven campaign definitions (JSON) | High | Campaigns table stores schemas; same API; overrides in-model config; hot-reloadable |
| **FR-P2-002** | Campaign lifecycle stages | High | States: draft → active → paused → completed; transitions validated; events dispatched |
| **FR-P2-003** | Conditional audience targeting | High | Expression evaluator: ==, >, <, !=, AND, OR, NOT; access to lead/campaign data |
| **FR-P2-004** | Parallel channel execution | High | Array of channels; execute concurrently; wait for all; individual retry on failure |
| **FR-P2-005** | Multi-team approval workflows | High | Approval strategies: unanimous, majority, quorum (2/3, 3/4); extensible |
| **FR-P2-006** | Dashboard API service | High | `MarketingDashboard::forUser($id)->activeCampaigns()`; filter by status, date, channel |
| **FR-P2-007** | Engagement tracking | High | Log opens, clicks, conversions; metadata, comments, attachments; searchable |
| **FR-P2-008** | Data validation via JSON schema | Medium | Schema definition in JSON; types: string, number, date, boolean, enum |
| **FR-P2-009** | Plugin architecture for channels | High | Channel contract interface; async execution; built-in: email, SMS, webhook |
| **FR-P2-010** | Lead scoring system | Medium | Configurable scoring rules; automatic score updates; threshold triggers |
| **FR-P2-011** | Campaign templates | Medium | Reusable templates; variable substitution; versioning |
| **FR-P2-012** | Segment management | High | Dynamic segments based on conditions; cached for performance |

### FR-P3: Phase 3 - Enterprise Marketing

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-P3-001** | Automatic escalation rules | High | After configurable time; notify/reassign; history logged; scheduled workers |
| **FR-P3-002** | ROI tracking with budgets | High | Track spend and revenue; breach actions (pause, alert); status indicators |
| **FR-P3-003** | Campaign delegation system | High | Delegation table; date ranges; automatic routing; maximum delegation depth: 3 |
| **FR-P3-004** | Rollback and compensation logic | Medium | Compensation actions on failure; reverse order execution; idempotent |
| **FR-P3-005** | Custom metrics configuration | Medium | Database-driven metric rules; applied on initialization; no code deployment |
| **FR-P3-006** | Timer and scheduling system | High | Timers table; indexed `trigger_at` column; worker-based (not cron); retry logic |
| **FR-P3-007** | Advanced reporting | High | Conversion funnels, cohort analysis, attribution modeling; export to CSV, PDF |
| **FR-P3-008** | GDPR compliance features | High | Consent management, data export, deletion workflows, audit trail |
| **FR-P3-009** | A/B testing engine | High | Variant creation, traffic splitting, statistical analysis, auto-winner selection |
| **FR-P3-010** | Behavioral triggers | High | Event-based campaign triggers; conditional logic; cooldown periods |
| **FR-P3-011** | Drip campaign automation | High | Multi-step sequences; time-based delays; conditional branching |
| **FR-P3-012** | API rate limiting and throttling | Medium | Per-channel rate limits; backoff strategies; queue management |

### FR-EXT: Extensibility Features

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-EXT-001** | Custom channel plugins | High | `ChannelContract` interface: `execute()`, `validate()`, `compensate()` methods |
| **FR-EXT-002** | Custom condition evaluators | High | `ConditionEvaluatorContract`: `evaluate($context)` method |
| **FR-EXT-003** | Custom approval strategies | High | `ApprovalStrategyContract`: `canProceed($approvals)` method |
| **FR-EXT-004** | Custom trigger handlers | Medium | `TriggerContract`: webhook, event-based, scheduled triggers |
| **FR-EXT-005** | Custom storage backends | Low | `StorageContract`: Eloquent, Redis, MongoDB adapters |
| **FR-EXT-006** | Custom metric calculators | Medium | `MetricCalculatorContract`: define and compute custom KPIs |

---

## Non-Functional Requirements

### Performance Requirements

| ID | Requirement | Target | Measurement Context |
|----|-------------|--------|---------------------|
| **PR-001** | Campaign launch time | < 100ms | Excluding async channel execution |
| **PR-002** | Dashboard query (1,000 active campaigns) | < 500ms | With proper database indexing |
| **PR-003** | ROI calculation (10,000 campaigns) | < 2s | Using timers table with indexed queries |
| **PR-004** | Campaign initialization | < 200ms | Including validation |
| **PR-005** | Parallel channel synchronization (10 channels) | < 100ms | Token-based coordination overhead |
| **PR-006** | Lead scoring update | < 50ms | Per lead, excluding external API calls |
| **PR-007** | Segment recalculation (100,000 leads) | < 10s | Background job, not blocking |

### Security Requirements

| ID | Requirement | Scope | Implementation |
|----|-------------|-------|----------------|
| **SR-001** | Prevent unauthorized campaign actions | Engine level | Guard conditions + authorization gates |
| **SR-002** | Sanitize user expressions | Condition evaluator | No code injection, whitelist operators |
| **SR-003** | Multi-tenant data isolation | Database queries | Automatic tenant_id scoping |
| **SR-004** | Sandbox plugin execution | Plugin system | Isolated execution context, resource limits |
| **SR-005** | Audit all campaign changes | Database | Immutable audit log table |
| **SR-006** | RBAC integration | Authorization | Laravel permissions/policies integration |
| **SR-007** | API authentication | REST/GraphQL APIs | Sanctum token-based auth |
| **SR-008** | Rate limiting per tenant | API layer | Configurable limits, Redis-backed |

### Reliability Requirements

| ID | Requirement | Implementation |
|----|-------------|----------------|
| **REL-001** | ACID transactions for state changes | Database transactions for all state modifications |
| **REL-002** | Failed channels don't block campaign | Queue-based execution with retry logic |
| **REL-003** | Concurrency control | Optimistic locking with version numbers |
| **REL-004** | Data corruption protection | Schema validation before persisting |
| **REL-005** | Retry transient failures | Exponential backoff with configurable max attempts |
| **REL-006** | Idempotent operations | Duplicate detection using unique keys |
| **REL-007** | Dead letter queue | Failed messages stored for manual review |

### Scalability Requirements

| ID | Requirement | Implementation |
|----|-------------|----------------|
| **SCL-001** | Horizontal scaling | Stateless services, Redis-backed queues |
| **SCL-002** | Handle 100,000+ active campaigns | Optimized queries, database indexing |
| **SCL-003** | Handle 1,000,000+ leads | Partitioned tables, efficient segmentation |
| **SCL-004** | Concurrent campaign processing | Queue workers with configurable concurrency |
| **SCL-005** | Efficient query performance | Database indexes on: tenant_id, status, created_at, trigger_at |
| **SCL-006** | Caching strategy | Redis cache for: segments, templates, metrics (TTL: 5-60 minutes) |

### Maintainability Requirements

| ID | Requirement | Implementation |
|----|-------------|----------------|
| **MAINT-001** | Framework-agnostic core | No Laravel dependencies in `src/Core/` |
| **MAINT-002** | Laravel adapter separation | All framework code in `src/Adapters/Laravel/` |
| **MAINT-003** | Test coverage | > 80% overall, > 90% for core engine |
| **MAINT-004** | Module independence | Campaign, channel, lead, analytics modules are independent |
| **MAINT-005** | Documentation | PHPDoc for all public methods, README with examples |
| **MAINT-006** | Code style | PSR-12, Laravel Pint formatting |

---

## Data Model & Architecture

### Core Marketing Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `marketing_definitions` | Campaign templates and schemas | `id`, `name`, `schema` (JSON), `active`, `version` |
| `marketing_campaigns` | Active campaign instances | `id`, `subject_type`, `subject_id`, `definition_id`, `state`, `data` (JSON), `started_at`, `completed_at` |
| `marketing_history` | Audit trail | `id`, `campaign_id`, `event`, `before` (JSON), `after` (JSON), `actor_id`, `payload` (JSON) |

### Lead & Engagement Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `marketing_leads` | Lead contacts | `id`, `email`, `name`, `score`, `segment_ids` (JSON), `metadata` (JSON) |
| `marketing_segments` | Audience segments | `id`, `name`, `conditions` (JSON), `lead_count`, `updated_at` |
| `marketing_engagements` | Interaction tracking | `id`, `campaign_id`, `lead_id`, `type`, `channel`, `metadata` (JSON), `tracked_at` |

### Automation Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `marketing_timers` | Scheduled events | `id`, `campaign_id`, `type`, `trigger_at`, `payload` (JSON), `status` |
| `marketing_roi` | Budget tracking | `id`, `campaign_id`, `budget`, `spent`, `revenue`, `started_at`, `breach_at` |
| `marketing_escalations` | Escalation history | `id`, `entity_type`, `entity_id`, `level`, `from_user_id`, `to_user_id`, `reason` |
| `marketing_delegations` | Temporary delegation | `id`, `delegator_id`, `delegatee_id`, `start_date`, `end_date`, `scope` |

### Channel & Template Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `marketing_channels` | Channel configurations | `id`, `type`, `name`, `config` (JSON), `enabled` |
| `marketing_templates` | Campaign templates | `id`, `name`, `type`, `content` (JSON), `variables` (JSON), `version` |
| `marketing_variants` | A/B test variants | `id`, `campaign_id`, `name`, `config` (JSON), `traffic_percentage`, `conversions` |

### Compliance Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `marketing_consents` | GDPR consent tracking | `id`, `lead_id`, `type`, `granted_at`, `revoked_at`, `ip_address` |
| `marketing_suppressions` | Unsubscribe/blocklist | `id`, `email`, `type`, `reason`, `suppressed_at` |

### JSON Schema Specifications

#### Phase 1: In-Model Configuration

```php
use Nexus\Marketing\Traits\HasMarketing;

class Product extends Model
{
    use HasMarketing;
    
    public function marketingConfig(): array
    {
        return [
            'campaigns' => [
                'product_launch' => [
                    'name' => 'Product Launch Campaign',
                    'channels' => ['email', 'social'],
                    'budget' => 5000,
                ],
            ],
        ];
    }
}

// Usage
$product->marketing()->launchCampaign('product_launch', [
    'target_segment' => 'early_adopters',
    'start_date' => now()->addDays(7),
]);
```

#### Phase 2: Database-Driven Campaigns

```json
{
  "id": "email-drip-sequence",
  "name": "Welcome Drip Campaign",
  "version": "1.0.0",
  "dataSchema": {
    "subscriber_email": { "type": "string", "required": true },
    "subscriber_name": { "type": "string", "required": true },
    "subscription_date": { "type": "date", "required": true }
  },
  "stages": ["queued", "active", "paused", "completed", "cancelled"],
  "channels": {
    "email": {
      "provider": "smtp",
      "template": "welcome-series"
    }
  },
  "transitions": {
    "activate": {
      "from": "queued",
      "to": "active",
      "condition": "data.subscription_date <= today()"
    },
    "complete": {
      "from": "active",
      "to": "completed",
      "condition": "data.emails_sent >= 5"
    }
  },
  "automation": {
    "sequence": [
      { "delay": "1 hour", "action": "send_email", "template": "welcome-1" },
      { "delay": "2 days", "action": "send_email", "template": "welcome-2" },
      { "delay": "5 days", "action": "send_email", "template": "welcome-3" }
    ]
  }
}
```

#### Phase 3: Enterprise Automation

```json
{
  "id": "enterprise-campaign",
  "name": "Multi-Channel Lead Nurturing",
  "version": "2.0.0",
  "roi": {
    "budget": 10000,
    "target_revenue": 50000,
    "threshold": "2x",
    "breach_action": "pause_and_alert"
  },
  "targeting": {
    "segment": {
      "conditions": [
        { "field": "lead_score", "operator": ">", "value": 50 },
        { "field": "industry", "operator": "in", "value": ["tech", "finance"] }
      ],
      "logic": "AND"
    }
  },
  "channels": {
    "email": { "enabled": true, "priority": 1 },
    "sms": { "enabled": true, "priority": 2 },
    "webhook": { "enabled": true, "url": "https://api.example.com/notify" }
  },
  "automation": {
    "escalation": [
      {
        "after": "24 hours",
        "condition": "data.engagement_rate < 0.1",
        "action": "reassign_to_manager"
      }
    ],
    "triggers": [
      {
        "event": "lead_score_increased",
        "condition": "data.lead_score >= 80",
        "action": "send_to_sales_team"
      }
    ]
  },
  "compliance": {
    "gdpr": true,
    "consent_required": true,
    "data_retention_days": 365
  }
}
```

### Built-in Condition Operators

- **Comparison**: `==`, `!=`, `>`, `<`, `>=`, `<=`
- **Logical**: `AND`, `OR`, `NOT`
- **Collection**: `in`, `not_in`, `contains`
- **String**: `starts_with`, `ends_with`, `matches` (regex)
- **Date**: `before`, `after`, `between`
- **Custom**: Extensible via `ConditionEvaluatorContract`

### Built-in Approval Strategies

- **Unanimous**: All approvers must approve
- **Majority**: > 50% approval required
- **Quorum**: Configurable threshold (e.g., 2/3, 3/4)
- **Single**: Any single approver can approve
- **Custom**: Implement `ApprovalStrategyContract`

---

## API Specifications

### REST API Endpoints

#### Campaign Management

```
POST   /api/v1/campaigns                    Create campaign
GET    /api/v1/campaigns                    List campaigns (paginated, filtered)
GET    /api/v1/campaigns/{id}               Get campaign details
PATCH  /api/v1/campaigns/{id}               Update campaign
DELETE /api/v1/campaigns/{id}               Cancel/delete campaign
POST   /api/v1/campaigns/{id}/launch        Launch campaign
POST   /api/v1/campaigns/{id}/pause         Pause campaign
POST   /api/v1/campaigns/{id}/resume        Resume campaign
GET    /api/v1/campaigns/{id}/analytics     Get campaign analytics
```

#### Lead Management

```
POST   /api/v1/leads                        Create lead
GET    /api/v1/leads                        List leads (paginated, filtered)
GET    /api/v1/leads/{id}                   Get lead details
PATCH  /api/v1/leads/{id}                   Update lead
DELETE /api/v1/leads/{id}                   Delete lead (GDPR-compliant)
POST   /api/v1/leads/{id}/score             Update lead score
GET    /api/v1/leads/{id}/engagements       Get lead engagement history
```

#### Segment Management

```
POST   /api/v1/segments                     Create segment
GET    /api/v1/segments                     List segments
GET    /api/v1/segments/{id}                Get segment details
PATCH  /api/v1/segments/{id}                Update segment conditions
DELETE /api/v1/segments/{id}                Delete segment
POST   /api/v1/segments/{id}/recalculate    Recalculate segment membership
GET    /api/v1/segments/{id}/leads          Get leads in segment
```

#### Analytics & Reporting

```
GET    /api/v1/analytics/dashboard          Get dashboard metrics
GET    /api/v1/analytics/campaigns/{id}     Campaign-specific analytics
GET    /api/v1/analytics/leads/{id}         Lead-specific analytics
POST   /api/v1/analytics/reports            Generate custom report
GET    /api/v1/analytics/reports/{id}       Download generated report
```

#### Template Management

```
POST   /api/v1/templates                    Create template
GET    /api/v1/templates                    List templates
GET    /api/v1/templates/{id}               Get template
PATCH  /api/v1/templates/{id}               Update template
DELETE /api/v1/templates/{id}               Delete template
POST   /api/v1/templates/{id}/duplicate     Duplicate template
```

### GraphQL Schema

```graphql
type Campaign {
  id: ID!
  name: String!
  status: CampaignStatus!
  startedAt: DateTime
  completedAt: DateTime
  budget: Float
  spent: Float
  revenue: Float
  roi: Float
  leads: [Lead!]!
  engagements(type: EngagementType): [Engagement!]!
  analytics: CampaignAnalytics!
}

type Lead {
  id: ID!
  email: String!
  name: String
  score: Int!
  segments: [Segment!]!
  engagements: [Engagement!]!
  metadata: JSON
}

type Segment {
  id: ID!
  name: String!
  conditions: JSON!
  leadCount: Int!
  leads(limit: Int, offset: Int): [Lead!]!
}

type Query {
  campaigns(status: CampaignStatus, limit: Int, offset: Int): [Campaign!]!
  campaign(id: ID!): Campaign
  leads(segment: ID, score: Int, limit: Int, offset: Int): [Lead!]!
  lead(id: ID!): Lead
  segments: [Segment!]!
  analytics: DashboardAnalytics!
}

type Mutation {
  createCampaign(input: CreateCampaignInput!): Campaign!
  launchCampaign(id: ID!): Campaign!
  pauseCampaign(id: ID!): Campaign!
  createLead(input: CreateLeadInput!): Lead!
  updateLeadScore(id: ID!, score: Int!): Lead!
  createSegment(input: CreateSegmentInput!): Segment!
}

enum CampaignStatus {
  DRAFT
  QUEUED
  ACTIVE
  PAUSED
  COMPLETED
  CANCELLED
}

enum EngagementType {
  EMAIL_SENT
  EMAIL_OPENED
  EMAIL_CLICKED
  SMS_SENT
  SMS_DELIVERED
  FORM_SUBMITTED
  PAGE_VISITED
}
```

### Webhook Events

```php
// Campaign events
marketing.campaign.created
marketing.campaign.launched
marketing.campaign.paused
marketing.campaign.resumed
marketing.campaign.completed
marketing.campaign.cancelled

// Lead events
marketing.lead.created
marketing.lead.updated
marketing.lead.scored
marketing.lead.segment_changed

// Engagement events
marketing.engagement.email_sent
marketing.engagement.email_opened
marketing.engagement.email_clicked
marketing.engagement.sms_delivered
marketing.engagement.conversion

// ROI events
marketing.roi.budget_exceeded
marketing.roi.target_achieved
marketing.roi.milestone_reached
```

---

## Development Phases

### Phase 1: Basic Campaign Tracking (Weeks 1-3)

**Goal**: Enable developers to add marketing capabilities with minimal setup

**Deliverables**:
- ✅ `HasMarketing` trait implementation
- ✅ In-model campaign parser (array → internal representation)
- ✅ Basic campaign engine (launch, pause, complete)
- ✅ Event system (CampaignLaunched, CampaignCompleted)
- ✅ Unit tests (engine logic, validation)
- ✅ Feature tests (Phase 1 user stories)
- ✅ Documentation (README, quick start guide)

**Success Criteria**:
- Trait can be added to model in < 5 minutes
- All Phase 1 user stories pass acceptance tests
- Test coverage > 85%

### Phase 2: Marketing Automation (Weeks 4-8)

**Goal**: Add database-driven campaigns with multi-channel support

**Deliverables**:
- ✅ Database migrations (all marketing tables)
- ✅ Campaign lifecycle state machine
- ✅ Multi-channel execution engine
- ✅ Condition evaluator (targeting)
- ✅ Approval workflow system
- ✅ Dashboard API service
- ✅ Lead management system
- ✅ Segment management system
- ✅ Integration tests (database persistence)
- ✅ API documentation (OpenAPI/Swagger)

**Success Criteria**:
- Seamless upgrade from Phase 1 without code changes
- All Phase 2 user stories pass acceptance tests
- Performance: dashboard loads in < 500ms with 1,000 campaigns
- Test coverage > 80%

### Phase 3: Enterprise Features (Weeks 9-12)

**Goal**: Add enterprise-grade automation and compliance

**Deliverables**:
- ✅ Timer/scheduling system
- ✅ ROI tracking engine
- ✅ Campaign delegation system
- ✅ Rollback/compensation logic
- ✅ Custom metrics configuration
- ✅ GDPR compliance features
- ✅ A/B testing engine
- ✅ Advanced reporting
- ✅ Load tests (100,000+ campaigns)
- ✅ Security audit

**Success Criteria**:
- All Phase 3 user stories pass acceptance tests
- Performance: ROI check on 10,000 campaigns in < 2s
- Security: passes OWASP Top 10 audit
- GDPR: data export/deletion workflows implemented
- Test coverage > 80%

### Phase 4: Extensibility & Polish (Weeks 13-14)

**Goal**: Finalize plugin system and documentation

**Deliverables**:
- ✅ Plugin development guide
- ✅ Custom channel examples (Slack, Discord)
- ✅ Custom condition examples
- ✅ Video tutorials
- ✅ Migration guides
- ✅ Performance optimization
- ✅ Final security audit
- ✅ Package publication preparation

**Success Criteria**:
- Plugin development guide published
- At least 2 custom channel examples
- All documentation reviewed and approved
- Package ready for Packagist publication

### Phase 5: Launch & Iteration (Weeks 15-16)

**Goal**: Public release and community engagement

**Deliverables**:
- ✅ Package published to Packagist
- ✅ Documentation website live
- ✅ Blog post announcement
- ✅ Community Discord/Slack setup
- ✅ Issue tracking system configured
- ✅ Beta testing with early adopters

**Success Criteria**:
- Package available via `composer require nexus/marketing`
- Documentation site accessible
- First 10 beta testers onboarded
- Community support channels active

---

## Testing Strategy

### Unit Tests

**Target**: > 90% coverage for core engine

**Test Categories**:
- Campaign engine logic (state transitions, validation)
- Condition evaluator (all operators, edge cases)
- Approval strategies (unanimous, majority, quorum)
- ROI calculator (budget tracking, breach detection)
- Timer system (scheduling, execution)
- Data validation (schema validation, type checking)

**Example Test Structure**:
```php
test('campaign transitions from draft to active when launched', function () {
    $campaign = Campaign::factory()->draft()->create();
    
    $engine = app(MarketingEngine::class);
    $engine->launchCampaign($campaign);
    
    expect($campaign->fresh()->status)->toBe(CampaignStatus::ACTIVE);
});
```

### Feature Tests

**Target**: 100% coverage of user stories

**Test Categories**:
- Phase 1: trait integration, basic operations
- Phase 2: database campaigns, multi-channel execution
- Phase 3: escalation, ROI tracking, delegation

**Example Test Structure**:
```php
test('US-001: developer can add HasMarketing trait to model', function () {
    $product = Product::factory()->create();
    
    expect($product)->toHaveMethod('marketing');
    expect($product->marketing())->toBeInstanceOf(MarketingManager::class);
});

test('US-003: developer can launch campaign via marketing() method', function () {
    $product = Product::factory()->create();
    
    $product->marketing()->launchCampaign('product_launch', [
        'target_segment' => 'early_adopters',
    ]);
    
    expect($product->marketing()->activeCampaigns())->toHaveCount(1);
});
```

### Integration Tests

**Test Categories**:
- Laravel framework integration (Eloquent, Queue, Cache)
- Multi-tenancy integration (if nexus-tenancy present)
- Audit logging integration (if nexus-audit-log present)
- External services (email providers, SMS gateways)

**Example Test Structure**:
```php
test('campaign launches trigger queued channel execution', function () {
    Queue::fake();
    
    $campaign = Campaign::factory()->create([
        'channels' => ['email', 'sms'],
    ]);
    
    app(MarketingEngine::class)->launchCampaign($campaign);
    
    Queue::assertPushed(SendEmailJob::class);
    Queue::assertPushed(SendSmsJob::class);
});
```

### Acceptance Tests

**Goal**: Validate all user stories

**Test Matrix**:
- All user stories (US-001 through US-027)
- Performance requirements (< 5 minute hello world)
- Upgrade path (Phase 1 → Phase 2 → Phase 3 without changes)

### Load Tests

**Scenarios**:
- 100,000 active campaigns
- 1,000,000 leads
- 10,000 concurrent campaign launches
- 1,000 campaigns/second ROI checks

**Tools**: Apache JMeter, Laravel Dusk, custom load scripts

---

## Dependencies

### Required Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| PHP | >= 8.2 | Modern PHP features (enums, readonly, etc.) |
| Laravel | >= 11.x | Framework integration (optional for core) |

### Optional Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| nexus-tenancy | ^1.0 | Multi-tenancy support |
| nexus-audit-log | ^1.0 | Audit trail integration |
| Redis | ^7.0 | Caching and queue backend |
| MySQL | >= 8.0 | Primary database (or PostgreSQL >= 12, SQLite >= 3.35) |

### Development Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| pestphp/pest | ^3.0 | Testing framework |
| laravel/pint | ^1.0 | Code style enforcement |
| phpstan/phpstan | ^1.0 | Static analysis |
| nunomaduro/larastan | ^2.0 | Laravel-specific PHPStan rules |

---

## Success Metrics

### Adoption Metrics

| Metric | Target | Timeframe | Measurement |
|--------|--------|-----------|-------------|
| Total installations | > 2,000 | 6 months | Packagist downloads |
| Active users | > 500 | 6 months | Unique tenants with active campaigns |
| Phase 2 adoption rate | > 10% | 6 months | Users upgrading from Phase 1 |
| Phase 3 adoption rate | > 5% | 6 months | Users with enterprise features enabled |

### Quality Metrics

| Metric | Target | Timeframe | Measurement |
|--------|--------|-----------|-------------|
| Test coverage | > 85% | Ongoing | PHPUnit/Pest coverage reports |
| P0 bugs | < 5 | 6 months | GitHub issues tagged P0 |
| P1 bugs | < 20 | 6 months | GitHub issues tagged P1 |
| Security vulnerabilities | 0 | Ongoing | Security audit results |

### Performance Metrics

| Metric | Target | Timeframe | Measurement |
|--------|--------|-----------|-------------|
| Hello World time | < 5 minutes | Ongoing | User onboarding surveys |
| Dashboard load time | < 500ms | Ongoing | APM monitoring (New Relic, etc.) |
| Campaign launch time | < 100ms | Ongoing | Application logging |
| ROI check time (10K campaigns) | < 2s | Ongoing | Performance tests |

### Developer Experience Metrics

| Metric | Target | Timeframe | Measurement |
|--------|--------|-----------|-------------|
| Documentation quality | < 10 questions/week | 3 months | GitHub issues/Discord questions |
| API clarity | > 4.5/5 rating | 6 months | Developer surveys |
| Plugin development time | < 2 hours | 6 months | Developer surveys + case studies |

---

## Glossary

| Term | Definition |
|------|------------|
| **Campaign** | A coordinated marketing effort targeting a specific audience with defined goals |
| **Channel** | A communication medium (email, SMS, social media, webhook, etc.) |
| **Lead** | A potential customer contact tracked in the system |
| **Segment** | A group of leads matching specific criteria |
| **Engagement** | An interaction event (email open, click, conversion, etc.) |
| **Phase 1** | Basic campaign tracking with in-model configuration |
| **Phase 2** | Database-driven campaigns with multi-channel automation |
| **Phase 3** | Enterprise features (escalation, ROI, delegation, compliance) |
| **ROI** | Return on Investment - revenue generated vs. budget spent |
| **Escalation** | Automatic campaign optimization when performance thresholds are not met |
| **Delegation** | Temporary campaign ownership transfer during absences |
| **Compensation** | Rollback actions executed when a campaign fails |
| **Drip Campaign** | Multi-step automated email sequence with time delays |
| **Lead Scoring** | Numerical value assigned to leads based on engagement and behavior |
| **A/B Testing** | Comparing campaign variants to determine best performer |
| **Behavioral Trigger** | Automatic campaign action based on user behavior |
| **GDPR** | General Data Protection Regulation - EU privacy law |
| **CAN-SPAM** | Controlling the Assault of Non-Solicited Pornography And Marketing Act |

---

## Package Structure

```
packages/nexus-marketing/
├── src/
│   ├── Core/
│   │   ├── Contracts/
│   │   │   ├── MarketingEngineContract.php
│   │   │   ├── ChannelContract.php
│   │   │   ├── ConditionEvaluatorContract.php
│   │   │   ├── ApprovalStrategyContract.php
│   │   │   ├── LeadRepositoryContract.php
│   │   │   ├── CampaignRepositoryContract.php
│   │   ├── Engine/
│   │   │   ├── MarketingEngine.php
│   │   │   ├── CampaignManager.php
│   │   │   ├── ChannelOrchestrator.php
│   │   ├── Services/
│   │   │   ├── CampaignService.php
│   │   │   ├── LeadService.php
│   │   │   ├── SegmentService.php
│   │   │   ├── AnalyticsService.php
│   │   │   ├── EscalationService.php
│   │   │   ├── RoiService.php
│   │   ├── DTOs/
│   │   │   ├── CampaignDefinition.php
│   │   │   ├── CampaignInstance.php
│   │   │   ├── LeadData.php
│   │   │   ├── EngagementData.php
│   │   ├── Enums/
│   │   │   ├── CampaignStatus.php
│   │   │   ├── ChannelType.php
│   │   │   ├── EngagementType.php
│   ├── Channels/
│   │   ├── EmailChannel.php
│   │   ├── SmsChannel.php
│   │   ├── WebhookChannel.php
│   │   ├── SocialMediaChannel.php
│   ├── Conditions/
│   │   ├── ExpressionEvaluator.php
│   │   ├── SegmentMatcher.php
│   ├── Strategies/
│   │   ├── UnanimousStrategy.php
│   │   ├── MajorityStrategy.php
│   │   ├── QuorumStrategy.php
│   ├── Timers/
│   │   ├── TimerQueue.php
│   │   ├── TimerProcessor.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── CampaignController.php
│   │   │   ├── LeadController.php
│   │   │   ├── SegmentController.php
│   │   │   ├── AnalyticsController.php
│   │   ├── Resources/
│   │   │   ├── CampaignResource.php
│   │   │   ├── LeadResource.php
│   │   │   ├── EngagementResource.php
│   │   ├── Requests/
│   │   │   ├── CreateCampaignRequest.php
│   │   │   ├── UpdateCampaignRequest.php
│   │   │   ├── CreateLeadRequest.php
│   ├── Adapters/
│   │   └── Laravel/
│   │       ├── Traits/
│   │       │   └── HasMarketing.php
│   │       ├── Models/
│   │       │   ├── Campaign.php
│   │       │   ├── Lead.php
│   │       │   ├── Segment.php
│   │       │   ├── Engagement.php
│   │       ├── Repositories/
│   │       │   ├── CampaignRepository.php
│   │       │   ├── LeadRepository.php
│   │       │   ├── SegmentRepository.php
│   │       ├── Services/
│   │       │   ├── MarketingDashboard.php
│   │       ├── Commands/
│   │       │   ├── ProcessTimersCommand.php
│   │       │   ├── RecalculateSegmentsCommand.php
│   │       │   ├── ProcessEscalationsCommand.php
│   │       └── MarketingServiceProvider.php
│   └── Events/
│       ├── CampaignLaunched.php
│       ├── CampaignCompleted.php
│       ├── CampaignPaused.php
│       ├── LeadCreated.php
│       ├── LeadScored.php
│       ├── EngagementTracked.php
│       ├── RoiBudgetExceeded.php
├── database/
│   └── migrations/
│       ├── 2025_11_15_000001_create_marketing_campaigns_table.php
│       ├── 2025_11_15_000002_create_marketing_leads_table.php
│       ├── 2025_11_15_000003_create_marketing_segments_table.php
│       ├── 2025_11_15_000004_create_marketing_engagements_table.php
│       ├── 2025_11_15_000005_create_marketing_timers_table.php
│       ├── 2025_11_15_000006_create_marketing_roi_table.php
│       ├── 2025_11_15_000007_create_marketing_consents_table.php
├── config/
│   └── marketing.php
├── routes/
│   ├── api.php
│   └── web.php
├── tests/
│   ├── Unit/
│   │   ├── CampaignEngineTest.php
│   │   ├── ConditionEvaluatorTest.php
│   │   ├── ApprovalStrategyTest.php
│   │   ├── LeadScoringTest.php
│   ├── Feature/
│   │   ├── Phase1/
│   │   │   ├── BasicCampaignTrackingTest.php
│   │   │   ├── InModelConfigurationTest.php
│   │   ├── Phase2/
│   │   │   ├── DatabaseCampaignsTest.php
│   │   │   ├── MultiChannelExecutionTest.php
│   │   │   ├── LeadManagementTest.php
│   │   ├── Phase3/
│   │   │   ├── RoiTrackingTest.php
│   │   │   ├── EscalationTest.php
│   │   │   ├── DelegationTest.php
│   │   │   ├── GdprComplianceTest.php
│   └── Integration/
│       ├── LaravelIntegrationTest.php
│       ├── TenancyIntegrationTest.php
│       ├── AuditLogIntegrationTest.php
├── docs/
│   ├── README.md
│   ├── REQUIREMENTS.md (this file)
│   ├── QUICKSTART.md
│   ├── PHASE_1_GUIDE.md
│   ├── PHASE_2_GUIDE.md
│   ├── PHASE_3_GUIDE.md
│   ├── PLUGIN_DEVELOPMENT.md
│   ├── API_REFERENCE.md
│   ├── MIGRATION_GUIDE.md
├── composer.json
├── phpunit.xml
├── .gitignore
└── LICENSE
```

---

## Business Rules

| ID | Rule | Scope |
|----|------|-------|
| **BR-001** | Campaigns cannot target same lead more than once per day (configurable) | Phase 2+ |
| **BR-002** | All state changes must be ACID transactions | All phases |
| **BR-003** | Low ROI campaigns auto-escalate after configured threshold | Phase 3 |
| **BR-004** | Compensation actions execute in reverse order of original actions | Phase 3 |
| **BR-005** | Delegation chain maximum depth: 3 levels | Phase 3 |
| **BR-006** | Phase 1 configurations remain compatible with Phase 2/3 | All phases |
| **BR-007** | One marketing instance per model/entity | All phases |
| **BR-008** | Parallel channels must all complete before proceeding | Phase 2+ |
| **BR-009** | Campaign assignment checks delegation chain first | Phase 3 |
| **BR-010** | Multi-team approval uses configured strategy | Phase 2+ |
| **BR-011** | GDPR consent required for EU leads | Phase 3 |
| **BR-012** | Unsubscribe respected across all campaigns | Phase 2+ |
| **BR-013** | Lead scoring updates trigger segment recalculation | Phase 2+ |
| **BR-014** | A/B test traffic distribution must total 100% | Phase 3 |

---

**Document Version**: 1.0.0  
**Last Updated**: November 15, 2025  
**Status**: Ready for Implementation  
**Maintainer**: Nexus ERP Core Team  

---

## Document Changelog

| Version | Date | Changes | Author |
|---------|------|---------|--------|
| 1.0.0 | 2025-11-15 | Initial comprehensive requirements document | Nexus Team |

---

**End of Requirements Document**
