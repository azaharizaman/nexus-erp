# nexus-procurement Package Requirements

**Version:** 1.0.0  
**Last Updated:** November 15, 2025  
**Status:** Initial Requirements - Cohesive Bounded Context Model

---

## Executive Summary

**nexus-procurement** is a **cohesive procurement management system** for PHP/Laravel that manages the complete procure-to-pay lifecycle—from requisition through purchase orders, vendor management, goods receipt, 3-way matching, to vendor payment authorization.

### The Domain Complexity We Embrace

Unlike atomic core packages (tenancy, settings, workflow), procurement is a **vertical business domain** where **atomicity must yield to cohesion**:

1. **Statutory Coupling:** Tax regulations, import duties, and compliance requirements are jurisdiction-specific and change annually. These cannot be externalized to core without losing domain coherence.
2. **Workflow Specificity:** Procurement approval flows (requisition → RFQ → tender evaluation → PO approval) are domain-specific state machines that belong within the bounded context.
3. **Data Ownership:** Purchase requisitions, RFQs, purchase orders, GRNs (Goods Receipt Notes), and vendor invoices form a cohesive aggregate where splitting across packages would create integration hell.

### Core Philosophy

1. **Bounded Context Coherence** - All procurement entities and rules live together in one package
2. **Domain-Driven Design** - Rich domain models with encapsulated business logic
3. **Progressive Complexity** - Start simple (basic PO), grow to advanced (3-way matching, vendor portals)
4. **Regulatory Awareness** - Built-in support for tax codes, import duties, compliance tracking
5. **Extensible State Machines** - Leverage core workflow engine while maintaining procurement-specific states

### Why This Approach Works

**For Small Businesses (60%):**
- Simple requisition → PO → receipt flow
- Basic vendor management
- Essential 3-way matching (PO-GRN-Invoice)
- No complex approval matrices

**For Mid-Market (30%):**
- Multi-level approval workflows
- RFQ/tender management
- Advanced 3-way matching with tolerance rules
- Vendor performance tracking

**For Enterprise (10%):**
- Complex approval matrices based on amount/category/GL account
- Full tender evaluation with weighted scoring
- Contract management and blanket POs
- Vendor portals and EDI integration
- Advanced analytics and spend visibility

---

## Architectural Position in Nexus ERP

### Relationship to Core Packages

| Core Package | Relationship | Usage Pattern |
|-------------|--------------|---------------|
| **nexus-tenancy** | Depends On | All procurement data is tenant-scoped via `BelongsToTenant` trait |
| **nexus-workflow** | Leverages | Uses workflow engine for approval processes but defines procurement-specific states |
| **nexus-sequencing** | Depends On | Uses sequence generation for PR numbers, PO numbers, GRN numbers |
| **nexus-settings** | Depends On | Retrieves procurement settings (approval limits, default terms, tax defaults) |
| **nexus-audit-log** | Depends On | Comprehensive audit trail for all procurement actions |
| **nexus-backoffice** | Depends On | Links to departments for budget tracking and cost center allocation |
| **nexus-accounting** | Consumes From | Posts accruals, liabilities, and payment authorizations to GL |
| **nexus-inventory** | Integrates With | Updates stock on goods receipt, reserves stock for internal requisitions |

### Why This Is NOT an Atomic Package

**Procurement violates the independence criterion:**
- Cannot be meaningfully subdivided (splitting RFQ from PO would break coherence)
- Statutory rules (tax, duties) are procurement-specific and change frequently
- Workflow states are domain-specific (not generic state machine patterns)
- Testing requires procurement context (cannot test PO approval without requisition context)

**This is a COHESIVE VERTICAL** where domain logic density justifies consolidation.

---

## Personas & User Stories

### Personas

| ID | Persona | Role | Primary Goal |
|-----|---------|------|--------------|
| **P1** | Requester | Employee needing goods/services | "Submit a purchase requisition for office supplies and track approval status" |
| **P2** | Department Manager | Budget owner | "Approve requisitions within my budget authority and monitor departmental spend" |
| **P3** | Procurement Officer | Buyer/sourcing specialist | "Convert approved requisitions to RFQs, evaluate quotes, issue purchase orders efficiently" |
| **P4** | Warehouse Staff | Receiving clerk | "Record goods receipt accurately and match against purchase orders" |
| **P5** | Accounts Payable Clerk | Finance team | "Match vendor invoices against PO and GRN, authorize payment only when 3-way match succeeds" |
| **P6** | CFO/Finance Director | Executive oversight | "Enforce approval limits, monitor procurement spend, ensure compliance with purchasing policies" |
| **P7** | Vendor | External supplier | "View POs issued to me, submit quotes for RFQs, track payment status" |

### User Stories

#### Level 1: Basic Procurement (Simple PO Flow)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-001** | P1 | As a requester, I want to create a purchase requisition for items I need, specifying quantity, description, and estimated cost | **High** |
| **US-002** | P2 | As a department manager, I want to approve or reject requisitions from my team members with comments | **High** |
| **US-003** | P3 | As a procurement officer, I want to convert an approved requisition into a purchase order, selecting a vendor and negotiating final price | **High** |
| **US-004** | P3 | As a procurement officer, I want to create purchase orders directly (without requisition) for regular/recurring purchases | **High** |
| **US-005** | P4 | As warehouse staff, I want to record goods receipt against a PO, noting actual quantity received and any discrepancies | **High** |
| **US-006** | P5 | As AP clerk, I want to match a vendor invoice against the PO and GRN (3-way match) before authorizing payment | **High** |
| **US-007** | P1 | As a requester, I want to view the status of my requisitions (pending, approved, converted to PO, delivered) | Medium |

#### Level 2: Advanced Procurement (RFQ & Vendor Management)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-010** | P3 | As a procurement officer, I want to create an RFQ (Request for Quotation) for a requisition and invite multiple vendors to quote | **High** |
| **US-011** | P7 | As a vendor, I want to receive RFQ invitations via email and submit my quote through a vendor portal | **High** |
| **US-012** | P3 | As a procurement officer, I want to compare quotes side-by-side (price, delivery time, payment terms) and select the best vendor | **High** |
| **US-013** | P3 | As a procurement officer, I want to maintain a vendor master with contact details, payment terms, tax IDs, and performance ratings | **High** |
| **US-014** | P3 | As a procurement officer, I want to track vendor performance (on-time delivery, quality, pricing) to inform future sourcing decisions | Medium |
| **US-015** | P3 | As a procurement officer, I want to create blanket POs for recurring purchases with release schedules | Medium |
| **US-016** | P5 | As AP clerk, I want the system to enforce 3-way match tolerance rules (e.g., allow 5% quantity variance, reject if >10%) | **High** |

#### Level 3: Enterprise Procurement (Complex Workflows & Compliance)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-020** | P6 | As CFO, I want to define approval matrices where requisitions >$10K require director approval, >$50K require CFO approval | **High** |
| **US-021** | P3 | As a procurement officer, I want to conduct formal tender evaluations with weighted scoring (price 60%, quality 25%, delivery 15%) | Medium |
| **US-022** | P3 | As a procurement officer, I want to manage procurement contracts with renewal dates, terms, and compliance tracking | Medium |
| **US-023** | P6 | As CFO, I want to enforce separation of duties (requester ≠ approver ≠ receiver) automatically | **High** |
| **US-024** | P3 | As a procurement officer, I want to track import duties, customs clearance, and landed cost for international purchases | Medium |
| **US-025** | P6 | As CFO, I want to monitor procurement spend by category, department, vendor, and time period with drill-down analytics | Medium |
| **US-026** | P7 | As a vendor, I want to view my purchase orders, track payment status, and submit invoices through a vendor portal | Low |

---

## Functional Requirements

### FR-L1: Level 1 - Basic Procurement (Essential MVP)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L1-001** | Create purchase requisition with line items | **High** | • Multi-line item entry (item description, quantity, unit price estimate, GL account)<br>• Auto-save draft functionality<br>• Attach supporting documents (quotes, specifications)<br>• Auto-populate requester details |
| **FR-L1-002** | Requisition approval workflow | **High** | • Route to department manager based on requester's department<br>• Approval/rejection with mandatory comments<br>• Email notifications to requester and approver<br>• Track approval history with timestamps |
| **FR-L1-003** | Convert requisition to purchase order | **High** | • Select vendor from vendor master<br>• Copy requisition line items to PO<br>• Adjust quantities/prices during conversion<br>• Auto-generate PO number via nexus-sequencing<br>• Calculate taxes based on vendor jurisdiction |
| **FR-L1-004** | Direct purchase order creation | **High** | • Create PO without requisition (for regular purchases)<br>• Vendor selection with auto-populate payment terms<br>• Line item entry with GL account allocation<br>• Tax calculation based on tax codes<br>• PO approval workflow (if amount exceeds threshold) |
| **FR-L1-005** | Goods receipt note (GRN) creation | **High** | • Select pending POs for receiving<br>• Record actual quantity received per line item<br>• Note discrepancies (over/under delivery, damaged goods)<br>• Attach delivery note and inspection photos<br>• Partial receipts (receive PO in multiple shipments)<br>• Auto-update inventory levels |
| **FR-L1-006** | 3-way matching (PO-GRN-Invoice) | **High** | • Upload/scan vendor invoice<br>• Auto-match invoice to PO by PO number or vendor reference<br>• Compare invoice amount vs PO amount vs GRN quantity<br>• Flag discrepancies (price variance, quantity variance)<br>• Authorize payment if match succeeds within tolerance<br>• Route to AP manager if discrepancies exceed tolerance |
| **FR-L1-007** | Purchase requisition status tracking | Medium | • Real-time status updates (draft, pending approval, approved, converted, closed)<br>• Notification on status changes<br>• Audit trail of all state transitions |

### FR-L2: Level 2 - Advanced Procurement (Vendor & RFQ Management)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L2-001** | Vendor master management | **High** | • Vendor profile: name, address, contact details, tax ID, bank account<br>• Payment terms configuration (Net 30, 2/10 Net 30, COD)<br>• Tax status and withholding tax rules<br>• Currency preference and exchange rate handling<br>• Vendor category classification (goods vs services)<br>• Active/inactive status |
| **FR-L2-002** | Request for Quotation (RFQ) creation | **High** | • Create RFQ from approved requisition<br>• Define quote submission deadline<br>• Invite multiple vendors (3-5 typical)<br>• Specify evaluation criteria (price, delivery, payment terms)<br>• Attach specification documents |
| **FR-L2-003** | Vendor quote submission | **High** | • Vendors receive RFQ invitation via email with secure link<br>• Vendor portal for quote submission (line-by-line pricing)<br>• Support for alternate offers and comments<br>• Upload supporting documents (certificates, samples)<br>• Track quote submission status (pending, submitted, withdrawn) |
| **FR-L2-004** | RFQ evaluation and comparison | **High** | • Side-by-side quote comparison table<br>• Sort by price, delivery time, total cost<br>• Flag non-compliant quotes (missing items, late submission)<br>• Add evaluation notes per vendor<br>• Select winning vendor and auto-convert to PO |
| **FR-L2-005** | Vendor performance tracking | Medium | • Automatic metrics: on-time delivery rate, quality acceptance rate, price competitiveness<br>• Manual ratings: communication, responsiveness, flexibility<br>• Performance dashboard per vendor<br>• Use metrics in vendor selection recommendations |
| **FR-L2-006** | Blanket purchase orders | Medium | • Create blanket PO with total value limit and validity period<br>• Release mechanism: create release POs against blanket PO<br>• Track utilization (released value vs committed value)<br>• Auto-notify when 80% utilized or near expiry |
| **FR-L2-007** | 3-way match tolerance rules | **High** | • Configurable tolerance settings: price variance (%), quantity variance (%), total value variance (amount)<br>• Auto-approve if within tolerance<br>• Auto-route to supervisor if exceeds tolerance but below escalation threshold<br>• Auto-reject if exceeds escalation threshold (requires CFO approval) |

### FR-L3: Level 3 - Enterprise Procurement (Complex Workflows & Analytics)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L3-001** | Dynamic approval matrix | **High** | • Define approval rules based on amount thresholds, GL accounts, departments<br>• Multi-level approvals (Manager → Director → CFO)<br>• Parallel approvals (Finance AND Operations must both approve)<br>• Approval escalation if not actioned within SLA<br>• Configurable without code changes |
| **FR-L3-002** | Formal tender evaluation | Medium | • Define weighted evaluation criteria (price 60%, quality 20%, delivery 10%, sustainability 10%)<br>• Score each vendor against criteria<br>• Automatic weighted score calculation<br>• Tender evaluation committee (multiple reviewers)<br>• Generate tender evaluation report |
| **FR-L3-003** | Contract management | Medium | • Link POs to procurement contracts<br>• Track contract terms, renewal dates, value limits<br>• Auto-alert on contract expiry (90 days, 30 days)<br>• Contract compliance tracking (did we use the contracted vendor?)<br>• Contract amendment history |
| **FR-L3-004** | Separation of duties enforcement | **High** | • Requester cannot approve their own requisition<br>• PO creator cannot be the GRN receiver<br>• GRN receiver cannot approve the invoice payment<br>• System enforces these rules automatically<br>• Audit flag if violation attempted |
| **FR-L3-005** | Import/customs tracking | Medium | • Capture customs declaration details on PO<br>• Track import duties, freight, insurance (landed cost)<br>• Record customs clearance dates and documents<br>• Allocate landed cost to inventory items |
| **FR-L3-006** | Procurement analytics dashboard | Medium | • Spend by category, vendor, department, time period<br>• Trend analysis (spend growth, vendor concentration)<br>• Compliance metrics (% of POs with requisition, average approval time)<br>• Savings tracking (RFQ savings, contract compliance savings)<br>• Drill-down to transaction details |
| **FR-L3-007** | Vendor self-service portal | Low | • Vendor login with secure authentication<br>• View POs issued to vendor<br>• Submit invoices electronically<br>• Track payment status<br>• Download remittance advice<br>• Update vendor profile details |

---

## Non-Functional Requirements

### Performance Requirements

| ID | Requirement | Target | Notes |
|----|-------------|--------|-------|
| **PR-001** | Requisition creation and save | < 2 seconds | Including draft auto-save |
| **PR-002** | PO generation from requisition | < 3 seconds | Including tax calculation |
| **PR-003** | 3-way match processing | < 5 seconds | For PO with up to 50 line items |
| **PR-004** | Vendor quote comparison loading | < 2 seconds | For RFQ with 5 vendors, 20 items |
| **PR-005** | Procurement analytics dashboard | < 10 seconds | For 12-month data across 1000+ transactions |

### Security Requirements

| ID | Requirement | Scope |
|----|-------------|-------|
| **SR-001** | Tenant data isolation | All procurement data MUST be tenant-scoped (via nexus-tenancy) |
| **SR-002** | Role-based access control | Enforce permissions: create-requisition, approve-requisition, create-po, approve-po, create-grn, approve-payment |
| **SR-003** | Vendor data encryption | Sensitive vendor data (bank account, tax ID) MUST be encrypted at rest |
| **SR-004** | Audit trail completeness | ALL create/update/delete operations MUST be logged via nexus-audit-log |
| **SR-005** | Separation of duties | System MUST enforce SOD rules (requester ≠ approver ≠ receiver) |
| **SR-006** | Document access control | Attachments (quotes, invoices, contracts) MUST be access-controlled by role |

### Reliability Requirements

| ID | Requirement | Notes |
|----|-------------|-------|
| **REL-001** | All financial transactions MUST be ACID-compliant | Wrapped in database transactions |
| **REL-002** | 3-way match MUST prevent payment authorization if discrepancies exceed tolerance | Hard constraint, no bypass |
| **REL-003** | Approval workflows MUST be resumable after system failure | Use nexus-workflow persistence |
| **REL-004** | Concurrency control for PO approval | Prevent duplicate approvals via optimistic locking |

### Compliance Requirements

| ID | Requirement | Jurisdiction |
|----|-------------|--------------|
| **COMP-001** | Tax calculation MUST support GST/VAT/Sales Tax | Multi-jurisdiction support via nexus-tax-management |
| **COMP-002** | Withholding tax on vendor payments | Malaysia: 2-10% WHT based on vendor type |
| **COMP-003** | Import duty calculation | Track duty rates, tariff codes, customs declarations |
| **COMP-004** | Procurement approval limits | Configurable by organization (e.g., $5K → Manager, $50K → Director) |
| **COMP-005** | Contract compliance reporting | Track PO value vs contract value, flag non-compliant purchases |

---

## Domain Model

### Core Entities

```
PurchaseRequisition
├── id (UUID)
├── tenant_id (UUID) - BelongsToTenant
├── requisition_number (string) - via nexus-sequencing
├── requester_id (UUID) - User who submitted
├── department_id (UUID) - from nexus-backoffice
├── status (enum: draft, pending_approval, approved, rejected, converted)
├── justification (text)
├── total_estimate (decimal)
├── approved_by (UUID, nullable)
├── approved_at (datetime, nullable)
└── Line Items (hasMany PurchaseRequisitionItem)

PurchaseRequisitionItem
├── id (UUID)
├── requisition_id (UUID)
├── line_number (int)
├── item_description (string)
├── quantity (decimal)
├── unit_of_measure (string) - from nexus-uom
├── unit_price_estimate (decimal)
├── gl_account_code (string) - from nexus-accounting
└── notes (text, nullable)

Vendor
├── id (UUID)
├── tenant_id (UUID)
├── vendor_code (string) - unique
├── name (string)
├── contact_person (string)
├── email (string)
├── phone (string)
├── address (json)
├── tax_id (encrypted)
├── bank_account (encrypted)
├── payment_terms (string) - "Net 30", "2/10 Net 30", etc.
├── currency_code (string)
├── vendor_category (enum: goods, services, both)
├── status (enum: active, suspended, inactive)
└── performance_metrics (json)

RequestForQuotation (RFQ)
├── id (UUID)
├── tenant_id (UUID)
├── rfq_number (string)
├── requisition_id (UUID)
├── created_by (UUID)
├── quote_deadline (datetime)
├── status (enum: draft, sent, closed, cancelled)
├── evaluation_criteria (json) - weighted scoring
└── Invited Vendors (manyToMany Vendor)
└── Line Items (hasMany RFQItem)

RFQItem
├── id (UUID)
├── rfq_id (UUID)
├── line_number (int)
├── item_description (string)
├── quantity (decimal)
├── unit_of_measure (string)
└── specifications (text)

VendorQuote
├── id (UUID)
├── rfq_id (UUID)
├── vendor_id (UUID)
├── submitted_at (datetime)
├── status (enum: pending, submitted, selected, rejected)
├── total_quoted_price (decimal)
├── delivery_days (int)
├── payment_terms (string)
├── notes (text)
└── Line Items (hasMany VendorQuoteItem)

VendorQuoteItem
├── id (UUID)
├── vendor_quote_id (UUID)
├── rfq_item_id (UUID)
├── unit_price (decimal)
├── alternate_offer (text, nullable)
└── notes (text, nullable)

PurchaseOrder
├── id (UUID)
├── tenant_id (UUID)
├── po_number (string)
├── requisition_id (UUID, nullable) - may be direct PO
├── vendor_id (UUID)
├── created_by (UUID)
├── status (enum: draft, pending_approval, approved, sent, partially_received, fully_received, closed, cancelled)
├── order_date (date)
├── delivery_date (date)
├── payment_terms (string)
├── subtotal (decimal)
├── tax_amount (decimal)
├── total_amount (decimal)
├── currency_code (string)
├── exchange_rate (decimal)
├── approved_by (UUID, nullable)
├── approved_at (datetime, nullable)
└── Line Items (hasMany PurchaseOrderItem)

PurchaseOrderItem
├── id (UUID)
├── po_id (UUID)
├── line_number (int)
├── item_description (string)
├── quantity (decimal)
├── unit_of_measure (string)
├── unit_price (decimal)
├── tax_code (string)
├── gl_account_code (string)
├── received_quantity (decimal) - updated by GRN
└── invoiced_quantity (decimal) - updated by invoice matching

GoodsReceiptNote (GRN)
├── id (UUID)
├── tenant_id (UUID)
├── grn_number (string)
├── po_id (UUID)
├── received_by (UUID)
├── received_at (datetime)
├── delivery_note_number (string)
├── status (enum: draft, completed, cancelled)
└── Line Items (hasMany GoodsReceiptItem)

GoodsReceiptItem
├── id (UUID)
├── grn_id (UUID)
├── po_item_id (UUID)
├── quantity_received (decimal)
├── quantity_accepted (decimal) - may differ if damaged
├── quantity_rejected (decimal)
├── rejection_reason (text, nullable)
└── notes (text, nullable)

VendorInvoice
├── id (UUID)
├── tenant_id (UUID)
├── invoice_number (string) - vendor's invoice number
├── vendor_id (UUID)
├── po_id (UUID)
├── grn_id (UUID, nullable) - for 3-way match
├── invoice_date (date)
├── due_date (date)
├── subtotal (decimal)
├── tax_amount (decimal)
├── total_amount (decimal)
├── currency_code (string)
├── match_status (enum: pending, matched, variance, rejected)
├── payment_status (enum: pending, authorized, paid)
├── payment_authorized_by (UUID, nullable)
├── payment_authorized_at (datetime, nullable)
└── Line Items (hasMany VendorInvoiceItem)

VendorInvoiceItem
├── id (UUID)
├── vendor_invoice_id (UUID)
├── po_item_id (UUID, nullable)
├── line_number (int)
├── description (string)
├── quantity (decimal)
├── unit_price (decimal)
├── tax_amount (decimal)
└── line_total (decimal)

ThreeWayMatchResult
├── id (UUID)
├── po_id (UUID)
├── grn_id (UUID)
├── vendor_invoice_id (UUID)
├── match_date (datetime)
├── match_status (enum: success, price_variance, quantity_variance, total_variance, rejected)
├── price_variance_pct (decimal)
├── quantity_variance_pct (decimal)
├── total_variance_amount (decimal)
├── tolerance_applied (json) - which rules were checked
├── approved_override (boolean) - manual approval despite variance
├── approved_by (UUID, nullable)
└── variance_details (json) - line-by-line comparison
```

### Aggregate Relationships

```
PurchaseRequisition (Aggregate Root)
  └─> PurchaseRequisitionItem (Entity)
  └─> Approvals (via nexus-workflow)

RequestForQuotation (Aggregate Root)
  └─> RFQItem (Entity)
  └─> VendorQuote (Entity)
      └─> VendorQuoteItem (Value Object)

PurchaseOrder (Aggregate Root)
  └─> PurchaseOrderItem (Entity)
  └─> GoodsReceiptNote (Entity)
      └─> GoodsReceiptItem (Value Object)
  └─> VendorInvoice (Entity)
      └─> VendorInvoiceItem (Value Object)
  └─> ThreeWayMatchResult (Value Object)
```

---

## Business Rules

| ID | Rule | Level |
|----|------|-------|
| **BR-001** | A requisition MUST have at least one line item | All levels |
| **BR-002** | Requisition total estimate MUST equal sum of line item estimates | All levels |
| **BR-003** | Approved requisitions cannot be edited (only cancelled) | All levels |
| **BR-004** | A purchase order MUST reference an approved requisition OR be explicitly marked as direct PO | All levels |
| **BR-005** | PO total amount MUST NOT exceed requisition approved amount by more than 10% without re-approval | Level 2 |
| **BR-006** | GRN quantity cannot exceed PO quantity for any line item | All levels |
| **BR-007** | 3-way match tolerance rules are configurable per tenant | Level 2 |
| **BR-008** | Payment authorization requires successful 3-way match OR manual override by authorized user | All levels |
| **BR-009** | Requester cannot approve their own requisition | Level 3 (SOD) |
| **BR-010** | PO creator cannot create GRN for the same PO | Level 3 (SOD) |
| **BR-011** | GRN creator cannot authorize payment for the same PO | Level 3 (SOD) |
| **BR-012** | Blanket PO releases cannot exceed blanket PO total committed value | Level 2 |
| **BR-013** | Vendor quote must be submitted before RFQ deadline to be considered valid | Level 2 |
| **BR-014** | Tax calculation based on vendor jurisdiction and tax codes from nexus-tax-management | All levels |
| **BR-015** | All procurement amounts must be in tenant's base currency OR converted at transaction date exchange rate | All levels |

---

## Workflow State Machines

### Purchase Requisition Workflow

```
States:
  - draft (initial)
  - pending_approval
  - approved
  - rejected
  - converted (to PO)
  - cancelled

Transitions:
  submit: draft → pending_approval
    - Validates: all line items have GL accounts, total > 0
    - Triggers: notification to approver
    
  approve: pending_approval → approved
    - Guard: user has approve-requisition permission
    - Guard: user is not the requester (SOD)
    - Triggers: notification to requester and procurement team
    
  reject: pending_approval → rejected
    - Guard: user has approve-requisition permission
    - Requires: rejection reason comment
    
  convert_to_po: approved → converted
    - Guard: user has create-po permission
    - Action: create PurchaseOrder record linked to requisition
    
  cancel: [draft, pending_approval, approved] → cancelled
    - Guard: user is requester OR has admin permission
    - Requires: cancellation reason
```

### Purchase Order Workflow

```
States:
  - draft (initial)
  - pending_approval (if amount > threshold)
  - approved
  - sent (to vendor)
  - partially_received
  - fully_received
  - closed
  - cancelled

Transitions:
  submit: draft → pending_approval
    - Guard: PO total > approval threshold
    - Validates: vendor selected, all line items valid
    
  approve: pending_approval → approved
    - Guard: user has approve-po permission
    - Guard: approval matrix rules satisfied
    
  send_to_vendor: [draft, approved] → sent
    - Guard: user has send-po permission
    - Action: generate PO PDF, send email to vendor
    
  receive_partial: sent → partially_received
    - Action: create GRN record
    - Update: received_quantity on PO line items
    
  receive_full: [sent, partially_received] → fully_received
    - Guard: all line items fully received
    - Action: create final GRN record
    
  close: fully_received → closed
    - Guard: all invoices matched and authorized
    
  cancel: [draft, pending_approval, approved, sent] → cancelled
    - Guard: user has cancel-po permission
    - Requires: cancellation reason
```

### 3-Way Match Workflow

```
States:
  - pending (initial)
  - matched (success)
  - variance_within_tolerance
  - variance_exceeds_tolerance
  - rejected
  - manually_approved

Transitions:
  auto_match: pending → [matched, variance_within_tolerance, variance_exceeds_tolerance, rejected]
    - Algorithm:
      1. Compare invoice line items to PO line items (price, quantity)
      2. Compare invoice quantities to GRN quantities
      3. Calculate variance percentages
      4. Apply tolerance rules
      5. Auto-approve if within tolerance
      6. Route to supervisor if exceeds tolerance but below escalation
      7. Reject if exceeds escalation threshold
      
  manual_approve: variance_exceeds_tolerance → manually_approved
    - Guard: user has override-3way-match permission (typically CFO)
    - Requires: override justification comment
    - Audit: log manual override with reason
```

---

## Integration Points

### With Core Packages

| Core Package | Integration Type | Usage |
|-------------|------------------|-------|
| **nexus-tenancy** | Direct Dependency | All models use `BelongsToTenant` trait for data isolation |
| **nexus-sequencing** | Service Call | Generate requisition numbers, PO numbers, GRN numbers, invoice numbers |
| **nexus-settings** | Service Call | Retrieve approval thresholds, tolerance rules, default payment terms |
| **nexus-audit-log** | Event Listener | Log all create/update/delete operations on procurement entities |
| **nexus-workflow** | Engine Usage | Leverage workflow engine for approval processes (requisition, PO, invoice) |
| **nexus-backoffice** | Data Reference | Link requisitions to departments for budget tracking |
| **nexus-tax-management** | Service Call | Calculate taxes on PO based on vendor jurisdiction and tax codes |

### With Business Domain Packages

| Package | Integration Type | Data Flow |
|---------|------------------|-----------|
| **nexus-accounting** | Event-Driven | Post accruals on PO approval, liabilities on GRN, payment authorization to AP |
| **nexus-inventory** | Event-Driven | Update stock levels on GRN completion, reserve stock for internal requisitions |
| **nexus-asset-management** | Data Reference | Tag PO line items as capital expenditure vs operating expense |
| **nexus-budget-management** | Validation Check | Check budget availability before approving requisition (if budget control enabled) |

### External Integrations (Optional)

| System | Integration Method | Purpose |
|--------|-------------------|---------|
| **Vendor Portal** | REST API | Allow vendors to view POs, submit quotes, upload invoices |
| **Email Gateway** | SMTP/Queue | Send PO PDFs, RFQ invitations, approval notifications |
| **Document Scanner** | File Upload API | Scan and attach vendor invoices for 3-way matching |
| **ERP Connectors** | EDI/API | Send PO to vendor ERP, receive ASN (Advanced Shipping Notice) |

---

## Testing Requirements

### Unit Tests
- Requisition line item total calculation
- Tax calculation based on vendor jurisdiction
- 3-way match variance calculation and tolerance application
- Approval matrix rule evaluation
- Blanket PO utilization tracking

### Feature Tests
- Complete requisition-to-PO-to-GRN-to-payment flow
- RFQ creation, vendor quote submission, quote comparison
- 3-way match success and failure scenarios
- Separation of duties enforcement (requester cannot approve)
- Multi-level approval workflows

### Integration Tests
- nexus-accounting integration: verify accrual and liability posting
- nexus-inventory integration: verify stock level updates on GRN
- nexus-workflow integration: verify approval state transitions
- nexus-sequencing integration: verify unique number generation

### Performance Tests
- Load test: 1000 concurrent requisition submissions
- Stress test: 3-way match processing for PO with 500 line items
- Analytics query performance: 12-month spend report with 10,000+ transactions

---

## Package Structure

```
packages/nexus-procurement/
├── src/
│   ├── Models/                      # Domain entities
│   │   ├── PurchaseRequisition.php
│   │   ├── PurchaseRequisitionItem.php
│   │   ├── Vendor.php
│   │   ├── RequestForQuotation.php
│   │   ├── VendorQuote.php
│   │   ├── PurchaseOrder.php
│   │   ├── PurchaseOrderItem.php
│   │   ├── GoodsReceiptNote.php
│   │   ├── VendorInvoice.php
│   │   └── ThreeWayMatchResult.php
│   │
│   ├── Repositories/                # Data access layer
│   │   ├── PurchaseRequisitionRepository.php
│   │   ├── VendorRepository.php
│   │   ├── PurchaseOrderRepository.php
│   │   └── GoodsReceiptRepository.php
│   │
│   ├── Services/                    # Business logic services
│   │   ├── RequisitionApprovalService.php
│   │   ├── RFQManagementService.php
│   │   ├── PurchaseOrderService.php
│   │   ├── ThreeWayMatchService.php
│   │   ├── VendorPerformanceService.php
│   │   └── ApprovalMatrixService.php
│   │
│   ├── Contracts/                   # Interfaces for external consumption
│   │   ├── PurchaseRequisitionRepositoryContract.php
│   │   ├── PurchaseOrderServiceContract.php
│   │   ├── VendorRepositoryContract.php
│   │   └── ThreeWayMatchServiceContract.php
│   │
│   ├── Enums/                       # Domain enums
│   │   ├── RequisitionStatus.php
│   │   ├── PurchaseOrderStatus.php
│   │   ├── MatchStatus.php
│   │   └── PaymentStatus.php
│   │
│   ├── Events/                      # Domain events
│   │   ├── RequisitionApproved.php
│   │   ├── PurchaseOrderCreated.php
│   │   ├── GoodsReceived.php
│   │   ├── InvoiceMatched.php
│   │   └── PaymentAuthorized.php
│   │
│   ├── Workflows/                   # Workflow definitions
│   │   ├── RequisitionWorkflow.php
│   │   ├── PurchaseOrderWorkflow.php
│   │   └── ThreeWayMatchWorkflow.php
│   │
│   ├── Rules/                       # Validation rules
│   │   ├── ToleranceRules.php
│   │   ├── ApprovalMatrixRules.php
│   │   └── SeparationOfDutiesRules.php
│   │
│   └── ProcurementServiceProvider.php
│
├── database/
│   └── migrations/
│       ├── 2025_11_15_000001_create_vendors_table.php
│       ├── 2025_11_15_000002_create_purchase_requisitions_table.php
│       ├── 2025_11_15_000003_create_purchase_requisition_items_table.php
│       ├── 2025_11_15_000004_create_rfqs_table.php
│       ├── 2025_11_15_000005_create_vendor_quotes_table.php
│       ├── 2025_11_15_000006_create_purchase_orders_table.php
│       ├── 2025_11_15_000007_create_purchase_order_items_table.php
│       ├── 2025_11_15_000008_create_goods_receipt_notes_table.php
│       ├── 2025_11_15_000009_create_vendor_invoices_table.php
│       └── 2025_11_15_000010_create_three_way_match_results_table.php
│
├── config/
│   └── procurement.php              # Package configuration
│
├── tests/
│   ├── Unit/
│   │   ├── Services/
│   │   │   ├── ThreeWayMatchServiceTest.php
│   │   │   └── ApprovalMatrixServiceTest.php
│   │   └── Rules/
│   │       └── ToleranceRulesTest.php
│   │
│   └── Feature/
│       ├── RequisitionWorkflowTest.php
│       ├── PurchaseOrderCreationTest.php
│       ├── ThreeWayMatchingTest.php
│       └── VendorManagementTest.php
│
├── routes/
│   └── api.php                      # Package routes
│
└── REQUIREMENTS.md                  # This document
```

---

## Configuration

### procurement.php

```php
return [
    // Approval thresholds (can be overridden per tenant via nexus-settings)
    'approval_thresholds' => [
        'requisition' => [
            'level_1' => 5000,   // Manager approval up to $5K
            'level_2' => 50000,  // Director approval up to $50K
            'level_3' => PHP_INT_MAX, // CFO approval above $50K
        ],
        'purchase_order' => [
            'level_1' => 10000,
            'level_2' => 100000,
            'level_3' => PHP_INT_MAX,
        ],
    ],
    
    // 3-way match tolerance rules
    'three_way_match' => [
        'price_variance_tolerance' => 5.0,    // Allow 5% price difference
        'quantity_variance_tolerance' => 2.0, // Allow 2% quantity difference
        'total_variance_amount' => 100.00,    // Allow $100 total difference
        'auto_approve_within_tolerance' => true,
        'escalate_on_exceed' => true,
    ],
    
    // Separation of duties enforcement
    'separation_of_duties' => [
        'enabled' => true,
        'requester_cannot_approve' => true,
        'creator_cannot_receive' => true,
        'receiver_cannot_authorize_payment' => true,
    ],
    
    // Vendor management
    'vendor' => [
        'require_tax_id' => true,
        'require_bank_account' => true,
        'performance_tracking_enabled' => true,
    ],
    
    // Blanket PO settings
    'blanket_po' => [
        'enabled' => true,
        'default_validity_days' => 365,
        'utilization_alert_threshold' => 0.8, // Alert at 80% utilization
    ],
];
```

---

## Success Metrics

| Metric | Target | Measurement Period | Why It Matters |
|--------|--------|-------------------|----------------|
| **Adoption Rate** | > 500 installations | 12 months | Validates procurement solution viability |
| **Requisition Cycle Time** | < 2 days (draft → approved) | Ongoing | Measures efficiency gains |
| **3-Way Match Accuracy** | > 95% auto-match success rate | Ongoing | Validates tolerance rules effectiveness |
| **PO-to-Payment Cycle Time** | < 30 days | Ongoing | Measures procure-to-pay efficiency |
| **Vendor Portal Adoption** | > 60% vendors self-serve | 12 months | Reduces manual vendor communication |
| **Compliance Rate** | > 98% requisitions have PO | Ongoing | Measures policy enforcement |
| **User Satisfaction** | > 4.5/5 rating | Quarterly survey | Overall usability and effectiveness |

---

## Development Phases

### Phase 1: Core Procurement (Weeks 1-8)
- Implement requisition management (create, approve, track)
- Implement vendor master
- Implement basic PO creation (direct and from requisition)
- Implement GRN creation
- Basic 3-way matching (without tolerance rules)
- Unit and feature tests for core flows

### Phase 2: RFQ & Advanced Matching (Weeks 9-14)
- Implement RFQ creation and vendor invitation
- Vendor quote submission (internal form, not portal yet)
- Quote comparison and selection
- Advanced 3-way match with tolerance rules
- Approval matrix configuration
- Feature tests for RFQ and matching scenarios

### Phase 3: Enterprise Features (Weeks 15-20)
- Blanket PO management
- Separation of duties enforcement
- Contract management
- Vendor performance tracking
- Procurement analytics dashboard
- Integration with nexus-accounting and nexus-inventory

### Phase 4: Vendor Portal (Weeks 21-24)
- Vendor authentication and profile management
- PO view and invoice submission
- Payment status tracking
- Security audit and penetration testing

### Phase 5: Optimization & Launch (Weeks 25-28)
- Performance tuning (database indexing, query optimization)
- Comprehensive documentation
- Video tutorials and user guides
- Beta testing with 5-10 organizations
- Production deployment

---

## Dependencies

### Required
- PHP ≥ 8.3
- Laravel ≥ 12.x
- nexus-tenancy (multi-tenant isolation)
- nexus-sequencing (document numbering)
- nexus-settings (configuration management)
- nexus-audit-log (activity tracking)
- nexus-workflow (approval processes)
- nexus-backoffice (organizational structure)

### Optional (for full functionality)
- nexus-accounting (GL posting, AP integration)
- nexus-inventory (stock updates on GRN)
- nexus-tax-management (tax calculation)
- nexus-budget-management (budget checking)

---

## Glossary

- **Purchase Requisition (PR):** Internal request for goods/services requiring approval before procurement
- **Request for Quotation (RFQ):** Formal invitation to vendors to submit price quotes
- **Purchase Order (PO):** Legally binding commitment to purchase from a vendor at agreed terms
- **Goods Receipt Note (GRN):** Record of goods received from vendor, matched against PO
- **3-Way Match:** Comparison of PO, GRN, and vendor invoice to authorize payment
- **Blanket PO:** Long-term purchase commitment with release mechanism for multiple deliveries
- **Separation of Duties (SOD):** Control mechanism ensuring different people perform different stages of transaction
- **Approval Matrix:** Rules defining approval authority based on amount, category, or other criteria
- **Landed Cost:** Total cost of purchase including freight, insurance, duties, and customs

---

**Document Version:** 1.0.0  
**Last Updated:** November 15, 2025  
**Status:** Ready for Review and Implementation Planning

---

## Notes on Bounded Context Coherence

This package intentionally violates the **Maximum Atomicity** principle because:

1. **Procurement is a cohesive business process** - splitting requisition, RFQ, PO, GRN, and invoice matching into separate packages would create excessive orchestration overhead and violate domain cohesion.

2. **Statutory coupling is domain-specific** - tax rules, import duties, and compliance requirements are procurement-specific and change too frequently to externalize.

3. **Workflow states are procurement-specific** - while we leverage nexus-workflow engine, the actual states (pending_approval, sent_to_vendor, partially_received) are domain concepts that belong in the bounded context.

4. **Data ownership is clear** - all procurement documents form a cohesive aggregate where entities reference each other tightly (PO references PR, GRN references PO, invoice references PO and GRN).

**This is intentional and aligns with Domain-Driven Design principles** where bounded contexts should be cohesive even if it means sacrificing some atomicity. The package remains **independently deployable and testable**, but it is **not atomically subdivided** into smaller packages.
