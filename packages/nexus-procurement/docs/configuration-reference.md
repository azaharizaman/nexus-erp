# Nexus Procurement Configuration Reference

## Overview

This document provides comprehensive reference for all Nexus Procurement configuration options, designed for system administrators and developers configuring the procurement domain.

## Configuration Structure

### Configuration Files

The package uses multiple configuration files for different concerns:

- `config/procurement.php` - Core procurement domain settings
- `config/vendor-portal.php` - Vendor portal configuration
- `database/migrations/` - Database schema configuration
- Runtime configuration via `nexus-settings` package

### Environment Variables

Configuration values can be overridden using environment variables with the `PROCUREMENT_` prefix.

## Core Procurement Configuration

### Domain Settings

```php
'domain' => [
    // Default currency for procurement transactions
    'currency' => env('PROCUREMENT_DEFAULT_CURRENCY', 'USD'),

    // Application timezone for procurement operations
    'timezone' => env('PROCUREMENT_TIMEZONE', 'UTC'),

    // Locale for number/date formatting
    'locale' => env('PROCUREMENT_LOCALE', 'en'),

    // Default country for tax calculations
    'country' => env('PROCUREMENT_COUNTRY', 'US'),

    // Fiscal year start month (1-12)
    'fiscal_year_start' => env('PROCUREMENT_FISCAL_YEAR_START', 1),

    // Working days configuration for SLA calculations
    'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],

    // Holiday calendar for SLA adjustments
    'holidays' => [
        '2024-01-01', // New Year's Day
        '2024-12-25', // Christmas
    ],
],
```

### Approval Configuration

```php
'approvals' => [
    // Amount threshold for auto-approval (bypasses workflow)
    'auto_approve_limit' => env('PROCUREMENT_AUTO_APPROVE_LIMIT', 1000),

    // Days before escalation to next approval level
    'escalation_days' => env('PROCUREMENT_ESCALATION_DAYS', 7),

    // Maximum approval levels before CFO involvement
    'max_levels' => env('PROCUREMENT_MAX_APPROVAL_LEVELS', 4),

    // Approval matrix definition
    'matrix' => [
        [
            'name' => 'Standard Approval Matrix',
            'active' => true,
            'rules' => [
                [
                    'condition' => 'total_amount <= 5000',
                    'approvers' => ['department_manager'],
                    'escalation_days' => 3,
                ],
                [
                    'condition' => 'total_amount > 5000 AND total_amount <= 50000',
                    'approvers' => ['department_manager', 'division_director'],
                    'escalation_days' => 5,
                ],
                [
                    'condition' => 'total_amount > 50000',
                    'approvers' => ['department_manager', 'division_director', 'cfo'],
                    'escalation_days' => 7,
                ],
            ],
        ],
    ],

    // Department-specific approval rules
    'department_overrides' => [
        'IT' => [
            'capital_expenditure_threshold' => 25000,
            'requires_technical_approval' => true,
        ],
        'HR' => [
            'auto_approve_limit' => 500,
            'requires_legal_approval' => true,
        ],
    ],

    // GL account-based approval rules
    'gl_account_rules' => [
        '6000-*' => ['requires_budget_approval' => true], // CapEx accounts
        '7000-*' => ['requires_legal_approval' => true],  // Contract accounts
    ],
],
```

### 3-Way Matching Configuration

```php
'matching' => [
    // Price variance tolerance (percentage)
    'price_tolerance_percent' => env('PROCUREMENT_MATCH_PRICE_TOLERANCE', 5.0),

    // Quantity variance tolerance (percentage)
    'quantity_tolerance_percent' => env('PROCUREMENT_MATCH_QUANTITY_TOLERANCE', 2.0),

    // Auto-approve matches within tolerance
    'auto_approve_within_tolerance' => env('PROCUREMENT_AUTO_APPROVE_MATCH', true),

    // Maximum variance before requiring investigation
    'escalation_threshold_percent' => env('PROCUREMENT_MATCH_ESCALATION_THRESHOLD', 10.0),

    // Matching rules by document type
    'rules' => [
        'standard' => [
            'require_po' => true,
            'require_receipt' => true,
            'require_invoice' => true,
            'allow_partial_matches' => false,
        ],
        'service' => [
            'require_po' => true,
            'require_receipt' => false, // Services don't have physical receipts
            'require_invoice' => true,
            'allow_partial_matches' => true,
        ],
    ],

    // Tolerance overrides by vendor or category
    'tolerance_overrides' => [
        'strategic_vendors' => [
            'price_tolerance_percent' => 10.0,
            'quantity_tolerance_percent' => 5.0,
        ],
        'categories' => [
            'IT' => ['price_tolerance_percent' => 3.0],
            'Facilities' => ['quantity_tolerance_percent' => 5.0],
        ],
    ],
],
```

### Separation of Duties Configuration

```php
'separation_of_duties' => [
    // Enable SoD enforcement
    'enabled' => env('PROCUREMENT_SOD_ENABLED', true),

    // Core SoD rules
    'rules' => [
        'requester_cannot_approve' => env('PROCUREMENT_SOD_REQUESTER_CANNOT_APPROVE', true),
        'creator_cannot_receive' => env('PROCUREMENT_SOD_CREATOR_CANNOT_RECEIVE', true),
        'single_user_complete_cycle' => env('PROCUREMENT_SOD_SINGLE_USER_COMPLETE', false),
        'approver_cannot_modify_after_approval' => true,
    ],

    // Department-specific SoD rules
    'department_rules' => [
        'Finance' => [
            'additional_checks' => ['budget_owner_separation'],
            'restricted_combinations' => ['budget_approval + payment_processing'],
        ],
        'IT' => [
            'technical_approval_required' => true,
            'vendor_relation_restricted' => true,
        ],
    ],

    // Monitoring and alerting
    'monitoring' => [
        'log_violations' => true,
        'alert_on_violation' => true,
        'violation_grace_period_days' => 7,
        'escalation_contacts' => ['compliance@company.com'],
    ],
],
```

### Document Numbering Configuration

```php
'numbering' => [
    // Document number formats
    'formats' => [
        'requisition' => 'REQ-{YYYY}-{NNNNNN}',
        'purchase_order' => 'PO-{YYYY}-{NNNNNN}',
        'goods_receipt' => 'GRN-{YYYY}-{NNNNNN}',
        'vendor_invoice' => 'INV-{YYYY}-{NNNNNN}',
        'rfq' => 'RFQ-{YYYY}-{NNNNNN}',
        'contract' => 'CON-{YYYY}-{NNNNNN}',
        'blanket_po' => 'BPO-{YYYY}-{NNNNNN}',
    ],

    // Numbering sequences
    'sequences' => [
        'requisition' => [
            'start' => 100001,
            'increment' => 1,
            'reset' => 'yearly',
        ],
        'purchase_order' => [
            'start' => 200001,
            'increment' => 1,
            'reset' => 'yearly',
        ],
    ],

    // Tenant-specific numbering
    'tenant_overrides' => [
        'tenant_123' => [
            'requisition' => 'REQ-{TENANT}-{YYYY}-{NNNNN}',
            'purchase_order' => 'PO-{TENANT}-{YYYY}-{NNNNN}',
        ],
    ],
],
```

## Vendor Portal Configuration

### Portal Settings

```php
'portal' => [
    // Portal enablement
    'enabled' => env('VENDOR_PORTAL_ENABLED', true),

    // Portal domain configuration
    'domain' => env('VENDOR_PORTAL_DOMAIN', 'portal.company.com'),

    // SSL requirements
    'ssl' => env('VENDOR_PORTAL_SSL', true),

    // Session configuration
    'session' => [
        'lifetime' => env('VENDOR_PORTAL_SESSION_LIFETIME', 480), // minutes
        'domain' => env('VENDOR_PORTAL_DOMAIN'),
        'secure' => true,
        'http_only' => true,
    ],

    // Portal feature toggles
    'features' => [
        'po_viewing' => env('VENDOR_PORTAL_PO_VIEWING', true),
        'invoice_submission' => env('VENDOR_PORTAL_INVOICE_SUBMISSION', true),
        'payment_tracking' => env('VENDOR_PORTAL_PAYMENT_TRACKING', true),
        'performance_analytics' => env('VENDOR_PORTAL_PERFORMANCE', true),
        'rfq_participation' => env('VENDOR_PORTAL_RFQ', true),
        'contract_viewing' => env('VENDOR_PORTAL_CONTRACTS', true),
    ],
],
```

### Authentication Configuration

```php
'authentication' => [
    // Authentication guard
    'guard' => env('VENDOR_PORTAL_GUARD', 'vendor'),

    // Password policy
    'password' => [
        'min_length' => env('VENDOR_PORTAL_PASSWORD_MIN_LENGTH', 8),
        'require_uppercase' => env('VENDOR_PORTAL_PASSWORD_UPPERCASE', true),
        'require_numbers' => env('VENDOR_PORTAL_PASSWORD_NUMBERS', true),
        'require_symbols' => env('VENDOR_PORTAL_PASSWORD_SYMBOLS', false),
        'prevent_reuse' => env('VENDOR_PORTAL_PASSWORD_PREVENT_REUSE', true),
        'history_count' => env('VENDOR_PORTAL_PASSWORD_HISTORY', 5),
    ],

    // Password reset settings
    'password_reset' => [
        'enabled' => env('VENDOR_PORTAL_PASSWORD_RESET_ENABLED', true),
        'token_lifetime' => env('VENDOR_PORTAL_PASSWORD_RESET_LIFETIME', 60), // minutes
        'max_attempts' => env('VENDOR_PORTAL_PASSWORD_RESET_MAX_ATTEMPTS', 3),
    ],

    // Multi-factor authentication
    'mfa' => [
        'enabled' => env('VENDOR_PORTAL_MFA_ENABLED', false),
        'required' => env('VENDOR_PORTAL_MFA_REQUIRED', false),
        'methods' => ['totp', 'sms'],
    ],

    // Session management
    'session' => [
        'concurrent_sessions' => env('VENDOR_PORTAL_CONCURRENT_SESSIONS', 1),
        'idle_timeout' => env('VENDOR_PORTAL_IDLE_TIMEOUT', 30), // minutes
        'absolute_timeout' => env('VENDOR_PORTAL_ABSOLUTE_TIMEOUT', 480), // minutes
    ],
],
```

### Security Configuration

```php
'security' => [
    // Data encryption
    'encryption' => [
        'sensitive_data' => env('VENDOR_PORTAL_ENCRYPT_SENSITIVE', true),
        'communications' => env('VENDOR_PORTAL_ENCRYPT_COMMUNICATIONS', true),
        'storage' => env('VENDOR_PORTAL_ENCRYPT_STORAGE', true),
    ],

    // Rate limiting
    'rate_limiting' => [
        'enabled' => env('VENDOR_PORTAL_RATE_LIMITING_ENABLED', true),
        'attempts' => env('VENDOR_PORTAL_RATE_LIMITING_ATTEMPTS', 5),
        'decay_minutes' => env('VENDOR_PORTAL_RATE_LIMITING_DECAY', 15),
        'throttle_after_attempts' => 3,
    ],

    // IP restrictions
    'ip_restrictions' => [
        'enabled' => env('VENDOR_PORTAL_IP_RESTRICTIONS_ENABLED', false),
        'whitelist' => [],
        'blacklist' => [],
    ],

    // Audit logging
    'audit' => [
        'enabled' => env('VENDOR_PORTAL_AUDIT_ENABLED', true),
        'events' => [
            'login',
            'logout',
            'password_change',
            'data_access',
            'file_download',
        ],
        'retention_days' => env('VENDOR_PORTAL_AUDIT_RETENTION', 2555), // 7 years
    ],
],
```

## Integration Configuration

### Accounting Integration

```php
'integrations' => [
    'accounting' => [
        // Integration enablement
        'enabled' => env('ACCOUNTING_INTEGRATION_ENABLED', true),

        // Auto-posting configuration
        'auto_post' => [
            'purchase_orders' => env('ACCOUNTING_AUTO_POST_POS', true),
            'goods_receipts' => env('ACCOUNTING_AUTO_POST_RECEIPTS', true),
            'invoices' => env('ACCOUNTING_AUTO_POST_INVOICES', true),
            'payments' => env('ACCOUNTING_AUTO_POST_PAYMENTS', true),
        ],

        // GL account mappings
        'gl_accounts' => [
            'inventory' => env('ACCOUNTING_GL_INVENTORY', '1200-0000'),
            'accounts_payable' => env('ACCOUNTING_GL_AP', '2000-0000'),
            'purchase_variance' => env('ACCOUNTING_GL_VARIANCE', '5100-2000'),
            'purchase_discounts' => env('ACCOUNTING_GL_DISCOUNTS', '5100-1000'),
            'accrued_liabilities' => env('ACCOUNTING_GL_ACCRUED', '2100-0000'),
            'prepaid_expenses' => env('ACCOUNTING_GL_PREPAID', '1300-0000'),
        ],

        // Journal entry templates
        'journal_templates' => [
            'po_accrual' => [
                'debit' => 'expense_account',
                'credit' => 'accrued_liabilities',
                'description' => 'Purchase Order Accrual - {po_number}',
            ],
            'receipt' => [
                'debit' => 'inventory',
                'credit' => 'accrued_liabilities',
                'description' => 'Goods Receipt - {grn_number}',
            ],
            'invoice_match' => [
                'debit' => 'accrued_liabilities',
                'credit' => 'accounts_payable',
                'description' => 'Invoice Matching - {invoice_number}',
            ],
        ],

        // Tax configuration
        'tax' => [
            'enabled' => env('ACCOUNTING_TAX_ENABLED', true),
            'calculation_method' => 'inclusive', // inclusive or exclusive
            'default_rate' => env('ACCOUNTING_DEFAULT_TAX_RATE', 8.25),
            'tax_codes' => [
                'STANDARD' => 8.25,
                'REDUCED' => 4.0,
                'EXEMPT' => 0.0,
            ],
        ],
    ],
],
```

### Inventory Integration

```php
'inventory' => [
    // Integration enablement
    'enabled' => env('INVENTORY_INTEGRATION_ENABLED', true),

    // Auto-update configuration
    'auto_update' => [
        'stock_levels' => env('INVENTORY_AUTO_UPDATE_STOCK', true),
        'reservations' => env('INVENTORY_AUTO_UPDATE_RESERVATIONS', true),
        'valuations' => env('INVENTORY_AUTO_UPDATE_VALUATIONS', true),
    ],

    // Item tracking
    'tracking' => [
        'serial_numbers' => env('INVENTORY_TRACK_SERIAL_NUMBERS', true),
        'lot_numbers' => env('INVENTORY_TRACK_LOT_NUMBERS', false),
        'expiration_dates' => env('INVENTORY_TRACK_EXPIRATION', true),
    ],

    // Warehouse configuration
    'warehouses' => [
        'default_receiving' => env('INVENTORY_DEFAULT_RECEIVING_WH', 'WH001'),
        'default_shipping' => env('INVENTORY_DEFAULT_SHIPPING_WH', 'WH001'),
        'quarantine' => env('INVENTORY_QUARANTINE_WH', 'WH-QUARANTINE'),
    ],

    // Valuation methods
    'valuation' => [
        'method' => env('INVENTORY_VALUATION_METHOD', 'fifo'), // fifo, lifo, average
        'update_on_receipt' => env('INVENTORY_VALUATION_UPDATE_RECEIPT', true),
        'update_on_invoice' => env('INVENTORY_VALUATION_UPDATE_INVOICE', true),
    ],

    // Integration endpoints
    'api' => [
        'base_url' => env('INVENTORY_API_BASE_URL'),
        'timeout' => env('INVENTORY_API_TIMEOUT', 30),
        'retry_attempts' => env('INVENTORY_API_RETRY_ATTEMPTS', 3),
    ],
],
```

### Workflow Integration

```php
'workflow' => [
    // Integration enablement
    'enabled' => env('WORKFLOW_INTEGRATION_ENABLED', true),

    // Workflow mappings
    'workflows' => [
        'requisition_approval' => env('WORKFLOW_REQUISITION_APPROVAL', 'PROCUREMENT_REQUISITION'),
        'po_approval' => env('WORKFLOW_PO_APPROVAL', 'PROCUREMENT_PO'),
        'contract_approval' => env('WORKFLOW_CONTRACT_APPROVAL', 'PROCUREMENT_CONTRACT'),
        'invoice_approval' => env('WORKFLOW_INVOICE_APPROVAL', 'PROCUREMENT_INVOICE'),
    ],

    // Approval step configuration
    'steps' => [
        'department_manager' => [
            'name' => 'Department Manager Approval',
            'required' => true,
            'escalation_days' => 3,
        ],
        'division_director' => [
            'name' => 'Division Director Approval',
            'required' => true,
            'escalation_days' => 5,
        ],
        'cfo' => [
            'name' => 'CFO Approval',
            'required' => true,
            'escalation_days' => 7,
        ],
    ],

    // Notification configuration
    'notifications' => [
        'enabled' => env('WORKFLOW_NOTIFICATIONS_ENABLED', true),
        'channels' => ['email', 'in_app'],
        'templates' => [
            'approval_request' => 'procurement.approval.request',
            'approval_reminder' => 'procurement.approval.reminder',
            'approval_escalation' => 'procurement.approval.escalation',
        ],
    ],
],
```

### Audit Log Integration

```php
'audit' => [
    // Integration enablement
    'enabled' => env('AUDIT_LOG_INTEGRATION_ENABLED', true),

    // Audit events to log
    'events' => [
        // Requisition events
        'purchase_requisition.created',
        'purchase_requisition.updated',
        'purchase_requisition.approved',
        'purchase_requisition.rejected',

        // PO events
        'purchase_order.created',
        'purchase_order.updated',
        'purchase_order.approved',
        'purchase_order.amended',
        'purchase_order.sent_to_vendor',

        // Receipt events
        'goods_receipt.created',
        'goods_receipt.processed',
        'goods_receipt.quality_checked',

        // Invoice events
        'vendor_invoice.received',
        'vendor_invoice.matched',
        'vendor_invoice.approved',
        'vendor_invoice.paid',

        // Vendor events
        'vendor.created',
        'vendor.updated',
        'vendor.status_changed',

        // Portal events
        'vendor_portal.login',
        'vendor_portal.data_access',
        'vendor_portal.invoice_submitted',
    ],

    // Data retention
    'retention' => [
        'requisition_logs' => env('AUDIT_RETENTION_REQUISITIONS', 2555), // 7 years
        'transaction_logs' => env('AUDIT_RETENTION_TRANSACTIONS', 2555),
        'access_logs' => env('AUDIT_RETENTION_ACCESS', 365), // 1 year
    ],

    // Sensitive data masking
    'masking' => [
        'enabled' => env('AUDIT_MASKING_ENABLED', true),
        'fields' => [
            'password',
            'credit_card_number',
            'bank_account_number',
            'social_security_number',
        ],
    ],
],
```

## Performance Configuration

### Caching Configuration

```php
'caching' => [
    // Cache enablement
    'enabled' => env('PROCUREMENT_CACHING_ENABLED', true),

    // Cache driver
    'driver' => env('PROCUREMENT_CACHE_DRIVER', 'redis'),

    // Cache TTL settings (seconds)
    'ttl' => [
        'vendor_data' => env('PROCUREMENT_CACHE_VENDOR_TTL', 3600), // 1 hour
        'approval_matrix' => env('PROCUREMENT_CACHE_APPROVAL_TTL', 1800), // 30 minutes
        'analytics' => env('PROCUREMENT_CACHE_ANALYTICS_TTL', 7200), // 2 hours
        'reference_data' => env('PROCUREMENT_CACHE_REFERENCE_TTL', 86400), // 24 hours
    ],

    // Cache tags for selective clearing
    'tags' => [
        'vendors' => ['vendor', 'vendor_performance'],
        'approvals' => ['approval_matrix', 'approval_rules'],
        'analytics' => ['spend_analytics', 'performance_metrics'],
    ],
],
```

### Queue Configuration

```php
'queue' => [
    // Queue enablement
    'enabled' => env('PROCUREMENT_QUEUE_ENABLED', true),

    // Queue driver
    'driver' => env('PROCUREMENT_QUEUE_DRIVER', 'redis'),

    // Queue names
    'queues' => [
        'procurement' => env('PROCUREMENT_QUEUE_NAME', 'procurement'),
        'notifications' => env('PROCUREMENT_NOTIFICATIONS_QUEUE', 'procurement-notifications'),
        'analytics' => env('PROCUREMENT_ANALYTICS_QUEUE', 'procurement-analytics'),
    ],

    // Job configuration
    'jobs' => [
        'retry_attempts' => env('PROCUREMENT_JOB_RETRY_ATTEMPTS', 3),
        'timeout' => env('PROCUREMENT_JOB_TIMEOUT', 300), // 5 minutes
        'max_jobs' => env('PROCUREMENT_JOB_MAX_JOBS', 1000),
        'delay' => env('PROCUREMENT_JOB_DELAY', 60), // seconds
    ],

    // Failed job handling
    'failed' => [
        'database' => env('PROCUREMENT_FAILED_JOBS_DATABASE', 'failed_jobs'),
        'retention_days' => env('PROCUREMENT_FAILED_JOBS_RETENTION', 7),
    ],
],
```

### Database Optimization

```php
'database' => [
    // Index configuration
    'indexes' => [
        'enabled' => env('PROCUREMENT_DB_INDEXES_ENABLED', true),
        'performance_indexes' => env('PROCUREMENT_DB_PERFORMANCE_INDEXES', true),
        'tenant_indexes' => env('PROCUREMENT_DB_TENANT_INDEXES', true),
    ],

    // Query optimization
    'optimization' => [
        'eager_loading' => env('PROCUREMENT_DB_EAGER_LOADING', true),
        'query_chunking' => env('PROCUREMENT_DB_CHUNKING_ENABLED', true),
        'chunk_size' => env('PROCUREMENT_DB_CHUNK_SIZE', 1000),
    ],

    // Connection pooling
    'pooling' => [
        'enabled' => env('PROCUREMENT_DB_POOLING_ENABLED', true),
        'max_connections' => env('PROCUREMENT_DB_MAX_CONNECTIONS', 10),
        'idle_timeout' => env('PROCUREMENT_DB_IDLE_TIMEOUT', 60),
    ],
],
```

## Notification Configuration

### Email Notifications

```php
'notifications' => [
    // Email configuration
    'email' => [
        'enabled' => env('PROCUREMENT_EMAIL_ENABLED', true),
        'from_address' => env('PROCUREMENT_EMAIL_FROM', 'procurement@company.com'),
        'from_name' => env('PROCUREMENT_EMAIL_FROM_NAME', 'Procurement System'),
        'subject_prefix' => env('PROCUREMENT_EMAIL_SUBJECT_PREFIX', '[Procurement]'),
    ],

    // Notification templates
    'templates' => [
        'requisition_submitted' => 'procurement.requisition.submitted',
        'requisition_approved' => 'procurement.requisition.approved',
        'requisition_rejected' => 'procurement.requisition.rejected',
        'po_created' => 'procurement.po.created',
        'po_approved' => 'procurement.po.approved',
        'invoice_matched' => 'procurement.invoice.matched',
        'payment_processed' => 'procurement.payment.processed',
    ],

    // Recipient configuration
    'recipients' => [
        'requisition_approvers' => ['department_managers', 'procurement_team'],
        'po_notifications' => ['requisition_creator', 'procurement_team'],
        'invoice_alerts' => ['accounts_payable', 'procurement_team'],
        'vendor_notifications' => ['vendor_contacts'],
    ],
],
```

### In-App Notifications

```php
'in_app' => [
    'enabled' => env('PROCUREMENT_IN_APP_NOTIFICATIONS_ENABLED', true),

    // Notification types
    'types' => [
        'info' => ['icon' => 'info-circle', 'color' => 'blue'],
        'success' => ['icon' => 'check-circle', 'color' => 'green'],
        'warning' => ['icon' => 'exclamation-triangle', 'color' => 'yellow'],
        'error' => ['icon' => 'times-circle', 'color' => 'red'],
    ],

    // Notification persistence
    'persistence' => [
        'store_in_database' => env('PROCUREMENT_IN_APP_PERSISTENCE', true),
        'retention_days' => env('PROCUREMENT_IN_APP_RETENTION', 30),
    ],
],
```

This configuration reference provides comprehensive guidance for customizing Nexus Procurement to meet specific business requirements and integration needs.