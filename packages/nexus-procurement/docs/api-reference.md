# Nexus Procurement API Reference

## Authentication

All API endpoints require authentication. Use Bearer token authentication:

```
Authorization: Bearer {your-token}
```

### Obtaining Tokens

#### For Internal Users
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@company.com",
  "password": "password"
}
```

#### For Vendor Portal Users
```http
POST /api/vendor/auth/login
Content-Type: application/json

{
  "email": "vendor@company.com",
  "password": "password"
}
```

## Core Procurement APIs

### Purchase Requisitions

#### Create Requisition
```http
POST /api/procurement/requisitions
Authorization: Bearer {token}
Content-Type: application/json

{
  "department_id": "uuid",
  "justification": "Need for Q4 project",
  "required_date": "2024-12-31",
  "items": [
    {
      "description": "Office chairs",
      "quantity": 10,
      "unit_price": 150.00,
      "gl_account": "6000-1000",
      "category": "furniture"
    }
  ]
}
```

**Response:**
```json
{
  "id": "req-2024-001",
  "status": "draft",
  "total_amount": 1500.00,
  "created_at": "2024-11-15T10:00:00Z"
}
```

#### List Requisitions
```http
GET /api/procurement/requisitions?page=1&per_page=20&status=pending
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": [
    {
      "id": "req-2024-001",
      "department": "IT",
      "requester": "John Doe",
      "status": "pending_approval",
      "total_amount": 1500.00,
      "created_at": "2024-11-15T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 45
  }
}
```

#### Approve/Reject Requisition
```http
POST /api/procurement/requisitions/{id}/approve
Authorization: Bearer {token}
Content-Type: application/json

{
  "action": "approve",
  "comments": "Approved for Q4 budget"
}
```

### Purchase Orders

#### Create Purchase Order
```http
POST /api/procurement/purchase-orders
Authorization: Bearer {token}
Content-Type: application/json

{
  "vendor_id": "uuid",
  "requisition_id": "req-2024-001",
  "payment_terms": "net_30",
  "delivery_date": "2024-12-15",
  "items": [
    {
      "item_id": "uuid",
      "quantity": 10,
      "unit_price": 145.00,
      "tax_rate": 8.25
    }
  ]
}
```

#### Get Purchase Order Details
```http
GET /api/procurement/purchase-orders/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": "PO-2024-001",
  "vendor": {
    "id": "uuid",
    "name": "Office Supplies Inc",
    "rating": 4.5
  },
  "status": "approved",
  "total_amount": 1590.25,
  "items": [...],
  "approvals": [...],
  "created_at": "2024-11-15T11:00:00Z"
}
```

#### Update PO Status
```http
PATCH /api/procurement/purchase-orders/{id}/status
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "sent_to_vendor",
  "comments": "PO sent via email"
}
```

### RFQ (Request for Quotation)

#### Create RFQ
```http
POST /api/procurement/rfqs
Authorization: Bearer {token}
Content-Type: application/json

{
  "requisition_id": "req-2024-001",
  "title": "Office Furniture RFQ",
  "description": "RFQ for office chairs and desks",
  "deadline": "2024-11-25T17:00:00Z",
  "vendors": ["uuid1", "uuid2", "uuid3"],
  "items": [...]
}
```

#### Submit Quote (Vendor)
```http
POST /api/procurement/rfqs/{id}/quotes
Authorization: Bearer {vendor-token}
Content-Type: application/json

{
  "vendor_id": "uuid",
  "valid_until": "2024-12-31",
  "items": [
    {
      "rfq_item_id": "uuid",
      "quantity": 10,
      "unit_price": 140.00,
      "delivery_days": 14,
      "notes": "Bulk discount applied"
    }
  ]
}
```

#### Evaluate Quotes
```http
GET /api/procurement/rfqs/{id}/evaluation
Authorization: Bearer {token}
```

**Response:**
```json
{
  "rfq_id": "rfq-2024-001",
  "quotes": [
    {
      "vendor": "Office Supplies Inc",
      "total_price": 1400.00,
      "avg_delivery_days": 14,
      "vendor_rating": 4.5,
      "score": 85
    }
  ],
  "recommendation": {
    "vendor_id": "uuid",
    "reason": "Best price and delivery"
  }
}
```

### Goods Receipt

#### Create Goods Receipt
```http
POST /api/procurement/goods-receipts
Authorization: Bearer {token}
Content-Type: application/json

{
  "purchase_order_id": "PO-2024-001",
  "received_date": "2024-11-20",
  "warehouse_id": "WH001",
  "items": [
    {
      "po_item_id": "uuid",
      "quantity_received": 10,
      "quantity_accepted": 10,
      "quantity_rejected": 0,
      "condition": "good",
      "notes": "All items received in good condition"
    }
  ]
}
```

#### Get Receipt Details
```http
GET /api/procurement/goods-receipts/{id}
Authorization: Bearer {token}
```

### Invoice Processing

#### Submit Invoice (Vendor)
```http
POST /api/procurement/invoices
Authorization: Bearer {vendor-token}
Content-Type: application/json

{
  "purchase_order_id": "PO-2024-001",
  "invoice_number": "INV-2024-001",
  "invoice_date": "2024-11-20",
  "due_date": "2024-12-20",
  "amount": 1590.25,
  "tax_amount": 118.25,
  "items": [...],
  "attachments": ["invoice.pdf"]
}
```

#### 3-Way Match Status
```http
GET /api/procurement/invoices/{id}/match-status
Authorization: Bearer {token}
```

**Response:**
```json
{
  "invoice_id": "inv-2024-001",
  "match_status": "matched",
  "po_amount": 1590.25,
  "receipt_amount": 1590.25,
  "invoice_amount": 1590.25,
  "variances": [],
  "tolerance_check": "passed"
}
```

#### Approve Payment
```http
POST /api/procurement/invoices/{id}/approve-payment
Authorization: Bearer {token}
Content-Type: application/json

{
  "approved": true,
  "payment_date": "2024-12-05",
  "payment_method": "check",
  "comments": "Payment approved within tolerance"
}
```

## Vendor Management APIs

### Vendor CRUD

#### Create Vendor
```http
POST /api/procurement/vendors
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "ABC Supplies Ltd",
  "email": "contact@abc.com",
  "phone": "+1-555-0123",
  "address": {
    "street": "123 Main St",
    "city": "Anytown",
    "state": "CA",
    "zip": "12345",
    "country": "USA"
  },
  "tax_id": "12-3456789",
  "payment_terms": "net_30",
  "categories": ["office_supplies", "furniture"]
}
```

#### Update Vendor
```http
PUT /api/procurement/vendors/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "rating": 4.5,
  "status": "active",
  "payment_terms": "net_15"
}
```

#### Get Vendor Details
```http
GET /api/procurement/vendors/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": "uuid",
  "name": "ABC Supplies Ltd",
  "rating": 4.5,
  "performance": {
    "on_time_delivery": 95.2,
    "quality_acceptance": 98.1,
    "average_rating": 4.5
  },
  "total_spend": 125000.00,
  "active_pos": 12,
  "created_at": "2024-01-15T00:00:00Z"
}
```

### Vendor Performance

#### Get Performance Metrics
```http
GET /api/procurement/vendors/{id}/performance?period=6months
Authorization: Bearer {token}
```

**Response:**
```json
{
  "vendor_id": "uuid",
  "period": "2024-05-01 to 2024-11-01",
  "metrics": {
    "total_orders": 45,
    "on_time_delivery": 95.2,
    "quality_acceptance": 98.1,
    "average_lead_time": 12.5,
    "price_competitiveness": 4.2
  },
  "trends": {
    "delivery_improvement": 2.1,
    "quality_trend": "stable"
  }
}
```

## Analytics APIs

### Procurement Analytics

#### Spend Analysis
```http
GET /api/procurement/analytics/spend?start_date=2024-01-01&end_date=2024-11-30&group_by=month
Authorization: Bearer {token}
```

**Response:**
```json
{
  "period": "2024-01-01 to 2024-11-30",
  "total_spend": 1250000.00,
  "spend_by_month": [
    {"month": "2024-01", "amount": 95000.00},
    {"month": "2024-02", "amount": 110000.00}
  ],
  "spend_by_category": [
    {"category": "IT Equipment", "amount": 450000.00},
    {"category": "Office Supplies", "amount": 280000.00}
  ],
  "spend_by_vendor": [...]
}
```

#### Approval Analytics
```http
GET /api/procurement/analytics/approvals?period=quarter
Authorization: Bearer {token}
```

**Response:**
```json
{
  "total_requisitions": 245,
  "approved_count": 220,
  "rejected_count": 25,
  "average_approval_time": "2.5 days",
  "approval_rate": 89.8,
  "bottlenecks": [
    {"step": "Department Manager", "avg_time": "1.2 days"},
    {"step": "Procurement Review", "avg_time": "3.1 days"}
  ]
}
```

#### Vendor Performance Dashboard
```http
GET /api/procurement/analytics/vendors?metric=on_time_delivery&limit=10
Authorization: Bearer {token}
```

## Vendor Portal APIs

### Dashboard
```http
GET /api/vendor/dashboard
Authorization: Bearer {vendor-token}
```

**Response:**
```json
{
  "active_pos": 12,
  "pending_invoices": 3,
  "overdue_payments": 1,
  "recent_activity": [...],
  "performance_score": 4.5
}
```

### Purchase Orders (Vendor View)
```http
GET /api/vendor/purchase-orders?status=active
Authorization: Bearer {vendor-token}
```

**Response:**
```json
{
  "data": [
    {
      "id": "PO-2024-001",
      "buyer": "ABC Corporation",
      "total_amount": 1590.25,
      "status": "approved",
      "delivery_date": "2024-12-15",
      "items": [...]
    }
  ]
}
```

### Invoice Management
```http
GET /api/vendor/invoices
Authorization: Bearer {vendor-token}
```

**Response:**
```json
{
  "data": [
    {
      "id": "inv-2024-001",
      "po_number": "PO-2024-001",
      "amount": 1590.25,
      "status": "paid",
      "due_date": "2024-12-20",
      "paid_date": "2024-12-05"
    }
  ]
}
```

## Configuration APIs

### Approval Matrix
```http
GET /api/procurement/config/approval-matrix
Authorization: Bearer {token}
```

**Response:**
```json
{
  "rules": [
    {
      "role": "manager",
      "max_amount": 5000,
      "conditions": {
        "department_budget_available": true
      }
    },
    {
      "role": "director",
      "max_amount": 50000,
      "conditions": {
        "department_budget_available": true,
        "capital_expenditure": false
      }
    }
  ]
}
```

### System Settings
```http
GET /api/procurement/config/settings
Authorization: Bearer {token}
```

**Response:**
```json
{
  "currency": "USD",
  "default_payment_terms": "net_30",
  "auto_approve_limit": 1000,
  "rfq_deadline_days": 7,
  "invoice_tolerance_percent": 5,
  "features": {
    "rfq_enabled": true,
    "vendor_portal_enabled": true,
    "analytics_enabled": true
  }
}
```

## Webhook Endpoints

### Procurement Events

The system supports webhooks for real-time notifications:

#### Configure Webhook
```http
POST /api/procurement/webhooks
Authorization: Bearer {token}
Content-Type: application/json

{
  "url": "https://your-app.com/webhooks/procurement",
  "events": [
    "requisition.approved",
    "po.created",
    "invoice.matched",
    "payment.processed"
  ],
  "secret": "your-webhook-secret"
}
```

#### Webhook Payload Example
```json
{
  "event": "po.created",
  "timestamp": "2024-11-15T10:30:00Z",
  "data": {
    "po_id": "PO-2024-001",
    "vendor_id": "uuid",
    "total_amount": 1590.25,
    "items": [...]
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
      "items.0.quantity": ["The quantity must be at least 1."]
    }
  }
}
```

### Common Error Codes
- `VALIDATION_ERROR`: Input validation failed
- `AUTHORIZATION_ERROR`: Insufficient permissions
- `NOT_FOUND`: Resource not found
- `BUSINESS_RULE_VIOLATION`: Business rule violated
- `INTEGRATION_ERROR`: External system error

## Rate Limiting

API endpoints are rate limited:
- **Authenticated requests**: 1000 per hour
- **Vendor portal**: 500 per hour
- **Analytics endpoints**: 100 per hour

Rate limit headers are included in responses:
```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1637088000
```

## Versioning

API versioning uses URL prefixes:
- Current version: `/api/v1/procurement/...`
- Future versions: `/api/v2/procurement/...`

## SDKs and Libraries

### PHP SDK
```php
use Nexus\Procurement\Client;

$client = new Client('your-api-token');
$requisition = $client->requisitions()->create([
    'department_id' => 'uuid',
    'justification' => 'Project needs',
    'items' => [...]
]);
```

### JavaScript SDK
```javascript
import { ProcurementAPI } from 'nexus-procurement-js';

const api = new ProcurementAPI('your-api-token');
const requisitions = await api.requisitions.list({ status: 'pending' });
```

---

*This API reference covers all major endpoints. For complete OpenAPI specification, see `docs/openapi.yaml`. For SDK documentation, visit the developer portal.*