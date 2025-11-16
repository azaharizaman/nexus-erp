# Nexus Procurement API Integration Guide

## Overview

This guide provides technical details for developers integrating external systems with the Nexus Procurement domain. All integrations are designed around domain events, REST APIs, and programmatic service access.

## Authentication & Authorization

### API Token Authentication

All procurement APIs require Bearer token authentication:

```http
Authorization: Bearer {access_token}
```

#### Obtaining Tokens

**Internal Users:**
```bash
curl -X POST https://api.yourapp.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "procurement.manager@company.com",
    "password": "secure_password"
  }'
```

**Vendor Portal Users:**
```bash
curl -X POST https://portal.yourcompany.com/api/vendor/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "vendor.rep@supplier.com",
    "password": "vendor_password"
  }'
```

#### Token Management

Tokens include scope limitations and expiration:

```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "scopes": ["procurement:read", "procurement:write"],
  "tenant_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

### Multi-Tenant Context

All requests must include tenant context:

```http
X-Tenant-ID: 550e8400-e29b-41d4-a716-446655440000
```

Or via subdomain routing in multi-tenant deployments.

## Core Procurement APIs

### Purchase Requisitions

#### Create Requisition

```http
POST /api/procurement/requisitions
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "department_id": "550e8400-e29b-41d4-a716-446655440001",
  "requester_id": "550e8400-e29b-41d4-a716-446655440002",
  "justification": "Q4 project requirements for new marketing campaign",
  "required_date": "2024-12-31",
  "currency": "USD",
  "items": [
    {
      "description": "High-resolution cameras for product photography",
      "quantity": 2,
      "unit_price": 2500.00,
      "gl_account": "6000-1500",
      "category": "equipment"
    },
    {
      "description": "Professional lighting kit",
      "quantity": 1,
      "unit_price": 800.00,
      "gl_account": "6000-1500",
      "category": "equipment"
    }
  ]
}
```

**Response:**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440003",
  "requisition_number": "REQ-2024-001",
  "status": "draft",
  "total_amount": 5800.00,
  "created_at": "2024-11-15T10:00:00Z",
  "next_approvers": [
    {
      "user_id": "550e8400-e29b-41d4-a716-446655440004",
      "role": "manager",
      "reason": "Department manager approval required"
    }
  ]
}
```

#### Query Requisitions

```http
GET /api/procurement/requisitions?status=pending_approval&department_id={dept_id}&page=1&per_page=20
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

**Response:**
```json
{
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440003",
      "requisition_number": "REQ-2024-001",
      "department": "Marketing",
      "requester": "John Smith",
      "status": "pending_approval",
      "total_amount": 5800.00,
      "required_date": "2024-12-31",
      "created_at": "2024-11-15T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 45,
    "total_pages": 3
  }
}
```

#### Approve/Reject Requisition

```http
POST /api/procurement/requisitions/{requisition_id}/approve
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "action": "approve",
  "comments": "Approved for Q4 marketing budget",
  "approved_amount": 5800.00
}
```

### Purchase Orders

#### Create Purchase Order

```http
POST /api/procurement/purchase-orders
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "requisition_id": "550e8400-e29b-41d4-a716-446655440003",
  "vendor_id": "550e8400-e29b-41d4-a716-446655440005",
  "order_date": "2024-11-15",
  "delivery_date": "2024-12-01",
  "payment_terms": "net_30",
  "shipping_terms": "FOB",
  "currency": "USD",
  "notes": "Urgent delivery required for project deadline",
  "items": [
    {
      "requisition_item_id": "550e8400-e29b-41d4-a716-446655440006",
      "description": "High-resolution camera",
      "quantity": 2,
      "unit_price": 2400.00,
      "tax_rate": 8.25
    }
  ]
}
```

#### Get Purchase Order Details

```http
GET /api/procurement/purchase-orders/{po_id}
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

**Response:**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440007",
  "po_number": "PO-2024-001",
  "requisition_number": "REQ-2024-001",
  "vendor": {
    "id": "550e8400-e29b-41d4-a716-446655440005",
    "name": "Professional Imaging Ltd",
    "rating": 4.5
  },
  "status": "approved",
  "order_date": "2024-11-15",
  "delivery_date": "2024-12-01",
  "total_amount": 5800.00,
  "tax_amount": 478.50,
  "currency": "USD",
  "approvals": [
    {
      "approved_by": "Jane Manager",
      "approved_at": "2024-11-15T11:30:00Z",
      "comments": "Approved for marketing project"
    }
  ]
}
```

#### Amend Purchase Order

```http
POST /api/procurement/purchase-orders/{po_id}/amend
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "reason": "Extended delivery date due to vendor constraints",
  "changes": {
    "delivery_date": "2024-12-15",
    "items": [
      {
        "id": "550e8400-e29b-41d4-a716-446655440008",
        "quantity": 3,
        "reason": "Additional unit required"
      }
    ]
  }
}
```

### Goods Receipt Processing

#### Create Goods Receipt

```http
POST /api/procurement/goods-receipts
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "po_id": "550e8400-e29b-41d4-a716-446655440007",
  "receipt_date": "2024-12-01",
  "receiver_id": "550e8400-e29b-41d4-a716-446655440009",
  "warehouse_id": "550e8400-e29b-41d4-a716-446655440010",
  "carrier_name": "FedEx",
  "delivery_note_number": "FX123456789",
  "condition": "good",
  "items": [
    {
      "po_item_id": "550e8400-e29b-41d4-a716-446655440008",
      "quantity_received": 2,
      "quantity_accepted": 2,
      "quantity_rejected": 0,
      "serial_numbers": ["SN001", "SN002"],
      "location": "WH-A-01-05"
    }
  ]
}
```

#### Quality Inspection

```http
POST /api/procurement/goods-receipts/{grn_id}/inspect
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "inspector_id": "550e8400-e29b-41d4-a716-446655440011",
  "inspection_date": "2024-12-01",
  "overall_result": "accepted",
  "items": [
    {
      "grn_item_id": "550e8400-e29b-41d4-a716-446655440012",
      "result": "accepted",
      "notes": "All specifications met",
      "attachments": ["inspection_report.pdf"]
    }
  ]
}
```

### Vendor Invoice Processing

#### Submit Vendor Invoice

```http
POST /api/procurement/invoices
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "po_id": "550e8400-e29b-41d4-a716-446655440007",
  "vendor_id": "550e8400-e29b-41d4-a716-446655440005",
  "invoice_number": "INV-2024-001",
  "invoice_date": "2024-12-02",
  "due_date": "2025-01-01",
  "currency": "USD",
  "notes": "Final invoice for camera equipment",
  "items": [
    {
      "po_item_id": "550e8400-e29b-41d4-a716-446655440008",
      "description": "High-resolution camera",
      "quantity": 2,
      "unit_price": 2400.00,
      "tax_rate": 8.25
    }
  ],
  "attachments": ["invoice_2024_001.pdf", "delivery_note.pdf"]
}
```

#### Get 3-Way Match Status

```http
GET /api/procurement/invoices/{invoice_id}/match-status
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

**Response:**
```json
{
  "invoice_id": "550e8400-e29b-41d4-a716-446655440013",
  "match_status": "matched",
  "matching_details": {
    "po_amount": 5800.00,
    "receipt_amount": 5800.00,
    "invoice_amount": 5800.00,
    "price_variance_percent": 0.0,
    "quantity_variance_percent": 0.0
  },
  "tolerance_check": {
    "passed": true,
    "auto_approved": true
  },
  "matched_at": "2024-12-02T14:30:00Z"
}
```

#### Approve Payment

```http
POST /api/procurement/invoices/{invoice_id}/approve-payment
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "approved": true,
  "payment_terms": "net_30",
  "payment_method": "ach",
  "scheduled_date": "2024-12-15",
  "comments": "Auto-approved within tolerance limits"
}
```

## Advanced Procurement APIs

### RFQ Management

#### Create RFQ

```http
POST /api/procurement/rfqs
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "requisition_id": "550e8400-e29b-41d4-a716-446655440003",
  "title": "Professional Photography Equipment",
  "description": "RFQ for high-resolution cameras and lighting equipment for marketing department",
  "submission_deadline": "2024-11-25T17:00:00Z",
  "evaluation_criteria": {
    "price_weight": 40,
    "delivery_weight": 30,
    "quality_weight": 20,
    "support_weight": 10
  },
  "vendors": [
    "550e8400-e29b-41d4-a716-446655440005",
    "550e8400-e29b-41d4-a716-446655440014"
  ],
  "items": [
    {
      "description": "Digital SLR Camera",
      "quantity": 2,
      "specifications": "Minimum 24MP, 4K video capability"
    }
  ]
}
```

#### Submit Vendor Quote

```http
POST /api/procurement/rfqs/{rfq_id}/quotes
Authorization: Bearer {vendor_token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "vendor_id": "550e8400-e29b-41d4-a716-446655440005",
  "valid_until": "2024-12-31",
  "payment_terms": "net_30",
  "delivery_days": 14,
  "warranty_period": 24,
  "items": [
    {
      "rfq_item_id": "550e8400-e29b-41d4-a716-446655440015",
      "quantity": 2,
      "unit_price": 2400.00,
      "alternative_models": ["Canon EOS R5", "Nikon Z6 II"],
      "notes": "Includes professional carrying cases"
    }
  ]
}
```

#### Evaluate Quotes

```http
GET /api/procurement/rfqs/{rfq_id}/evaluation
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

**Response:**
```json
{
  "rfq_id": "550e8400-e29b-41d4-a716-446655440016",
  "quotes": [
    {
      "vendor_id": "550e8400-e29b-41d4-a716-446655440005",
      "vendor_name": "Professional Imaging Ltd",
      "total_price": 4800.00,
      "delivery_days": 14,
      "scores": {
        "price_score": 85,
        "delivery_score": 90,
        "quality_score": 88,
        "support_score": 82,
        "total_score": 86
      }
    }
  ],
  "recommendation": {
    "vendor_id": "550e8400-e29b-41d4-a716-446655440005",
    "reason": "Best overall score with competitive pricing",
    "savings_potential": 1200.00
  }
}
```

### Contract Management

#### Create Procurement Contract

```http
POST /api/procurement/contracts
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "vendor_id": "550e8400-e29b-41d4-a716-446655440005",
  "contract_type": "framework",
  "title": "Professional Imaging Equipment Supply Agreement",
  "description": "Framework agreement for photography equipment and services",
  "start_date": "2024-01-01",
  "end_date": "2025-12-31",
  "value": 50000.00,
  "currency": "USD",
  "payment_terms": "net_30",
  "items": [
    {
      "category": "cameras",
      "description": "Digital cameras and accessories",
      "min_order_quantity": 1,
      "max_order_quantity": 10,
      "unit_price": 2000.00,
      "discount_percent": 5.0
    }
  ]
}
```

#### Amend Contract

```http
POST /api/procurement/contracts/{contract_id}/amend
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "reason": "Price adjustment due to market conditions",
  "changes": {
    "value": 55000.00,
    "items": [
      {
        "id": "550e8400-e29b-41d4-a716-446655440017",
        "unit_price": 2100.00,
        "discount_percent": 7.5
      }
    ]
  },
  "effective_date": "2024-07-01"
}
```

### Blanket Purchase Orders

#### Create Blanket PO

```http
POST /api/procurement/blanket-pos
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "contract_id": "550e8400-e29b-41d4-a716-446655440018",
  "vendor_id": "550e8400-e29b-41d4-a716-446655440005",
  "title": "Q4 Marketing Equipment Blanket Order",
  "start_date": "2024-10-01",
  "end_date": "2024-12-31",
  "total_value": 25000.00,
  "currency": "USD",
  "items": [
    {
      "contract_item_id": "550e8400-e29b-41d4-a716-446655440017",
      "description": "Digital cameras",
      "quantity": 10,
      "unit_price": 2100.00
    }
  ]
}
```

#### Create Release Order

```http
POST /api/procurement/blanket-pos/{bpo_id}/releases
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "required_date": "2024-11-15",
  "delivery_address": "Marketing Department, Building A",
  "items": [
    {
      "bpo_item_id": "550e8400-e29b-41d4-a716-446655440019",
      "quantity": 2,
      "notes": "Urgent requirement for product launch"
    }
  ]
}
```

## Vendor Management APIs

### Vendor CRUD Operations

#### Create Vendor

```http
POST /api/procurement/vendors
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "name": "Professional Imaging Ltd",
  "email": "procurement@imaging.com",
  "phone": "+1-555-0123",
  "tax_id": "12-3456789",
  "payment_terms": "net_30",
  "address": {
    "street": "123 Photography Ave",
    "city": "Los Angeles",
    "state": "CA",
    "zip": "90210",
    "country": "USA"
  },
  "categories": ["photography", "electronics"],
  "certifications": ["ISO9001", "WBE"],
  "contacts": [
    {
      "name": "Sarah Johnson",
      "title": "Procurement Manager",
      "email": "sarah@imaging.com",
      "phone": "+1-555-0124"
    }
  ]
}
```

#### Update Vendor Performance

```http
PUT /api/procurement/vendors/{vendor_id}/performance
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "rating": 4.5,
  "metrics": {
    "on_time_delivery_rate": 95.2,
    "quality_acceptance_rate": 98.1,
    "average_lead_time_days": 12.5,
    "price_competitiveness_score": 4.2
  },
  "assessment_period": "2024-01-01 to 2024-10-31"
}
```

### Vendor Portal APIs

#### Get Vendor Dashboard

```http
GET /api/vendor/dashboard
Authorization: Bearer {vendor_token}
X-Tenant-ID: {tenant_id}
```

**Response:**
```json
{
  "active_pos": 5,
  "pending_invoices": 2,
  "overdue_payments": 0,
  "recent_activity": [
    {
      "type": "po_received",
      "po_number": "PO-2024-001",
      "date": "2024-11-15T10:00:00Z"
    }
  ],
  "performance_score": 4.5,
  "total_revenue": 125000.00
}
```

#### Submit Invoice (Vendor)

```http
POST /api/vendor/invoices
Authorization: Bearer {vendor_token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "po_id": "550e8400-e29b-41d4-a716-446655440007",
  "invoice_number": "INV-2024-001",
  "invoice_date": "2024-12-02",
  "due_date": "2025-01-01",
  "amount": 5800.00,
  "tax_amount": 478.50,
  "currency": "USD",
  "items": [
    {
      "po_item_id": "550e8400-e29b-41d4-a716-446655440008",
      "quantity": 2,
      "unit_price": 2400.00
    }
  ],
  "attachments": ["invoice.pdf"]
}
```

## Analytics & Reporting APIs

### Procurement Analytics

#### Spend Analysis

```http
GET /api/procurement/analytics/spend?start_date=2024-01-01&end_date=2024-10-31&group_by=month&currency=USD
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

**Response:**
```json
{
  "period": "2024-01-01 to 2024-10-31",
  "total_spend": 1250000.00,
  "currency": "USD",
  "spend_by_period": [
    {"period": "2024-01", "amount": 95000.00},
    {"period": "2024-02", "amount": 110000.00}
  ],
  "spend_by_category": [
    {"category": "IT Equipment", "amount": 450000.00},
    {"category": "Office Supplies", "amount": 280000.00}
  ],
  "spend_by_vendor": [
    {"vendor": "Dell Technologies", "amount": 320000.00},
    {"vendor": "Office Depot", "amount": 180000.00}
  ],
  "budget_comparison": {
    "budgeted": 1200000.00,
    "actual": 1250000.00,
    "variance_percent": 4.17
  }
}
```

#### Process Efficiency Metrics

```http
GET /api/procurement/analytics/efficiency?period=quarter&department_id={dept_id}
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

**Response:**
```json
{
  "period": "2024-Q3",
  "department": "Marketing",
  "metrics": {
    "average_requisition_approval_time_days": 2.3,
    "average_po_cycle_time_days": 5.1,
    "average_invoice_processing_time_days": 3.2,
    "on_time_delivery_rate": 94.5,
    "invoice_accuracy_rate": 98.2,
    "first_pass_yield": 87.3
  },
  "trends": {
    "approval_time_trend": -0.5,
    "cycle_time_trend": -1.2,
    "delivery_rate_trend": 2.1
  }
}
```

### Vendor Performance Analytics

```http
GET /api/procurement/analytics/vendors?metric=on_time_delivery&limit=10&period=6months
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

**Response:**
```json
{
  "period": "2024-05-01 to 2024-10-31",
  "metric": "on_time_delivery",
  "vendors": [
    {
      "vendor_id": "550e8400-e29b-41d4-a716-446655440005",
      "vendor_name": "Professional Imaging Ltd",
      "score": 95.2,
      "rank": 1,
      "total_orders": 45,
      "on_time_orders": 43
    }
  ],
  "benchmarks": {
    "industry_average": 88.5,
    "top_performer": 97.8,
    "bottom_performer": 72.3
  }
}
```

## Configuration APIs

### Approval Matrix Management

#### Get Approval Rules

```http
GET /api/procurement/config/approval-matrix
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

**Response:**
```json
{
  "rules": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440020",
      "name": "Standard Approval Matrix",
      "conditions": [
        {
          "field": "total_amount",
          "operator": "<=",
          "value": 5000,
          "approvers": ["department_manager"]
        },
        {
          "field": "total_amount",
          "operator": ">",
          "value": 5000,
          "approvers": ["department_manager", "division_director"]
        }
      ],
      "active": true
    }
  ]
}
```

#### Update Approval Rules

```http
PUT /api/procurement/config/approval-matrix/{rule_id}
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "conditions": [
    {
      "field": "total_amount",
      "operator": "<=",
      "value": 7500,
      "approvers": ["department_manager"]
    }
  ]
}
```

### System Settings

#### Get Procurement Settings

```http
GET /api/procurement/config/settings
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

**Response:**
```json
{
  "domain": {
    "currency": "USD",
    "timezone": "America/New_York",
    "locale": "en_US"
  },
  "approvals": {
    "auto_approve_limit": 1000,
    "escalation_days": 7
  },
  "matching": {
    "price_tolerance_percent": 5.0,
    "quantity_tolerance_percent": 2.0,
    "auto_approve_within_tolerance": true
  },
  "integrations": {
    "accounting_enabled": true,
    "inventory_enabled": true,
    "workflow_enabled": true
  }
}
```

## Webhook Integration

### Configure Webhooks

```http
POST /api/procurement/webhooks
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json

{
  "url": "https://erp-integration.company.com/webhooks/procurement",
  "events": [
    "PurchaseRequisitionApproved",
    "PurchaseOrderCreated",
    "GoodsReceiptProcessed",
    "VendorInvoiceMatched"
  ],
  "secret": "webhook_secret_key",
  "active": true
}
```

### Webhook Payload Examples

**Purchase Order Created:**
```json
{
  "event": "PurchaseOrderCreated",
  "timestamp": "2024-11-15T10:30:00Z",
  "tenant_id": "550e8400-e29b-41d4-a716-446655440000",
  "data": {
    "po_id": "550e8400-e29b-41d4-a716-446655440007",
    "po_number": "PO-2024-001",
    "vendor_id": "550e8400-e29b-41d4-a716-446655440005",
    "total_amount": 5800.00,
    "currency": "USD",
    "items": [
      {
        "description": "High-resolution camera",
        "quantity": 2,
        "unit_price": 2400.00
      }
    ]
  }
}
```

**Invoice Matched:**
```json
{
  "event": "VendorInvoiceMatched",
  "timestamp": "2024-12-02T14:30:00Z",
  "tenant_id": "550e8400-e29b-41d4-a716-446655440000",
  "data": {
    "invoice_id": "550e8400-e29b-41d4-a716-446655440013",
    "po_id": "550e8400-e29b-41d4-a716-446655440007",
    "match_status": "matched",
    "variance_percent": 0.0,
    "auto_approved": true
  }
}
```

## Error Handling

### Standard Error Response

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "items.0.quantity": ["The quantity must be at least 1."],
      "vendor_id": ["The selected vendor is not active."]
    },
    "trace_id": "550e8400-e29b-41d4-a716-446655440021"
  }
}
```

### Common Error Codes

- `VALIDATION_ERROR`: Input validation failed
- `AUTHORIZATION_ERROR`: Insufficient permissions
- `BUSINESS_RULE_VIOLATION`: Domain rule violated
- `NOT_FOUND`: Resource not found
- `TENANT_ISOLATION_ERROR`: Cross-tenant access attempted
- `INTEGRATION_ERROR`: External system communication failed

## Rate Limiting

API endpoints are rate limited based on user type:

- **Internal Users**: 1000 requests/hour
- **Vendor Portal Users**: 500 requests/hour
- **Analytics Endpoints**: 100 requests/hour

Rate limit headers are included:

```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1637088000
X-RateLimit-Retry-After: 3600
```

## SDK Examples

### PHP Integration

```php
use Nexus\Procurement\Facades\Procurement;

// Create requisition programmatically
$requisition = Procurement::requisitions()->create([
    'department_id' => $department->id,
    'justification' => 'Project requirements',
    'items' => $items
]);

// Convert to purchase order
$po = Procurement::purchaseOrders()->createFromRequisition(
    $requisition,
    $selectedVendor
);

// Process goods receipt
$receipt = Procurement::goodsReceipts()->process([
    'po_id' => $po->id,
    'items' => $receivedItems
]);
```

### JavaScript/Node.js Integration

```javascript
const procurement = require('nexus-procurement-client');

const client = new procurement.Client({
  baseUrl: 'https://api.yourapp.com',
  tenantId: '550e8400-e29b-41d4-a716-446655440000',
  apiKey: 'your-api-key'
});

// Create purchase order
const po = await client.purchaseOrders.create({
  vendor_id: '550e8400-e29b-41d4-a716-446655440005',
  items: purchaseItems
});

// Monitor status
const status = await client.purchaseOrders.getStatus(po.id);
```

This API integration guide provides comprehensive technical details for implementing procurement workflows in external systems.