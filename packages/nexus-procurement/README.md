# Nexus Procurement Package

**Domain-Driven Procurement Management for Laravel/PHP**

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/azaharizaman/nexus-erp)
[![Laravel](https://img.shields.io/badge/laravel-12.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/php-8.3+-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## Overview

Nexus Procurement is a **domain-driven bounded context** that encapsulates the complete procurement lifecycle within a cohesive domain model. Unlike atomic packages, procurement is intentionally designed as a unified bounded context due to the tight coupling of procurement processes, statutory requirements, and complex workflow dependencies.

### Domain Capabilities

The package provides a complete **procure-to-pay domain model** with the following bounded contexts:

- **Requisition Management**: Purchase request lifecycle with approval workflows
- **Vendor Management**: Supplier lifecycle, performance tracking, and portal integration
- **Purchase Order Management**: PO creation, amendments, and vendor communication
- **Goods Receipt**: Physical receipt processing with quality control
- **Invoice Processing**: 3-way matching, variance analysis, and payment authorization
- **Contract Management**: Framework agreements and blanket purchase orders
- **RFQ Management**: Competitive bidding and quote evaluation
- **Analytics & Reporting**: Procurement intelligence and performance metrics

### Key Domain Entities

#### Core Procurement Entities
- `PurchaseRequisition` - Initiates procurement requests with approval routing
- `PurchaseOrder` - Authoritative purchase commitment to vendors
- `GoodsReceiptNote` - Documents physical receipt of ordered goods
- `VendorInvoice` - Vendor billing with 3-way matching validation

#### Advanced Procurement Entities
- `RequestForQuotation` - Competitive bidding process management
- `ProcurementContract` - Formal vendor agreements and terms
- `BlanketPurchaseOrder` - Long-term commitment frameworks
- `Vendor` - Supplier master data with performance metrics

#### Supporting Entities
- `VendorUser` - External vendor portal authentication
- `ThreeWayMatchResult` - Automated PO-GRN-Invoice reconciliation
- `ContractAmendment` - Contract modification tracking

### Domain Services

#### Business Logic Services
- `RequisitionApprovalService` - Handles approval matrix routing and delegation
- `ThreeWayMatchService` - Automated 3-way matching with tolerance rules
- `VendorPerformanceService` - Calculates and tracks vendor KPIs
- `ProcurementAnalyticsService` - Generates procurement intelligence reports

#### Operational Services
- `PurchaseOrderService` - PO lifecycle management and amendments
- `GoodsReceiptService` - Receipt processing and inventory integration
- `RFQManagementService` - Quote evaluation and vendor selection
- `ContractManagementService` - Contract lifecycle and compliance

### Integration Points

#### Required Nexus Packages
- `nexus-tenancy` - Multi-tenant data isolation
- `nexus-workflow` - Approval workflow engine
- `nexus-sequencing` - Document numbering
- `nexus-audit-log` - Activity logging and compliance

#### Optional Integrations
- `nexus-accounting` - GL posting and financial integration
- `nexus-inventory` - Stock level updates and reservations
- `nexus-settings` - Configuration management
- `nexus-backoffice` - Administrative interfaces

### Domain Events

The package emits domain events for integration:

```php
// Procurement Lifecycle Events
PurchaseRequisitionApproved::class
PurchaseOrderCreated::class
GoodsReceiptProcessed::class
VendorInvoiceMatched::class

// Vendor Management Events
VendorPerformanceCalculated::class
ContractAmendmentApproved::class

// Analytics Events
ProcurementMetricsUpdated::class
```

### API Capabilities

#### REST API Endpoints
- **Requisitions**: CRUD operations with approval workflow integration
- **Purchase Orders**: Full PO lifecycle management
- **Vendor Management**: Supplier onboarding and performance tracking
- **Invoice Processing**: 3-way matching and payment authorization
- **Analytics**: Procurement intelligence and reporting
- **Vendor Portal**: External vendor self-service APIs

#### Webhook Support
Configurable webhooks for real-time integration with external systems.

### Security & Compliance

#### Multi-Tenant Isolation
- Complete data segregation by tenant
- Tenant-scoped queries and operations
- Cross-tenant data leakage prevention

#### Audit Trail
- Complete activity logging via nexus-audit-log
- Compliance reporting capabilities
- Tamper-evident audit records

#### Separation of Duties
- Configurable SoD rules enforcement
- Automated conflict detection
- Compliance monitoring and reporting

### Performance Characteristics

#### Database Optimization
- 30+ strategic indexes for query performance
- Tenant-aware partitioning strategies
- Optimized for high-volume procurement operations

#### Caching Strategy
- Redis-backed caching for reference data
- Query result caching for analytics
- Session management for vendor portal

#### Queue Processing
- Asynchronous processing for heavy operations
- Background job processing for notifications
- Scalable queue architecture for high throughput

## Phase 5: Optimization & Launch - COMPLETE ✅

Nexus Procurement has successfully completed all development phases:

### ✅ Phase 1: Core Procurement (Complete)
- Purchase Requisitions with approval workflows
- Purchase Orders with vendor management
- Goods Receipt Notes
- Basic invoice processing

### ✅ Phase 2: RFQ & Advanced Matching (Complete)
- Request for Quotation system
- Competitive bidding workflows
- Enhanced 3-way matching
- Vendor quote evaluation

### ✅ Phase 3: Enterprise Features (Complete)
- Contract management system
- Blanket Purchase Orders
- Separation of Duties enforcement
- Advanced analytics dashboard

### ✅ Phase 4: Vendor Portal (Complete)
- Vendor authentication and registration
- Self-service portal for vendors
- Invoice submission and tracking
- Performance dashboards

### ✅ Phase 5: Optimization & Launch (Complete)
- **Performance Optimization**: 30+ database indexes for optimal query performance
- **Comprehensive Documentation**: User guides, API reference, deployment guides, troubleshooting
- **Video Tutorials**: 14 detailed tutorials covering all workflows
- **Production Ready**: Health checks, monitoring, backup procedures
- **Beta Testing Ready**: Complete test suites and validation procedures

### Production Readiness Checklist
- ✅ Database performance optimized with comprehensive indexes
- ✅ Complete user documentation and training materials
- ✅ API reference with examples and SDK information
- ✅ Deployment and configuration guides
- ✅ Troubleshooting and maintenance procedures
- ✅ Security hardening and compliance features
- ✅ Multi-tenant isolation and data protection
- ✅ Integration with core Nexus packages
- ✅ Comprehensive test coverage
- ✅ Audit trails and compliance reporting

## Architecture

### Bounded Context Design

Procurement is intentionally designed as a **cohesive bounded context** rather than atomic packages because:

- **Domain Coherence**: Procurement entities are tightly coupled (requisition → PO → GRN → invoice)
- **Statutory Requirements**: Tax rules, import duties, and compliance requirements are procurement-specific
- **Workflow Complexity**: Approval matrices and state machines span multiple entities
- **Data Relationships**: Extensive referential integrity across the procurement lifecycle

### Package Structure

```
packages/nexus-procurement/
├── src/
│   ├── Models/                 # Domain entities with business logic
│   │   ├── PurchaseRequisition.php
│   │   ├── PurchaseOrder.php
│   │   ├── GoodsReceiptNote.php
│   │   ├── VendorInvoice.php
│   │   ├── ProcurementContract.php
│   │   └── Vendor.php
│   ├── Services/              # Domain services encapsulating business logic
│   │   ├── RequisitionApprovalService.php
│   │   ├── ThreeWayMatchService.php
│   │   ├── VendorPerformanceService.php
│   │   └── ProcurementAnalyticsService.php
│   ├── Http/Controllers/      # REST API controllers
│   ├── Repositories/          # Data access abstractions
│   ├── Enums/                 # Domain value objects
│   ├── Rules/                 # Business rule validations
│   └── Notifications/         # Domain event notifications
├── database/
│   ├── migrations/           # Database schema evolution
│   └── seeders/              # Test data provisioning
├── routes/                   # API route definitions
├── config/                   # Package configuration
└── tests/                    # Domain behavior specifications
```

### Domain Model Details

#### Entity Relationships

```php
// Core Procurement Flow
PurchaseRequisition (1) → (many) PurchaseOrder
PurchaseOrder (1) → (many) GoodsReceiptNote
PurchaseOrder (1) → (many) VendorInvoice
GoodsReceiptNote (1) → (many) VendorInvoice (3-way match)

// Advanced Features
Vendor (1) → (many) ProcurementContract
ProcurementContract (1) → (many) BlanketPurchaseOrder
BlanketPurchaseOrder (1) → (many) BlanketPORelease

// RFQ Process
PurchaseRequisition (1) → (1) RequestForQuotation
RequestForQuotation (1) → (many) VendorQuote
VendorQuote (1) → (1) PurchaseOrder (winner)
```

#### Aggregate Roots

- `PurchaseRequisition` - Requisition aggregate with items and approvals
- `PurchaseOrder` - PO aggregate with items, amendments, and receipts
- `ProcurementContract` - Contract aggregate with amendments and blanket POs
- `Vendor` - Vendor aggregate with performance metrics and contracts
- `RequestForQuotation` - RFQ aggregate with quotes and evaluation

#### Value Objects & Enums

```php
// Status Enums
RequisitionStatus::DRAFT → APPROVED → CONVERTED
PurchaseOrderStatus::DRAFT → APPROVED → SENT → RECEIVED → CLOSED
ContractStatus::DRAFT → ACTIVE → AMENDED → EXPIRED

// Domain Enums
MatchStatus::MATCHED | QUANTITY_VARIANCE | PRICE_VARIANCE | UNMATCHED
PaymentStatus::PENDING | AUTHORIZED | PAID | OVERDUE
```

### Business Rules Engine

#### Approval Matrix Rules
```php
// Dynamic approval routing based on:
- Monetary thresholds (manager → director → CFO)
- Department-specific rules
- GL account classifications
- Item category restrictions
- User role hierarchies
```

#### 3-Way Matching Rules
```php
// Configurable tolerance rules:
- Price variance tolerance (default: 5%)
- Quantity variance tolerance (default: 2%)
- Auto-approval within tolerance
- Escalation for variances exceeding limits
```

#### Separation of Duties Rules
```php
// Configurable SoD constraints:
- Requester cannot approve own requisitions
- Creator cannot receive goods
- Single user cannot process entire P2P cycle
- Department-level segregation rules
```

## Installation & Configuration

### System Requirements

- **PHP**: 8.3+ with BCMath, Mbstring, XML extensions
- **Laravel**: 12.x framework
- **Database**: PostgreSQL 15+ or MySQL 8.0+ (with JSON support)
- **Cache**: Redis 6.0+ for session and cache management
- **Queue**: Redis or database queue driver for background processing

### Package Dependencies

#### Required Dependencies
```json
{
  "require": {
    "nexus/tenancy": "^1.0",           // Multi-tenant data isolation
    "nexus/workflow": "^1.0",          // Approval workflow engine
    "nexus/sequencing": "^1.0",        // Document numbering
    "nexus/audit-log": "^1.0",         // Activity logging
    "nexus/settings": "^1.0"           // Configuration management
  }
}
```

#### Optional Dependencies
```json
{
  "suggest": {
    "nexus/accounting": "^1.0",        // GL integration
    "nexus/inventory": "^1.0",         // Stock management
    "nexus/backoffice": "^1.0",        // Admin interfaces
    "laravel/horizon": "^5.0"          // Queue monitoring
  }
}
```

### Installation Steps

1. **Install Package**
   ```bash
   composer require nexus/procurement
   ```

2. **Register Service Provider**
   ```php
   // config/app.php or bootstrap/providers.php
   Nexus\Procurement\ProcurementServiceProvider::class,
   ```

3. **Publish Assets**
   ```bash
   # Configuration files
   php artisan vendor:publish --provider="Nexus\Procurement\ProcurementServiceProvider" --tag="config"

   # Migration files
   php artisan vendor:publish --provider="Nexus\Procurement\ProcurementServiceProvider" --tag="migrations"

   # Route files (optional)
   php artisan vendor:publish --provider="Nexus\Procurement\ProcurementServiceProvider" --tag="routes"
   ```

4. **Run Migrations**
   ```bash
   php artisan migrate
   ```

5. **Seed Reference Data** (optional)
   ```bash
   php artisan db:seed --class=ProcurementSeeder
   ```

### Environment Configuration

#### Required Environment Variables
```env
# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nexus_erp
DB_USERNAME=procurement_user
DB_PASSWORD=secure_password

# Cache Configuration
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue Configuration
QUEUE_CONNECTION=redis
QUEUE_FAILED_DRIVER=redis

# Procurement Configuration
PROCUREMENT_DEFAULT_CURRENCY=USD
PROCUREMENT_TIMEZONE=UTC
PROCUREMENT_LOCALE=en
```

#### Procurement-Specific Configuration
```env
# Approval Configuration
PROCUREMENT_AUTO_APPROVE_LIMIT=1000
PROCUREMENT_ESCALATION_DAYS=7

# 3-Way Match Configuration
PROCUREMENT_MATCH_PRICE_TOLERANCE=5.0
PROCUREMENT_MATCH_QUANTITY_TOLERANCE=2.0
PROCUREMENT_AUTO_APPROVE_MATCH=true

# Vendor Portal Configuration
VENDOR_PORTAL_ENABLED=true
VENDOR_PORTAL_DOMAIN=portal.yourcompany.com
VENDOR_PORTAL_SESSION_LIFETIME=480

# Integration Configuration
ACCOUNTING_INTEGRATION_ENABLED=true
INVENTORY_INTEGRATION_ENABLED=true
WORKFLOW_INTEGRATION_ENABLED=true
AUDIT_LOG_INTEGRATION_ENABLED=true
```

### Configuration Files

#### procurement.php
```php
return [
    // Domain Configuration
    'domain' => [
        'currency' => env('PROCUREMENT_DEFAULT_CURRENCY', 'USD'),
        'timezone' => env('PROCUREMENT_TIMEZONE', 'UTC'),
        'locale' => env('PROCUREMENT_LOCALE', 'en'),
    ],

    // Approval Matrix Configuration
    'approvals' => [
        'auto_approve_limit' => env('PROCUREMENT_AUTO_APPROVE_LIMIT', 1000),
        'escalation_days' => env('PROCUREMENT_ESCALATION_DAYS', 7),
        'matrix' => [
            'manager' => ['max_amount' => 5000],
            'director' => ['max_amount' => 50000],
            'cfo' => ['max_amount' => PHP_INT_MAX],
        ],
    ],

    // 3-Way Matching Rules
    'matching' => [
        'price_tolerance_percent' => env('PROCUREMENT_MATCH_PRICE_TOLERANCE', 5.0),
        'quantity_tolerance_percent' => env('PROCUREMENT_MATCH_QUANTITY_TOLERANCE', 2.0),
        'auto_approve_within_tolerance' => env('PROCUREMENT_AUTO_APPROVE_MATCH', true),
        'escalation_threshold_percent' => 10.0,
    ],

    // Separation of Duties
    'separation_of_duties' => [
        'enabled' => true,
        'rules' => [
            'requester_cannot_approve' => true,
            'creator_cannot_receive' => true,
            'single_user_complete_cycle' => false,
        ],
    ],

    // Integration Points
    'integrations' => [
        'accounting' => [
            'enabled' => env('ACCOUNTING_INTEGRATION_ENABLED', true),
            'auto_post' => true,
            'gl_accounts' => [
                'inventory' => '1200-0000',
                'accounts_payable' => '2000-0000',
                'purchase_variance' => '5100-2000',
            ],
        ],
        'inventory' => [
            'enabled' => env('INVENTORY_INTEGRATION_ENABLED', true),
            'auto_update_stock' => true,
            'track_serial_numbers' => true,
        ],
        'workflow' => [
            'enabled' => env('WORKFLOW_INTEGRATION_ENABLED', true),
            'approval_workflow' => 'PROCUREMENT_APPROVAL',
        ],
        'audit' => [
            'enabled' => env('AUDIT_LOG_INTEGRATION_ENABLED', true),
            'events' => ['*'], // All events or specific list
        ],
    ],
];
```

#### vendor-portal.php
```php
return [
    'portal' => [
        'enabled' => env('VENDOR_PORTAL_ENABLED', true),
        'domain' => env('VENDOR_PORTAL_DOMAIN'),
        'ssl' => true,
        'session' => [
            'lifetime' => env('VENDOR_PORTAL_SESSION_LIFETIME', 480),
            'domain' => env('VENDOR_PORTAL_DOMAIN'),
        ],
        'features' => [
            'po_viewing' => true,
            'invoice_submission' => true,
            'payment_tracking' => true,
            'performance_analytics' => true,
        ],
    ],

    'authentication' => [
        'guard' => 'vendor',
        'password_reset' => [
            'enabled' => true,
            'token_lifetime' => 60, // minutes
        ],
    ],

    'security' => [
        'encryption' => [
            'sensitive_data' => true,
            'communications' => true,
        ],
        'rate_limiting' => [
            'enabled' => true,
            'attempts' => 5,
            'decay_minutes' => 15,
        ],
    ],
];
```

## API Reference

### Authentication

All procurement APIs require authentication. Use Sanctum tokens:

```bash
# Get token
curl -X POST /api/login -d '{"email":"user@example.com","password":"password"}'

# Use token
curl -H "Authorization: Bearer {token}" /api/procurement/requisitions
```

### Core Endpoints

#### Purchase Requisitions

```http
# Create requisition
POST /api/procurement/requisitions
Content-Type: application/json

{
  "department_id": "uuid",
  "justification": "Office supplies for Q4",
  "items": [
    {
      "item_description": "Printer paper",
      "quantity": 100,
      "unit_price_estimate": 0.50,
      "gl_account_code": "6001"
    }
  ]
}

# Approve requisition
POST /api/procurement/requisitions/{id}/approve

# Convert to PO
POST /api/procurement/orders/create-from-requisition
```

#### Purchase Orders

```http
# Create direct PO
POST /api/procurement/orders
{
  "vendor_id": "uuid",
  "items": [
    {
      "item_description": "Office chairs",
      "quantity": 10,
      "unit_price": 150.00
    }
  ]
}

# Send PO to vendor
POST /api/procurement/orders/{id}/send-to-vendor

# Get PO receipt summary
GET /api/procurement/orders/{id}/receipt-summary
```

#### Goods Receipt

```http
# Create GRN from PO
POST /api/procurement/receipts/create-from-purchase-order
{
  "purchase_order_id": "uuid",
  "items": [
    {
      "po_item_id": "uuid",
      "quantity_received": 10,
      "quantity_accepted": 10
    }
  ]
}
```

#### 3-Way Matching

```http
# Upload vendor invoice
POST /api/procurement/invoices
Content-Type: multipart/form-data
Form: invoice.pdf

# Perform 3-way match
POST /api/procurement/invoices/{id}/match

# Authorize payment
POST /api/procurement/invoices/{id}/approve-payment
```

#### RFQ Management

```http
# Create RFQ
POST /api/procurement/rfqs
{
  "requisition_id": "uuid",
  "quote_deadline": "2025-12-01",
  "vendors": ["vendor-1", "vendor-2"]
}

# Submit vendor quote
POST /api/procurement/quotes/rfq/{rfqId}
{
  "vendor_id": "uuid",
  "items": [
    {
      "rfq_item_id": "uuid",
      "unit_price": 45.00,
      "delivery_days": 7
    }
  ]
}
```

### Vendor Portal API

```http
# Vendor login
POST /api/vendor-portal/auth/login
{
  "email": "vendor@example.com",
  "password": "password"
}

# Get vendor POs
GET /api/vendor-portal/purchase-orders

# Submit invoice
POST /api/vendor-portal/invoices
{
  "purchase_order_id": "uuid",
  "invoice_number": "INV-001",
  "total_amount": 1000.00,
  "items": [...]
}
```

### Analytics API

```http
# Dashboard data
GET /api/procurement/analytics/dashboard?months=12

# Spend analysis
GET /api/procurement/analytics/spend-analysis?months=12

# Vendor performance
GET /api/procurement/analytics/supplier-performance?months=12
```

## Business Workflows

### Standard Procurement Flow

1. **Requisition** → Employee submits purchase request
2. **Approval** → Department manager approves based on budget
3. **RFQ** → Procurement creates request for quotes (optional)
4. **Quote Evaluation** → Compare vendor quotes and select winner
5. **PO Creation** → Generate purchase order for selected vendor
6. **PO Approval** → Higher approval if amount exceeds threshold
7. **Vendor Notification** → PO sent to vendor via email/portal
8. **Goods Receipt** → Warehouse receives and inspects goods
9. **Invoice Processing** → Vendor submits invoice
10. **3-Way Match** → System matches PO + GRN + Invoice
11. **Payment Authorization** → AP clerk approves payment
12. **Payment Processing** → Integration with accounting system

### Approval Matrix Example

| Amount Range | Department | Approvers |
|-------------|------------|-----------|
| $0 - $5,000 | Any | Department Manager |
| $5,001 - $50,000 | IT/Operations | Department Manager + IT Director |
| $5,001 - $50,000 | Sales/Marketing | Department Manager + CFO |
| $50,001+ | Any | Department Manager + Director + CFO |

### 3-Way Match Tolerance Rules

| Variance Type | Tolerance | Action |
|---------------|-----------|--------|
| Price | ±5% | Auto-approve |
| Quantity | ±2% | Auto-approve |
| Price | 5-10% | Route to supervisor |
| Quantity | 2-5% | Route to supervisor |
| Price/Quantity | >10%/5% | Reject, require investigation |

## Security Features

### Separation of Duties (SoD)

- **Requester ≠ Approver**: Person requesting cannot approve their own requisition
- **Creator ≠ Receiver**: PO creator cannot perform goods receipt
- **Receiver ≠ Payment Authorizer**: GRN creator cannot authorize invoice payment
- **Automated Enforcement**: System blocks violations with audit logging

### Data Protection

- **Tenant Isolation**: All data scoped to tenant (via nexus-tenancy)
- **Encrypted Fields**: Bank accounts, tax IDs encrypted at rest
- **Audit Logging**: All create/update/delete operations logged
- **Role-Based Access**: Permissions enforced per user role

### Vendor Portal Security

- **Secure Authentication**: Separate auth guard for vendor users
- **Session Management**: Automatic logout on inactivity
- **Data Access Control**: Vendors can only see their own data
- **Rate Limiting**: API rate limits to prevent abuse

## Performance Optimization

### Database Indexes

Comprehensive indexing strategy for optimal query performance:

- **Tenant-scoped queries**: `tenant_id` prefixed on all multi-tenant queries
- **Status filtering**: Status columns indexed for workflow queries
- **Date ranges**: Created/updated timestamps indexed for analytics
- **Foreign keys**: All FK relationships indexed for joins

### Query Optimization

- **Eager loading**: Strategic use of `with()` for related data
- **Pagination**: All list endpoints paginated (15 items default)
- **Caching**: Redis caching for reference data (vendors, GL accounts)
- **Background processing**: Heavy operations queued (PDF generation, email sending)

### Monitoring

- **Performance metrics**: Response times tracked per endpoint
- **Database monitoring**: Slow query logging enabled
- **Cache hit rates**: Redis performance monitoring
- **Queue monitoring**: Background job processing stats

## Testing

### Test Structure

```bash
# Run all tests
php artisan test

# Run procurement tests only
php artisan test --filter=Procurement

# Run with coverage
php artisan test --coverage --min=80
```

### Test Categories

- **Unit Tests**: Business logic, calculations, validation rules
- **Feature Tests**: Complete workflows (requisition → payment)
- **Integration Tests**: External service integrations
- **Performance Tests**: Load testing for high-volume scenarios

### Test Data

Factories provided for all entities:
- `PurchaseRequisitionFactory`
- `PurchaseOrderFactory`
- `VendorFactory`
- `VendorInvoiceFactory`

## Deployment

### Production Checklist

- [ ] Environment variables configured
- [ ] Database migrations run
- [ ] Redis cache/queue configured
- [ ] File storage configured (for attachments)
- [ ] Email service configured
- [ ] SSL certificates installed
- [ ] Backup strategy implemented
- [ ] Monitoring tools configured

### Environment Variables

```bash
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nexus_erp
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@nexus-erp.com"
MAIL_FROM_NAME="${APP_NAME}"

# File Storage
FILESYSTEM_DISK=local
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false
```

### Health Checks

```bash
# Database connectivity
php artisan tinker --execute="DB::connection()->getPdo()"

# Queue status
php artisan queue:status

# Cache status
php artisan tinker --execute="Cache::store()->getStore()->connection()->ping()"
```

## Troubleshooting

### Common Issues

#### High Memory Usage
- **Cause**: Large result sets without pagination
- **Solution**: Ensure all list endpoints use pagination
- **Prevention**: Add memory limits to queue workers

#### Slow Queries
- **Cause**: Missing database indexes
- **Solution**: Run performance index migration
- **Monitoring**: Enable slow query log in MySQL

#### Email Delivery Issues
- **Cause**: SMTP configuration incorrect
- **Solution**: Verify mail settings and test with MailHog
- **Fallback**: Use log driver for development

#### File Upload Failures
- **Cause**: Storage permissions or disk space
- **Solution**: Check directory permissions and available space
- **Security**: Validate file types and sizes

### Debug Commands

```bash
# Check package installation
php artisan package:discover

# Verify migrations
php artisan migrate:status

# Test API endpoints
curl -H "Authorization: Bearer {token}" /api/procurement/dashboard

# Check queue status
php artisan queue:failed
php artisan queue:retry all
```

## Contributing

### Development Setup

1. **Clone repository**
   ```bash
   git clone https://github.com/azaharizaman/nexus-erp.git
   cd nexus-erp
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Setup environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Run migrations**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Run tests**
   ```bash
   php artisan test
   ```

### Code Standards

- **PSR-12**: PHP coding standards
- **Laravel Conventions**: Follow Laravel naming conventions
- **Documentation**: All public methods documented with PHPDoc
- **Testing**: Minimum 80% code coverage required

### Commit Guidelines

```bash
# Feature commits
feat: add vendor performance analytics

# Bug fixes
fix: resolve 3-way match tolerance calculation

# Documentation
docs: update API reference for vendor portal

# Performance
perf: add database indexes for PO queries
```

## License

This package is licensed under the MIT License. See [LICENSE](LICENSE) file for details.

## Support

## Documentation

### User Documentation
- **[User Guide](docs/user-guide.md)**: Complete guide for all user roles (requesters, approvers, procurement officers, vendors)
- **[Video Tutorials](docs/video-tutorials.md)**: 14 comprehensive video tutorials covering all workflows
- **[Troubleshooting Guide](docs/troubleshooting.md)**: Common issues and solutions

### Technical Documentation
- **[API Reference](docs/api-reference.md)**: Complete REST API documentation with examples
- **[Deployment Guide](docs/deployment-guide.md)**: Production setup, configuration, and maintenance
- **[System Architecture](docs/system-architecture.md)**: Technical design and integration details

### Additional Resources
- [Configuration Guide](docs/configuration.md)
- [Migration Guide](docs/migration-guide.md)
- [Security Guide](docs/security.md)
- [Performance Tuning](docs/performance-tuning.md)

### Community
- [GitHub Issues](https://github.com/azaharizaman/nexus-erp/issues)
- [Discussions](https://github.com/azaharizaman/nexus-erp/discussions)

### Enterprise Support
For enterprise support, custom development, or training:
- Email: support@nexus-erp.com
- Website: https://nexus-erp.com/support

---

**Built with ❤️ for the Laravel community**

*Transforming procurement processes, one workflow at a time.*