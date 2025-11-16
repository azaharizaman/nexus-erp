# Nexus Procurement Deployment Guide

## Overview

This guide provides comprehensive instructions for deploying and configuring the Nexus Procurement package in development, staging, and production environments.

## Prerequisites

### System Requirements

- **PHP**: 8.3 or higher
- **Laravel**: 12.x
- **Database**: MySQL 8.0+, PostgreSQL 13+, or SQL Server 2019+
- **Redis**: 6.0+ (for caching and queues)
- **Node.js**: 18+ (for asset compilation)
- **Composer**: 2.0+

### Required Packages

The procurement package requires the following Nexus packages:

```json
{
    "require": {
        "nexus/erp": "^1.0",
        "nexus/tenancy": "^1.0",
        "nexus/workflow": "^1.0",
        "nexus/accounting": "^1.0",
        "nexus/inventory": "^1.0",
        "nexus/audit-log": "^1.0",
        "nexus/settings": "^1.0"
    }
}
```

## Installation

### 1. Package Installation

Install the procurement package via Composer:

```bash
composer require nexus/procurement
```

### 2. Service Provider Registration

The service provider is auto-discovered in Laravel 11+. If using an older version, manually register it in `config/app.php`:

```php
'providers' => [
    // ... other providers
    Nexus\Procurement\ProcurementServiceProvider::class,
],
```

### 3. Configuration Publishing

Publish the configuration files:

```bash
# Publish procurement configuration
php artisan vendor:publish --provider="Nexus\Procurement\ProcurementServiceProvider" --tag=config

# Publish vendor portal configuration
php artisan vendor:publish --provider="Nexus\Procurement\ProcurementServiceProvider" --tag=vendor-portal-config

# Publish migration files
php artisan vendor:publish --provider="Nexus\Procurement\ProcurementServiceProvider" --tag=migrations
```

### 4. Database Migration

Run the database migrations:

```bash
php artisan migrate
```

For multi-tenant installations, run migrations for each tenant:

```bash
php artisan tenancy:migrate
```

### 5. Seed Initial Data

Seed the procurement package with initial data:

```bash
php artisan db:seed --class="Nexus\\Procurement\\Database\\Seeders\\ProcurementSeeder"
```

## Environment Configuration

### Environment Variables

Configure the following environment variables in your `.env` file:

```bash
# Core Procurement Settings
PROCUREMENT_DEFAULT_CURRENCY=USD
PROCUREMENT_TIMEZONE=UTC
PROCUREMENT_LOCALE=en
PROCUREMENT_COUNTRY=US
PROCUREMENT_FISCAL_YEAR_START=1

# Approval Configuration
PROCUREMENT_AUTO_APPROVE_LIMIT=1000
PROCUREMENT_ESCALATION_DAYS=7
PROCUREMENT_MAX_APPROVAL_LEVELS=4

# 3-Way Matching
PROCUREMENT_MATCH_PRICE_TOLERANCE=5.0
PROCUREMENT_MATCH_QUANTITY_TOLERANCE=2.0
PROCUREMENT_AUTO_APPROVE_MATCH=true

# Separation of Duties
PROCUREMENT_SOD_ENABLED=true
PROCUREMENT_SOD_REQUESTER_CANNOT_APPROVE=true

# Vendor Portal
VENDOR_PORTAL_ENABLED=true
VENDOR_PORTAL_DOMAIN=portal.company.com
VENDOR_PORTAL_SSL=true

# Integration Settings
ACCOUNTING_INTEGRATION_ENABLED=true
INVENTORY_INTEGRATION_ENABLED=true
WORKFLOW_INTEGRATION_ENABLED=true
AUDIT_LOG_INTEGRATION_ENABLED=true

# Performance Settings
PROCUREMENT_CACHING_ENABLED=true
PROCUREMENT_CACHE_DRIVER=redis
PROCUREMENT_QUEUE_ENABLED=true
PROCUREMENT_QUEUE_DRIVER=redis

# Notification Settings
PROCUREMENT_EMAIL_ENABLED=true
PROCUREMENT_EMAIL_FROM=procurement@company.com
PROCUREMENT_IN_APP_NOTIFICATIONS_ENABLED=true
```

### Multi-Tenant Configuration

For multi-tenant deployments, configure tenant-specific settings:

```php
// In your tenant configuration
'procurement' => [
    'currency' => 'EUR', // Override per tenant
    'approval_matrix' => 'custom_matrix',
    'vendor_portal_domain' => 'tenant1-portal.company.com',
],
```

## Queue Configuration

### Queue Worker Setup

Configure queue workers for background processing:

```bash
# Create queue worker for procurement jobs
php artisan queue:work --queue=procurement --sleep=3 --tries=3

# Create separate worker for notifications
php artisan queue:work --queue=procurement-notifications --sleep=3 --tries=3

# Create worker for analytics processing
php artisan queue:work --queue=procurement-analytics --sleep=3 --tries=3
```

### Supervisor Configuration

For production deployments, use Supervisor to manage queue workers:

```ini
[program:procurement-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=procurement --sleep=3 --tries=3
directory=/path/to/project
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/procurement-worker.log
```

## Caching Configuration

### Redis Configuration

Configure Redis for caching and sessions:

```php
// config/database.php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
    ],
    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_CACHE_DB', 1),
    ],
    'queues' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_QUEUE_DB', 2),
    ],
],
```

### Cache Tags

The package uses cache tags for efficient cache management:

```php
// Clear vendor-related caches
Cache::tags(['vendor', 'vendor_performance'])->flush();

// Clear approval-related caches
Cache::tags(['approval_matrix', 'approval_rules'])->flush();

// Clear analytics caches
Cache::tags(['spend_analytics', 'performance_metrics'])->flush();
```

## Integration Setup

### Accounting Integration

Configure accounting integration settings:

```php
// config/procurement.php
'integrations' => [
    'accounting' => [
        'enabled' => true,
        'auto_post' => [
            'purchase_orders' => true,
            'goods_receipts' => true,
            'invoices' => true,
            'payments' => true,
        ],
        'gl_accounts' => [
            'inventory' => '1200-0000',
            'accounts_payable' => '2000-0000',
            'purchase_variance' => '5100-2000',
            'purchase_discounts' => '5100-1000',
        ],
    ],
],
```

### Inventory Integration

Configure inventory integration:

```php
'inventory' => [
    'enabled' => true,
    'auto_update' => [
        'stock_levels' => true,
        'reservations' => true,
        'valuations' => true,
    ],
    'tracking' => [
        'serial_numbers' => true,
        'lot_numbers' => false,
        'expiration_dates' => true,
    ],
    'warehouses' => [
        'default_receiving' => 'WH001',
        'default_shipping' => 'WH001',
        'quarantine' => 'WH-QUARANTINE',
    ],
],
```

### Workflow Integration

Configure workflow integration:

```php
'workflow' => [
    'enabled' => true,
    'workflows' => [
        'requisition_approval' => 'PROCUREMENT_REQUISITION',
        'po_approval' => 'PROCUREMENT_PO',
        'contract_approval' => 'PROCUREMENT_CONTRACT',
        'invoice_approval' => 'PROCUREMENT_INVOICE',
    ],
],
```

### Audit Log Integration

Configure audit logging:

```php
'audit' => [
    'enabled' => true,
    'events' => [
        'purchase_requisition.created',
        'purchase_order.approved',
        'goods_receipt.processed',
        'vendor_invoice.matched',
        // ... other events
    ],
    'retention' => [
        'requisition_logs' => 2555, // 7 years
        'transaction_logs' => 2555,
        'access_logs' => 365, // 1 year
    ],
],
```

## Security Configuration

### Authentication Setup

Configure authentication guards for the vendor portal:

```php
// config/auth.php
'guards' => [
    // ... existing guards
    'vendor' => [
        'driver' => 'session',
        'provider' => 'vendors',
    ],
],

'providers' => [
    // ... existing providers
    'vendors' => [
        'driver' => 'eloquent',
        'model' => Nexus\Procurement\Models\Vendor::class,
    ],
],
```

### API Authentication

Configure API authentication for REST endpoints:

```php
// Use Laravel Sanctum for API authentication
// config/sanctum.php
'stateful' => [
    // Add your procurement API domains
    'portal.company.com',
    'api.company.com',
],
```

### CORS Configuration

Configure CORS for the vendor portal:

```php
// config/cors.php
'paths' => [
    'api/procurement/*',
    'api/vendor-portal/*',
],

'allowed_origins' => [
    'https://portal.company.com',
    'https://app.company.com',
],

'allowed_headers' => [
    'Authorization',
    'Content-Type',
    'X-Requested-With',
    'X-Tenant-ID',
],
```

## Performance Optimization

### Database Optimization

Run database optimization commands:

```bash
# Analyze and optimize tables
php artisan db:monitor

# Create performance indexes
php artisan procurement:optimize-indexes

# Update table statistics
php artisan procurement:update-statistics
```

### Asset Compilation

Compile and optimize frontend assets:

```bash
# Install dependencies
npm install

# Compile assets for development
npm run dev

# Compile and minify for production
npm run build

# Watch for changes during development
npm run watch
```

### Monitoring Setup

Configure monitoring and alerting:

```php
// config/procurement.php
'monitoring' => [
    'enabled' => true,
    'metrics' => [
        'response_times' => true,
        'error_rates' => true,
        'queue_depth' => true,
        'cache_hit_rates' => true,
    ],
    'alerts' => [
        'queue_depth_threshold' => 1000,
        'error_rate_threshold' => 5.0, // percentage
        'response_time_threshold' => 5000, // milliseconds
    ],
],
```

## Deployment Checklist

### Pre-Deployment

- [ ] Environment variables configured
- [ ] Database migrations run
- [ ] Initial data seeded
- [ ] Queue workers configured
- [ ] Cache backend configured
- [ ] Integrations tested
- [ ] Security settings verified

### Production Deployment

- [ ] SSL certificates installed
- [ ] Domain configured
- [ ] CDN setup for assets
- [ ] Backup strategy implemented
- [ ] Monitoring tools configured
- [ ] Log aggregation setup
- [ ] Performance benchmarks established

### Post-Deployment

- [ ] Smoke tests executed
- [ ] Integration tests passed
- [ ] User acceptance testing completed
- [ ] Performance monitoring active
- [ ] Backup verification completed
- [ ] Documentation updated

## Troubleshooting

### Common Issues

#### Queue Jobs Not Processing

```bash
# Check queue status
php artisan queue:status

# Clear failed jobs
php artisan queue:clear

# Restart queue workers
php artisan queue:restart
```

#### Cache Issues

```bash
# Clear application cache
php artisan cache:clear

# Clear procurement-specific caches
php artisan procurement:clear-cache

# Rebuild cache
php artisan procurement:rebuild-cache
```

#### Database Connection Issues

```bash
# Test database connection
php artisan tinker
DB::connection()->getPdo();

# Check migration status
php artisan migrate:status

# Rollback and re-run migrations if needed
php artisan migrate:rollback --step=1
php artisan migrate
```

#### Integration Failures

```bash
# Test accounting integration
php artisan procurement:test-integration accounting

# Test inventory integration
php artisan procurement:test-integration inventory

# Test workflow integration
php artisan procurement:test-integration workflow
```

## Maintenance Tasks

### Regular Maintenance

```bash
# Daily tasks
php artisan procurement:cleanup-expired-sessions
php artisan procurement:archive-old-logs

# Weekly tasks
php artisan procurement:update-vendor-performance
php artisan procurement:optimize-database

# Monthly tasks
php artisan procurement:generate-monthly-reports
php artisan procurement:cleanup-audit-logs
```

### Backup Strategy

```bash
# Database backup
mysqldump procurement_db > procurement_backup_$(date +%Y%m%d).sql

# Configuration backup
tar -czf config_backup_$(date +%Y%m%d).tar.gz config/

# Log rotation
logrotate /etc/logrotate.d/procurement
```

This deployment guide ensures successful installation and configuration of the Nexus Procurement package across all environments.