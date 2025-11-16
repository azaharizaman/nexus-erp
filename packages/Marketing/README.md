# Nexus Marketing

> **Status**: 📝 Requirements Phase  
> **Version**: 1.0.0 (Not yet implemented)  
> **Package**: `nexus/marketing`

## 🎯 Overview

**nexus-marketing** is a progressive marketing automation engine for PHP/Laravel that scales from basic campaign tracking (5 minutes) to enterprise-grade marketing orchestration (production-ready).

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

## 📚 Documentation

For complete requirements, technical specifications, and implementation details, see:

👉 **[REQUIREMENTS.md](./REQUIREMENTS.md)** - Comprehensive requirements document

The REQUIREMENTS.md contains:
- Executive summary and package scope
- Core philosophy and architectural principles
- Problem statement and solution overview
- 27 user stories across 3 phases
- 40+ functional requirements
- Non-functional requirements (performance, security, reliability)
- Complete data model (14 database tables)
- API specifications (REST + GraphQL + webhooks)
- Development phases and timeline
- Testing strategy
- Success metrics

---

## 🚀 Quick Start (Phase 1 - Coming Soon)

Once implemented, you'll be able to use it like this:

### Installation

```bash
composer require nexus/marketing
```

### Basic Usage

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

// Launch campaign
$product->marketing()->launchCampaign('product_launch', [
    'target_segment' => 'early_adopters',
    'start_date' => now()->addDays(7),
]);

// Check campaign status
$product->marketing()->activeCampaigns();

// Get campaign history
$product->marketing()->history();
```

---

## 🎓 Progressive Disclosure Model

nexus-marketing follows a three-phase progression model:

### Phase 1: Basic Campaign Tracking (5 minutes)

- Add `HasMarketing` trait to your model
- Define campaigns as an array in your model
- Launch and track campaigns with simple API
- **Target**: 80% of users (mass market)

### Phase 2: Marketing Automation (1-2 hours)

- Database-driven campaigns with templates
- Multi-channel orchestration (email, SMS, social)
- Lead scoring and segmentation
- Campaign analytics and reporting
- **Target**: 15% of users (growing businesses)

### Phase 3: Enterprise Marketing (Production-ready)

- Advanced automation (behavioral triggers, drip campaigns)
- ROI tracking with budget constraints
- A/B testing engine
- GDPR compliance features
- Escalation and delegation workflows
- **Target**: 5% of users (enterprise)

**Backwards compatibility guaranteed**: Phase 1 code continues working after upgrading to Phase 2/3.

---

## 🏗️ Architecture

### Framework Agnostic Core

- **Core logic** (`src/Core/`) is pure PHP, no framework dependencies
- **Laravel adapter** (`src/Adapters/Laravel/`) provides framework integration
- Can be used in any PHP project, not just Laravel

### Atomic Package Design

Following Nexus ERP's **Maximum Atomicity** principle:

- ✅ **Self-contained**: All marketing logic within this package
- ✅ **Independently testable**: Complete test suite without external dependencies
- ✅ **Zero cross-package coupling**: No direct dependencies on other Nexus packages
- ✅ **Contract-driven**: Events and interfaces for integration

### API-First Design

- REST API endpoints for all operations
- GraphQL schema for flexible queries
- Webhook events for integrations
- No UI components (headless backend)

---

## 📊 Features by Phase

### Phase 1 Features

- [x] `HasMarketing` trait for models
- [x] In-model campaign definitions
- [x] Basic campaign lifecycle (launch, pause, complete)
- [x] Permission checks and guards
- [x] Campaign history/audit trail
- [x] Lifecycle hooks (before/after events)

### Phase 2 Features

- [x] Database-driven campaign templates
- [x] Campaign state machine (draft → active → paused → completed)
- [x] Multi-channel execution (email, SMS, webhook, social)
- [x] Lead management system
- [x] Audience segmentation with conditions
- [x] Engagement tracking (opens, clicks, conversions)
- [x] Campaign dashboard API
- [x] JSON schema validation

### Phase 3 Features

- [x] Automatic escalation for underperforming campaigns
- [x] ROI tracking with budget breach alerts
- [x] Campaign delegation system
- [x] Rollback and compensation logic
- [x] Custom metrics configuration
- [x] GDPR compliance (consent, deletion, export)
- [x] A/B testing engine
- [x] Advanced reporting and analytics
- [x] Drip campaign automation
- [x] Behavioral triggers

---

## 🧪 Testing

Comprehensive testing strategy:

- **Unit Tests**: > 90% coverage for core engine
- **Feature Tests**: 100% coverage of user stories
- **Integration Tests**: Laravel, multi-tenancy, audit logging
- **Load Tests**: 100,000+ campaigns, 1,000,000+ leads
- **Acceptance Tests**: All 27 user stories validated

---

## 📦 Dependencies

### Required

- PHP >= 8.3
- Database: MySQL >= 8.0, PostgreSQL >= 12, or SQLite >= 3.35

### Optional

- Laravel >= 12.x (for framework integration)
- Redis >= 7.0 (for caching and queues)

**Note**: Integration with nexus-tenancy and nexus-audit-log is handled via contracts and events in the nexus/erp orchestration layer, not as direct package dependencies.

---

## 🤝 Contributing

This package is part of the [Nexus ERP](https://github.com/azaharizaman/nexus-erp) monorepo.

To contribute:

1. Read [REQUIREMENTS.md](./REQUIREMENTS.md) to understand specifications
2. Follow [Nexus ERP Coding Guidelines](../../CODING_GUIDELINES.md)
3. Adhere to architectural principles in [System Architecture Document](../../docs/SYSTEM%20ARCHITECTURAL%20DOCUMENT.md)
4. Write tests (Pest framework)
5. Submit PR to main repository

---

## 📄 License

MIT License - See [LICENSE](../../LICENSE) file for details

---

## 🔗 Related Packages

Part of the Nexus ERP ecosystem:

- **nexus-sales** - Sales automation (quotes, orders, invoicing)
- **nexus-crm** - Customer relationship management
- **nexus-backoffice** - Organization structure (companies, offices, departments)
- **nexus-inventory** - Inventory management
- **nexus-accounting** - General ledger and accounting
- **nexus-tenancy** - Multi-tenancy support
- **nexus-audit-log** - Audit trail and activity logging

---

## 🎯 Success Metrics

| Metric | Target | Timeframe |
|--------|--------|-----------|
| Total installations | > 2,000 | 6 months |
| Active users | > 500 | 6 months |
| Phase 2 adoption | > 10% | 6 months |
| Phase 3 adoption | > 5% | 6 months |
| Test coverage | > 85% | Ongoing |
| P0 bugs | < 5 | 6 months |
| Hello World time | < 5 min | Ongoing |

---

## 📞 Support

- **Documentation**: [REQUIREMENTS.md](./REQUIREMENTS.md)
- **Issues**: [GitHub Issues](https://github.com/azaharizaman/nexus-erp/issues)
- **Discussions**: [GitHub Discussions](https://github.com/azaharizaman/nexus-erp/discussions)

---

**Current Status**: Requirements phase complete. Implementation starting soon.

For detailed technical specifications, see [REQUIREMENTS.md](./REQUIREMENTS.md).
