# Nexus ERP Core Orchestrator Requirements

**Version:** 1.0.0  
**Last Updated:** November 15, 2025  
**Status:** Initial Requirements - Orchestration Layer

---

## Executive Summary

**Nexus\Erp** is the **orchestration layer and headless API presentation** for the Nexus ERP ecosystem. It coordinates atomic business domain packages, provides unified RESTful APIs, manages cross-package workflows, enforces feature toggling, and handles system-wide concerns like authentication, authorization, and tenant context management.

### The Orchestration Challenge

Unlike atomic packages that focus on single domains, the ERP orchestrator must:

1. **Coordinate Multiple Packages** - Create purchase orders that trigger inventory reservations, accounting entries, and approval workflows across nexus-procurement, nexus-inventory, nexus-accounting, and nexus-workflow.

2. **Provide Unified API** - Present a coherent API to frontend applications (like Edward CLI) that abstracts the complexity of multiple underlying packages.

3. **Manage Feature Availability** - Control which features are available based on tenant licenses, user permissions, and system configuration.

4. **Handle Cross-Cutting Concerns** - Authentication, authorization, rate limiting, API versioning, and audit logging that affect all packages.

5. **Maintain Package Independence** - Ensure atomic packages remain decoupled while orchestrating their interactions.

### Core Philosophy

1. **Thin Orchestration Layer** - Business logic lives in atomic packages; orchestrator only coordinates
2. **Contract-Driven Integration** - All package interactions via interfaces, never concrete implementations
3. **Event-Driven Coordination** - Cross-package workflows triggered by domain events
4. **Progressive Disclosure** - Features enabled incrementally based on tenant needs
5. **API-First Design** - Complete ERP functionality accessible via RESTful endpoints

---

## Architectural Position

### What Nexus\Erp IS:

| Responsibility | Description |
|----------------|-------------|
| **API Presentation Layer** | Exposes RESTful endpoints (GET /api/v1/projects, POST /api/v1/purchase-orders) |
| **Action Orchestrator** | Coordinates cross-package workflows via Laravel Actions |
| **Contract Binder** | Maps interfaces to implementations in service container |
| **Feature Toggle Engine** | Manages feature availability per tenant/user |
| **Authentication Gateway** | API token management via Laravel Sanctum |
| **Authorization Enforcer** | RBAC implementation via Spatie Permission |
| **Event Coordinator** | Listens to domain events and orchestrates responses |
| **Tenant Context Manager** | Ensures all operations are tenant-scoped |

### What Nexus\Erp IS NOT:

| Not Responsible For | Owned By |
|---------------------|----------|
| ❌ Vendor master data management | nexus-procurement |
| ❌ Work order execution logic | nexus-manufacturing |
| ❌ Project costing algorithms | nexus-project-management |
| ❌ General ledger posting rules | nexus-accounting |
| ❌ Inventory valuation methods | nexus-inventory |
| ❌ Serial number generation patterns | nexus-sequencing |
| ❌ Multi-tenant data isolation | nexus-tenancy |
| ❌ Workflow state machine definitions | nexus-workflow |

**Rule:** If logic belongs to a single domain, it goes in the atomic package. If it coordinates multiple domains, it goes in Nexus\Erp.

---

## Personas & User Stories

### Personas

| ID | Persona | Role | Primary Goal |
|-----|---------|------|--------------|
| **P1** | System Integrator | External developer | "Build custom frontends that consume Nexus ERP APIs" |
| **P2** | ERP Administrator | Internal IT team | "Configure and manage the ERP system for our organization" |
| **P3** | API Consumer | Machine-to-machine client | "Integrate external systems with Nexus ERP via REST APIs" |
| **P4** | License Manager | Software vendor admin | "Control feature availability based on customer licenses" |
| **P5** | Tenant Owner | Customer organization owner | "Enable/disable modules based on business needs" |
| **P6** | Package Developer | Core contributor | "Develop new atomic packages that integrate with orchestrator" |

### User Stories

#### Level 1: Core Orchestration (Essential)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-001** | P1 | As a system integrator, I want a unified REST API that abstracts underlying package complexity | **High** |
| **US-002** | P2 | As an ERP admin, I want to authenticate via API tokens with configurable expiration | **High** |
| **US-003** | P2 | As an ERP admin, I want role-based access control to restrict API endpoint access | **High** |
| **US-004** | P3 | As an API consumer, I want comprehensive API documentation (OpenAPI/Swagger) | **High** |
| **US-005** | P5 | As a tenant owner, I want to enable/disable business modules (procurement, manufacturing) | **High** |
| **US-006** | P1 | As a system integrator, I want cross-package workflows (create PO → update inventory → post to GL) | **High** |
| **US-007** | P2 | As an ERP admin, I want to view audit logs for all API operations | **High** |

#### Level 2: Advanced Orchestration (Progressive Features)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-010** | P4 | As a license manager, I want to restrict features based on license tiers (Basic/Pro/Enterprise) | **High** |
| **US-011** | P5 | As a tenant owner, I want to enable/disable specific features within modules | Medium |
| **US-012** | P1 | As a system integrator, I want webhooks to receive real-time notifications of ERP events | **High** |
| **US-013** | P3 | As an API consumer, I want rate limiting to prevent abuse | **High** |
| **US-014** | P2 | As an ERP admin, I want API versioning to maintain backward compatibility | **High** |
| **US-015** | P6 | As a package developer, I want to register new packages via service providers | Medium |
| **US-016** | P2 | As an ERP admin, I want to configure cross-package event listeners | Medium |

#### Level 3: Enterprise Orchestration (Advanced)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-020** | P1 | As a system integrator, I want GraphQL API for flexible data querying | Medium |
| **US-021** | P3 | As an API consumer, I want bulk operations (create 1000 POs in one call) | Medium |
| **US-022** | P2 | As an ERP admin, I want API usage analytics and performance monitoring | **High** |
| **US-023** | P4 | As a license manager, I want usage-based billing metrics (API calls, storage) | Medium |
| **US-024** | P2 | As an ERP admin, I want scheduled tasks orchestration (nightly reports, batch jobs) | **High** |
| **US-025** | P1 | As a system integrator, I want server-sent events (SSE) for real-time updates | Low |

---

## Functional Requirements

### FR-L1: Level 1 - Core Orchestration (Essential MVP)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L1-001** | Unified RESTful API | **High** | • All atomic package features accessible via REST<br>• Consistent response format (JSON)<br>• Standard HTTP status codes<br>• Pagination for list endpoints<br>• Filtering and sorting support |
| **FR-L1-002** | Authentication via API tokens | **High** | • Token generation via Laravel Sanctum<br>• Configurable token expiration (default: 30 days)<br>• Device-specific tokens<br>• Token revocation support<br>• Remember me functionality |
| **FR-L1-003** | Role-based access control (RBAC) | **High** | • User→Role→Permission hierarchy<br>• Endpoint-level permission checks<br>• Tenant-scoped roles<br>• Integration with Spatie Permission<br>• Default roles (Admin, Manager, User, Viewer) |
| **FR-L1-004** | Tenant context management | **High** | • Automatic tenant context from token<br>• Middleware enforces tenant scoping<br>• All queries filtered by tenant_id<br>• Cross-tenant access prevention<br>• Tenant switching for super-admins |
| **FR-L1-005** | Cross-package action orchestration | **High** | • Laravel Actions pattern implementation<br>• Actions callable via API, CLI, Queue, Events<br>• Example: CreatePurchaseOrderAction orchestrates nexus-procurement + nexus-inventory + nexus-accounting<br>• Transactional integrity across packages |
| **FR-L1-006** | Event-driven coordination | **High** | • Listen to domain events from atomic packages<br>• Orchestrator listeners trigger cross-package actions<br>• Example: ProjectCompleted → GenerateInvoice → PostToGL<br>• Event queue for asynchronous processing |
| **FR-L1-007** | Audit logging | **High** | • Log all API requests (user, endpoint, payload, response)<br>• Leverage nexus-audit-log package<br>• Searchable audit trail<br>• Retention policy (default: 7 years) |

### FR-L2: Level 2 - Advanced Orchestration (Progressive Features)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L2-001** | Feature toggle engine | **High** | • Enable/disable modules per tenant (procurement, manufacturing, project, accounting)<br>• Feature flags (beta features, experimental APIs)<br>• License-based feature restrictions<br>• Runtime feature checking (no app restart)<br>• Feature toggle API endpoints |
| **FR-L2-002** | License enforcement | **High** | • License tiers: Basic, Professional, Enterprise<br>• Per-tier feature matrix (Basic: 10 users, Pro: 100 users, Enterprise: unlimited)<br>• Usage limits (API calls per month, storage GB)<br>• License expiration warnings<br>• Grace period after expiration (14 days read-only) |
| **FR-L2-003** | API documentation | **High** | • OpenAPI 3.0 specification<br>• Swagger UI (/api/documentation)<br>• Auto-generated from route annotations<br>• Request/response examples<br>• Authentication guide |
| **FR-L2-004** | Rate limiting | **High** | • Per-user rate limits (default: 60 req/min)<br>• Per-tenant rate limits (default: 1000 req/min)<br>• License-based limits (Basic: 100/min, Enterprise: unlimited)<br>• 429 Too Many Requests response<br>• X-RateLimit headers |
| **FR-L2-005** | API versioning | **High** | • URL-based versioning (/api/v1/, /api/v2/)<br>• Maintain v1 for 12 months after v2 release<br>• Deprecation warnings in response headers<br>• Migration guide in docs |
| **FR-L2-006** | Webhook management | **High** | • Register webhook URLs per tenant<br>• Event subscription (project.created, invoice.paid)<br>• Retry logic (exponential backoff)<br>• Webhook signature verification (HMAC)<br>• Webhook delivery logs |
| **FR-L2-007** | Contract binding registry | **High** | • Central registry of all contracts<br>• Automatic binding in ErpServiceProvider<br>• Override bindings via config<br>• Validation: ensure all contracts bound before boot |

### FR-L3: Level 3 - Enterprise Orchestration (Advanced)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L3-001** | GraphQL API | Medium | • GraphQL endpoint (/graphql)<br>• Schema auto-generated from models<br>• Nested queries (project.tasks.timesheets)<br>• Mutations for write operations<br>• GraphQL Playground |
| **FR-L3-002** | Bulk operations | Medium | • Batch create (POST /api/v1/bulk/purchase-orders)<br>• Batch update (PATCH /api/v1/bulk/projects)<br>• Batch delete (DELETE /api/v1/bulk/tasks)<br>• Progress tracking for long-running batches<br>• Partial success handling |
| **FR-L3-003** | API usage analytics | **High** | • Real-time dashboard (requests per second, error rate)<br>• Per-endpoint metrics (latency p50/p95/p99)<br>• Per-user usage tracking<br>• Slow query identification<br>• Export to monitoring tools (Datadog, New Relic) |
| **FR-L3-004** | Scheduled orchestration | **High** | • Laravel scheduler integration<br>• Daily report generation (email to users)<br>• Monthly invoicing automation<br>• Weekly inventory reorder recommendations<br>• Custom schedule definition via admin UI |
| **FR-L3-005** | Server-sent events (SSE) | Low | • Real-time endpoint (/api/v1/events)<br>• Subscribe to event streams<br>• Automatic reconnection<br>• Heartbeat to keep connection alive |
| **FR-L3-006** | Multi-database orchestration | Low | • Read replicas for query performance<br>• Write to primary, read from replicas<br>• Automatic failover<br>• Tenant-specific databases (for compliance) |

---

## Non-Functional Requirements

### Performance Requirements

| ID | Requirement | Target | Notes |
|----|-------------|--------|-------|
| **PR-001** | API endpoint response time (simple queries) | < 200ms (p95) | Single-domain queries (GET /api/v1/projects) |
| **PR-002** | API endpoint response time (complex queries) | < 1s (p95) | Cross-package queries (GET /api/v1/projects/{id}/full-details) |
| **PR-003** | Cross-package action execution | < 3s (p95) | CreatePurchaseOrderAction with inventory + accounting |
| **PR-004** | Concurrent users supported | 1000+ | With horizontal scaling |
| **PR-005** | API throughput | 10,000 req/sec | Per application instance |

### Security Requirements

| ID | Requirement | Scope |
|----|-------------|-------|
| **SR-001** | API authentication | All endpoints require Bearer token (except /login, /register) |
| **SR-002** | HTTPS enforcement | All production APIs MUST use TLS 1.3 |
| **SR-003** | CORS configuration | Configurable allowed origins per tenant |
| **SR-004** | SQL injection prevention | Use query builder / Eloquent only, no raw SQL |
| **SR-005** | XSS prevention | Escape all output (though API is JSON-only) |
| **SR-006** | CSRF protection | Not applicable (stateless API), but enabled for Sanctum SPA mode |
| **SR-007** | Audit trail immutability | Audit logs cannot be edited or deleted (append-only) |

### Reliability Requirements

| ID | Requirement | Notes |
|----|-------------|-------|
| **REL-001** | API availability | 99.9% uptime (excluding scheduled maintenance) |
| **REL-002** | Cross-package transaction integrity | Use database transactions for multi-package operations |
| **REL-003** | Graceful degradation | If one package fails, other packages remain functional |
| **REL-004** | Error handling | Consistent error response format, helpful messages |
| **REL-005** | Idempotency | POST/PUT/DELETE operations idempotent where possible |

### Scalability Requirements

| ID | Requirement | Notes |
|----|-------------|-------|
| **SCALE-001** | Horizontal scaling | Support load balancing across multiple instances |
| **SCALE-002** | Database connection pooling | Efficient connection management |
| **SCALE-003** | Queue worker scaling | Independent scaling of background job workers |
| **SCALE-004** | Cache layer | Redis for session, token validation, feature flags |

---

## Orchestration Domain Model

### Core Orchestration Entities

```
User (Authentication Domain - Owned by Nexus\Erp)
├── id (UUID)
├── tenant_id (UUID)
├── name (string)
├── email (string, unique per tenant)
├── password (hashed)
├── email_verified_at (datetime)
├── failed_login_attempts (int)
├── locked_until (datetime, nullable)
└── Roles (manyToMany via model_has_roles)
└── API Tokens (hasMany PersonalAccessToken)

FeatureToggle (Feature Management - Owned by Nexus\Erp)
├── id (UUID)
├── tenant_id (UUID, nullable) - null = global
├── feature_key (string) - e.g., "module.procurement", "feature.bulk-operations"
├── enabled (boolean)
├── license_tier_required (enum: basic, professional, enterprise, nullable)
├── effective_date (date, nullable)
├── expiry_date (date, nullable)
└── configuration (json) - feature-specific settings

License (License Management - Owned by Nexus\Erp)
├── id (UUID)
├── tenant_id (UUID)
├── license_key (string, unique)
├── tier (enum: basic, professional, enterprise)
├── starts_at (date)
├── expires_at (date)
├── max_users (int)
├── max_api_calls_per_month (int)
├── max_storage_gb (int)
├── enabled_modules (json) - ["procurement", "manufacturing"]
└── metadata (json)

ApiUsageMetric (Analytics - Owned by Nexus\Erp)
├── id (UUID)
├── tenant_id (UUID)
├── user_id (UUID, nullable)
├── endpoint (string) - /api/v1/purchase-orders
├── method (enum: GET, POST, PUT, DELETE)
├── status_code (int)
├── response_time_ms (int)
├── requested_at (datetime)
├── ip_address (string)
└── user_agent (string)

Webhook (Event Delivery - Owned by Nexus\Erp)
├── id (UUID)
├── tenant_id (UUID)
├── url (string) - customer webhook endpoint
├── events (json) - ["project.created", "invoice.paid"]
├── secret (string) - for HMAC signature
├── enabled (boolean)
├── failed_attempts (int)
└── last_triggered_at (datetime)

WebhookDelivery (Delivery Tracking - Owned by Nexus\Erp)
├── id (UUID)
├── webhook_id (UUID)
├── event_type (string)
├── payload (json)
├── response_status (int, nullable)
├── response_body (text, nullable)
├── attempted_at (datetime)
└── succeeded (boolean)
```

### Aggregate Relationships

```
Tenant (from nexus-tenancy)
  └─> Users (hasMany)
  └─> FeatureToggles (hasMany)
  └─> License (hasOne)
  └─> ApiUsageMetrics (hasMany)
  └─> Webhooks (hasMany)

User (Nexus\Erp)
  └─> Roles (manyToMany via Spatie Permission)
  └─> Permissions (manyToMany via Spatie Permission)
  └─> ApiTokens (hasMany PersonalAccessToken)
```

---

## Business Rules

| ID | Rule | Level |
|----|------|-------|
| **BR-001** | All API endpoints require authentication except /login, /register, /forgot-password | All levels |
| **BR-002** | Users can only access data within their tenant | All levels |
| **BR-003** | Super-admin role can switch tenant context | Level 1 |
| **BR-004** | Feature toggle changes take effect immediately (no app restart) | Level 2 |
| **BR-005** | Disabled features return 403 Forbidden with reason | Level 2 |
| **BR-006** | License expiration blocks write operations but allows read access for 14 days | Level 2 |
| **BR-007** | Rate limit exceeded returns 429 with Retry-After header | Level 2 |
| **BR-008** | Cross-package actions execute in database transactions | All levels |
| **BR-009** | Failed webhook deliveries retry 5 times with exponential backoff | Level 2 |
| **BR-010** | API version deprecation warnings sent 90 days before removal | Level 2 |

---

## API Workflow State Machines

### Feature Toggle Lifecycle

```
States:
  - disabled (initial state)
  - enabled (feature available)
  - deprecated (marked for removal)
  - removed (no longer available)

Transitions:
  enable: disabled → enabled
    - Guard: license allows feature OR is global feature
    - Action: update feature_toggles table
    - Triggers: FeatureEnabledEvent
    
  disable: enabled → disabled
    - Guard: user has manage-features permission
    - Action: update feature_toggles table
    - Triggers: FeatureDisabledEvent
    
  deprecate: enabled → deprecated
    - Guard: API versioning policy
    - Action: add deprecation warning to API responses
    - Triggers: FeatureDeprecatedEvent
    
  remove: deprecated → removed
    - Guard: 90 days after deprecation
    - Action: return 410 Gone for removed endpoints
```

### License Lifecycle

```
States:
  - trial (14 days free)
  - active (paid license)
  - expiring_soon (< 30 days remaining)
  - expired (past expiration date)
  - suspended (payment issue)
  - terminated (account closed)

Transitions:
  activate: trial → active
    - Guard: payment received
    - Action: update license record
    - Triggers: LicenseActivatedEvent
    
  warn_expiring: active → expiring_soon
    - Guard: 30 days before expiration
    - Action: send email notification
    - Triggers: LicenseExpiringEvent
    
  expire: expiring_soon → expired
    - Guard: expiration date reached
    - Action: block write operations, allow read for 14 days
    - Triggers: LicenseExpiredEvent
    
  suspend: [active, expired] → suspended
    - Guard: payment failure
    - Action: block all operations except read access to invoices
    - Triggers: LicenseSuspendedEvent
```

---

## Integration Points

### With Atomic Packages

| Package | Integration Type | Usage |
|---------|------------------|-------|
| **nexus-tenancy** | Direct Dependency | Tenant context management, BelongsToTenant trait |
| **nexus-sequencing** | Service Call | Generate document numbers for cross-package workflows |
| **nexus-settings** | Service Call | Retrieve configuration (feature flags stored here as key-value) |
| **nexus-audit-log** | Event Listener | Log all API operations automatically |
| **nexus-workflow** | Engine Usage | Leverage workflow engine for approval processes |
| **nexus-backoffice** | Data Reference | User→Department relationships for org hierarchy |
| **nexus-procurement** | Action Orchestration | CreatePurchaseOrderAction coordinates multiple packages |
| **nexus-project-management** | Action Orchestration | CompleteProjectAction triggers invoicing and accounting |
| **nexus-manufacturing** | Action Orchestration | CreateWorkOrderAction reserves inventory and posts costs |
| **nexus-accounting** | Action Orchestration | All financial postings orchestrated through actions |
| **nexus-inventory** | Action Orchestration | Stock movements triggered by procurement, manufacturing, sales |

### External Integrations

| System | Integration Method | Purpose |
|--------|-------------------|---------|
| **Frontend Applications** | REST API | Edward CLI, custom web/mobile apps |
| **Third-Party ERPs** | REST API + Webhooks | SAP, Oracle, Microsoft Dynamics integration |
| **Payment Gateways** | REST API | Stripe, PayPal for subscription billing |
| **Email Services** | SMTP/API | SendGrid, Mailgun for notifications |
| **Monitoring Tools** | Metrics Export | Datadog, New Relic, Prometheus |
| **Identity Providers** | OAuth2/SAML | Single Sign-On (future) |

---

## Testing Requirements

### Unit Tests
- Action orchestration logic (mocked package dependencies)
- Feature toggle evaluation
- License validation
- Rate limiting algorithm
- Contract binding verification

### Feature Tests
- Complete API endpoint workflows (authentication → request → response)
- Cross-package action execution (with database transactions)
- Feature toggle enforcement
- License expiration handling
- Webhook delivery and retry

### Integration Tests
- Full stack: Edward CLI → Nexus\Erp API → Atomic Packages
- Multi-package workflows (create PO → receive goods → post invoice)
- Event-driven coordination (domain event → orchestrator listener → action)

### Performance Tests
- API load testing (1000 concurrent users)
- Complex cross-package query performance
- Rate limiting under heavy load
- Database connection pool exhaustion

---

## Package Structure

```
src/  (Nexus\Erp namespace)
├── Actions/                          # Cross-package orchestration
│   ├── Procurement/
│   │   ├── CreatePurchaseOrderAction.php
│   │   ├── ReceiveGoodsAction.php
│   │   └── Process3WayMatchAction.php
│   ├── Project/
│   │   ├── CreateProjectAction.php
│   │   ├── AssignTaskAction.php
│   │   └── GenerateProjectInvoiceAction.php
│   ├── Manufacturing/
│   │   ├── CreateWorkOrderAction.php
│   │   ├── ReleaseWorkOrderAction.php
│   │   └── CompleteProductionAction.php
│   └── Inventory/
│       ├── AdjustStockAction.php
│       └── TransferStockAction.php
│
├── Console/
│   └── Commands/                     # Orchestration CLI commands
│       ├── SyncLicensesCommand.php
│       ├── GenerateDailyReportsCommand.php
│       └── ClearExpiredTokensCommand.php
│
├── Http/
│   ├── Controllers/Api/V1/           # API presentation layer
│   │   ├── Auth/
│   │   │   ├── LoginController.php
│   │   │   ├── RegisterController.php
│   │   │   └── PasswordResetController.php
│   │   ├── Procurement/
│   │   │   ├── PurchaseRequisitionController.php
│   │   │   ├── PurchaseOrderController.php
│   │   │   └── VendorController.php
│   │   ├── Project/
│   │   │   ├── ProjectController.php
│   │   │   ├── TaskController.php
│   │   │   └── TimesheetController.php
│   │   ├── Manufacturing/
│   │   │   ├── WorkOrderController.php
│   │   │   └── ProductionReportController.php
│   │   └── System/
│   │       ├── FeatureToggleController.php
│   │       ├── LicenseController.php
│   │       └── WebhookController.php
│   │
│   ├── Middleware/
│   │   ├── EnsureTenantContext.php
│   │   ├── EnforceFeatureToggle.php
│   │   ├── ValidateLicense.php
│   │   ├── ApiRateLimiting.php
│   │   └── ApiVersioning.php
│   │
│   └── Resources/                    # API response transformers
│       ├── UserResource.php
│       ├── FeatureToggleResource.php
│       └── ApiUsageMetricResource.php
│
├── Models/                           # Orchestration domain models
│   ├── User.php
│   ├── FeatureToggle.php
│   ├── License.php
│   ├── ApiUsageMetric.php
│   ├── Webhook.php
│   └── WebhookDelivery.php
│
├── Repositories/                     # Data access for orchestration entities
│   ├── UserRepository.php
│   ├── FeatureToggleRepository.php
│   └── LicenseRepository.php
│
├── Services/                         # Orchestration business logic
│   ├── FeatureToggleService.php
│   ├── LicenseEnforcementService.php
│   ├── WebhookDeliveryService.php
│   ├── ApiUsageTrackingService.php
│   └── ContractBindingService.php
│
├── Contracts/                        # Orchestrator-specific interfaces
│   ├── FeatureToggleServiceContract.php
│   ├── LicenseServiceContract.php
│   └── WebhookServiceContract.php
│
├── Enums/                            # Orchestrator enums
│   ├── LicenseTier.php
│   ├── FeatureState.php
│   └── ApiVersion.php
│
├── Events/                           # Orchestration events
│   ├── FeatureToggled.php
│   ├── LicenseExpired.php
│   ├── RateLimitExceeded.php
│   └── WebhookDeliveryFailed.php
│
├── Listeners/                        # Cross-package event listeners
│   ├── Procurement/
│   │   ├── PurchaseOrderCreatedListener.php  # Triggers inventory reservation
│   │   └── GoodsReceivedListener.php         # Triggers accounting posting
│   ├── Project/
│   │   ├── ProjectCompletedListener.php      # Triggers invoice generation
│   │   └── TimesheetApprovedListener.php     # Triggers cost posting
│   └── Manufacturing/
│       ├── WorkOrderCompletedListener.php    # Triggers inventory receipt
│       └── MaterialConsumedListener.php      # Triggers cost allocation
│
├── Providers/
│   ├── ErpServiceProvider.php        # Main orchestrator provider
│   ├── ContractBindingProvider.php   # Binds all package contracts
│   ├── EventServiceProvider.php      # Registers cross-package listeners
│   └── RouteServiceProvider.php      # API route registration
│
└── Support/
    ├── Facades/
    │   ├── FeatureToggle.php
    │   └── License.php
    └── Helpers/
        └── orchestrator_helpers.php

database/
└── migrations/
    ├── 2025_11_15_000001_create_users_table.php
    ├── 2025_11_15_000002_create_feature_toggles_table.php
    ├── 2025_11_15_000003_create_licenses_table.php
    ├── 2025_11_15_000004_create_api_usage_metrics_table.php
    ├── 2025_11_15_000005_create_webhooks_table.php
    └── 2025_11_15_000006_create_webhook_deliveries_table.php

routes/
├── api.php                           # Public API routes (v1, v2)
├── web.php                           # Not used (headless API only)
└── console.php                       # Artisan command registration

config/
├── erp.php                           # Main orchestrator configuration
├── features.php                      # Feature toggle definitions
├── licenses.php                      # License tier definitions
└── api.php                           # API configuration (versioning, rate limits)

tests/
├── Unit/
│   ├── Services/
│   │   ├── FeatureToggleServiceTest.php
│   │   ├── LicenseEnforcementServiceTest.php
│   │   └── WebhookDeliveryServiceTest.php
│   └── Middleware/
│       ├── EnforceFeatureToggleTest.php
│       └── ValidateLicenseTest.php
│
├── Feature/
│   ├── Api/
│   │   ├── AuthenticationTest.php
│   │   ├── FeatureToggleApiTest.php
│   │   └── RateLimitingTest.php
│   └── Orchestration/
│       ├── CrossPackageWorkflowTest.php
│       ├── EventDrivenCoordinationTest.php
│       └── TransactionalIntegrityTest.php
│
└── Integration/
    └── FullStackWorkflowTest.php     # Edward CLI → API → Packages

docs/
├── api/
│   ├── openapi.yaml                  # OpenAPI 3.0 specification
│   └── postman-collection.json       # Postman collection
├── orchestration/
│   ├── cross-package-workflows.md    # Workflow documentation
│   └── feature-toggle-guide.md       # Feature management guide
└── REQUIREMENTS.md                   # This document
```

---

## Configuration

### erp.php (Main Configuration)

```php
return [
    // Orchestrator identity
    'name' => env('APP_NAME', 'Nexus ERP'),
    'version' => '1.0.0',
    'api_version' => 'v1',
    
    // Package auto-discovery
    'packages' => [
        'nexus-tenancy' => [
            'enabled' => true,
            'priority' => 1,  // Load order
        ],
        'nexus-sequencing' => [
            'enabled' => true,
            'priority' => 2,
        ],
        'nexus-procurement' => [
            'enabled' => env('ENABLE_PROCUREMENT', true),
            'priority' => 10,
        ],
        'nexus-manufacturing' => [
            'enabled' => env('ENABLE_MANUFACTURING', true),
            'priority' => 11,
        ],
        'nexus-project-management' => [
            'enabled' => env('ENABLE_PROJECT', true),
            'priority' => 12,
        ],
    ],
    
    // Cross-package transaction settings
    'transactions' => [
        'enabled' => true,
        'isolation_level' => 'READ_COMMITTED',
    ],
    
    // API defaults
    'api' => [
        'pagination_default' => 25,
        'pagination_max' => 100,
        'response_format' => 'json',
    ],
];
```

### features.php (Feature Toggle Definitions)

```php
return [
    // Module-level toggles
    'modules' => [
        'procurement' => [
            'name' => 'Procurement Management',
            'default_enabled' => true,
            'license_required' => 'basic',
        ],
        'manufacturing' => [
            'name' => 'Manufacturing Execution',
            'default_enabled' => false,
            'license_required' => 'professional',
        ],
        'project-management' => [
            'name' => 'Project Management',
            'default_enabled' => true,
            'license_required' => 'basic',
        ],
    ],
    
    // Feature-level toggles
    'features' => [
        'bulk-operations' => [
            'name' => 'Bulk API Operations',
            'default_enabled' => false,
            'license_required' => 'professional',
        ],
        'webhooks' => [
            'name' => 'Webhook Notifications',
            'default_enabled' => true,
            'license_required' => 'basic',
        ],
        'graphql' => [
            'name' => 'GraphQL API',
            'default_enabled' => false,
            'license_required' => 'enterprise',
        ],
        'advanced-analytics' => [
            'name' => 'Advanced Analytics Dashboard',
            'default_enabled' => false,
            'license_required' => 'enterprise',
        ],
    ],
];
```

### licenses.php (License Tier Definitions)

```php
return [
    'tiers' => [
        'basic' => [
            'name' => 'Basic',
            'max_users' => 10,
            'max_api_calls_per_month' => 100000,
            'max_storage_gb' => 10,
            'enabled_modules' => ['procurement', 'project-management'],
            'enabled_features' => ['webhooks'],
            'price_per_month' => 99,
        ],
        'professional' => [
            'name' => 'Professional',
            'max_users' => 100,
            'max_api_calls_per_month' => 1000000,
            'max_storage_gb' => 100,
            'enabled_modules' => ['procurement', 'manufacturing', 'project-management', 'accounting'],
            'enabled_features' => ['webhooks', 'bulk-operations'],
            'price_per_month' => 499,
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'max_users' => -1,  // Unlimited
            'max_api_calls_per_month' => -1,  // Unlimited
            'max_storage_gb' => -1,  // Unlimited
            'enabled_modules' => '*',  // All modules
            'enabled_features' => '*',  // All features
            'price_per_month' => 1999,
        ],
    ],
    
    // Grace period after expiration
    'grace_period_days' => 14,
    
    // Trial settings
    'trial' => [
        'enabled' => true,
        'duration_days' => 14,
        'tier' => 'professional',
    ],
];
```

### api.php (API Configuration)

```php
return [
    // API versioning
    'versions' => [
        'current' => 'v1',
        'supported' => ['v1'],
        'deprecated' => [],
    ],
    
    // Rate limiting
    'rate_limits' => [
        'per_user' => [
            'basic' => 60,        // 60 req/min
            'professional' => 120,
            'enterprise' => -1,   // Unlimited
        ],
        'per_tenant' => [
            'basic' => 1000,      // 1000 req/min
            'professional' => 5000,
            'enterprise' => -1,
        ],
    ],
    
    // CORS
    'cors' => [
        'allowed_origins' => explode(',', env('API_ALLOWED_ORIGINS', '*')),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Tenant-ID'],
    ],
    
    // Response format
    'response' => [
        'include_timestamp' => true,
        'include_request_id' => true,
        'wrap_data' => true,  // {data: {...}, meta: {...}}
    ],
];
```

---

## Success Metrics

| Metric | Target | Measurement Period | Why It Matters |
|--------|--------|-------------------|----------------|
| **API Availability** | > 99.9% | Monthly | System reliability |
| **API Response Time (p95)** | < 500ms | Real-time | User experience |
| **Package Integration Success** | 100% | Per release | Orchestration effectiveness |
| **Feature Toggle Adoption** | > 70% tenants use custom toggles | Quarterly | Configuration flexibility |
| **License Compliance** | 100% (no unauthorized usage) | Ongoing | Revenue protection |
| **API Documentation Coverage** | 100% endpoints documented | Per release | Developer experience |
| **Cross-Package Transaction Success Rate** | > 99.5% | Monthly | Data integrity |
| **Developer Satisfaction (API)** | > 4.5/5 rating | Quarterly survey | Ecosystem growth |

---

## Development Phases

### Phase 1: Foundation (Weeks 1-6)
- ErpServiceProvider and contract binding registry
- User authentication (Sanctum API tokens)
- Basic RBAC (Spatie Permission integration)
- Tenant context middleware
- Core API endpoints (projects, tasks, purchase orders)
- OpenAPI documentation generation
- Unit and feature tests

### Phase 2: Orchestration Core (Weeks 7-12)
- Laravel Actions implementation for cross-package workflows
- Event-driven coordination (domain event listeners)
- Cross-package transaction management
- Audit logging integration (nexus-audit-log)
- API versioning (v1 structure)
- Rate limiting middleware
- Integration tests with Edward CLI

### Phase 3: Feature Management (Weeks 13-18)
- Feature toggle engine
- License enforcement system
- Feature toggle API and admin UI
- Usage tracking (API metrics)
- Webhook management
- Webhook delivery with retry logic
- Performance testing

### Phase 4: Advanced Features (Weeks 19-24)
- GraphQL API endpoint
- Bulk operations support
- API usage analytics dashboard
- Scheduled orchestration (Laravel scheduler)
- Server-sent events (SSE)
- Security audit and penetration testing

### Phase 5: Production Readiness (Weeks 25-28)
- Performance optimization (query caching, connection pooling)
- Comprehensive documentation (API guides, orchestration patterns)
- Video tutorials for system integrators
- Beta testing with 5-10 partner organizations
- Production deployment

---

## Dependencies

### Required (Core Orchestration)
- PHP ≥ 8.3
- Laravel ≥ 12.x
- Laravel Sanctum ^4.2 (API authentication)
- Spatie Laravel Permission ^6.23 (RBAC)
- lorisleiva/laravel-actions ^2.0 (Action pattern)

### Required (Atomic Packages)
- nexus-tenancy (tenant context management)
- nexus-sequencing (document numbering)
- nexus-settings (configuration storage)
- nexus-audit-log (activity logging)
- nexus-workflow (approval processes)
- nexus-backoffice (organizational structure)

### Optional (Business Domain Packages)
- nexus-procurement (procurement management)
- nexus-manufacturing (production execution)
- nexus-project-management (project tracking)
- nexus-accounting (financial management)
- nexus-inventory (stock management)
- nexus-hrm (human resources)
- nexus-crm (customer relationship)

### Optional (Advanced Features)
- Laravel Octane (performance boost)
- Laravel Horizon (queue monitoring)
- Laravel Telescope (debugging)
- Laravel Pulse (application monitoring)

---

## Glossary

- **Orchestration Layer:** The Nexus\Erp code that coordinates multiple atomic packages to fulfill business workflows
- **Action:** A Laravel Action class that encapsulates cross-package business logic (CreatePurchaseOrderAction)
- **Feature Toggle:** Runtime switch to enable/disable modules or features per tenant
- **License Tier:** Subscription level (Basic/Professional/Enterprise) determining feature availability
- **Contract:** PHP interface defining how packages communicate (avoiding concrete dependencies)
- **Cross-Package Workflow:** Business process spanning multiple atomic packages (PO creation → inventory → accounting)
- **Event-Driven Coordination:** Using domain events to trigger orchestrator actions across packages
- **Headless API:** RESTful API with no frontend, consumed by external clients (Edward CLI, custom apps)
- **Tenant Context:** The active tenant for current request, enforced via middleware
- **Rate Limiting:** Restricting API request frequency to prevent abuse

---

**Document Version:** 1.0.0  
**Last Updated:** November 15, 2025  
**Status:** Ready for Review and Implementation Planning

---

## Notes on Orchestration vs Domain Logic

This package intentionally handles **orchestration concerns only**:

1. **Coordination, Not Domain Logic** - Nexus\Erp coordinates how packages interact but does NOT implement domain business rules. Example: "How to calculate project variance?" belongs in nexus-project-management, not here.

2. **Contract-Driven Integration** - All atomic package interactions use interfaces, never concrete classes. This ensures packages remain independently testable and deployable.

3. **Feature Toggling Is Orchestration** - Feature toggles control which packages/workflows are available to tenants. This is pure orchestration logic that cannot function meaningfully without packages to orchestrate.

4. **License Enforcement Is Orchestration** - License management determines feature availability across all packages, making it a cross-cutting orchestration concern.

5. **API Presentation Is Orchestration** - RESTful endpoints that expose atomic package functionality to external consumers are orchestration layer responsibilities.

**This design ensures atomic packages remain pure, reusable business logic libraries while Nexus\Erp provides the intelligent coordination layer that makes them work together as a cohesive ERP system.**
