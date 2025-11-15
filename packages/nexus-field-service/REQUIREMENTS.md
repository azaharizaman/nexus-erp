# nexus-field-service Package Requirements

**Version:** 1.0.0  
**Last Updated:** November 15, 2025  
**Status:** Initial Requirements - Cohesive Bounded Context Model

---

## Executive Summary

**nexus-field-service** (namespace: `Nexus\FieldService`) is a **cohesive field service management system** for PHP/Laravel that manages the complete field service lifecycle—from work order creation, technician scheduling and dispatching, mobile job execution, materials/parts consumption, quality inspection, to service report generation and billing integration.

### The Domain Complexity We Embrace

Unlike atomic core packages (tenancy, settings, workflow), field service is a **vertical business domain** where **atomicity must yield to cohesion**:

1. **Workflow Specificity:** Field service workflows (work order creation → scheduling → dispatching → job execution → completion → billing) are domain-specific state machines that belong within the bounded context.
2. **Data Ownership:** Work orders, schedules, technician assignments, service reports, and parts consumption form a cohesive aggregate where splitting across packages would create integration complexity.
3. **Industry Variability:** HVAC, facilities management, utilities, equipment maintenance, cleaning services, and installation services all have different operational patterns but share common field service workflows.
4. **Mobile-First Nature:** Field technicians require offline-capable mobile interfaces with photo capture, signature collection, GPS tracking, and real-time synchronization.

### Core Philosophy

1. **Bounded Context Coherence** - All field service entities and rules live together in one package
2. **Domain-Driven Design** - Rich domain models with encapsulated field service logic
3. **Progressive Complexity** - Start simple (basic work orders), grow to advanced (preventive maintenance, SLA management, route optimization)
4. **Industry Flexibility** - Supports multiple service industries (HVAC, facilities, utilities, equipment, cleaning, installation)
5. **Mobile-First Design** - Built for technician mobile experience with offline capabilities

### Why This Approach Works

**For Small Service Companies (60%):**
- Simple work order creation and assignment
- Technician scheduling (daily/weekly calendar view)
- Mobile job execution (photos, notes, signature)
- Basic service reporting
- Parts consumption tracking

**For Mid-Market Service Companies (30%):**
- Advanced scheduling and route optimization
- Preventive maintenance planning
- Asset and equipment management
- Quality inspection workflows
- Service contract and SLA management
- Customer portal for service requests

**For Enterprise Service Organizations (10%):**
- Multi-location dispatching
- Real-time GPS tracking and fleet management
- IoT integration for predictive maintenance
- Advanced analytics (technician productivity, first-time fix rate)
- Capacity planning and resource optimization
- Compliance tracking and audit trails

---

## Architectural Position in Nexus ERP

### Relationship to Core Packages

| Core Package | Relationship | Usage Pattern |
|-------------|--------------|---------------|
| **nexus-tenancy** | Depends On | All field service data is tenant-scoped via `BelongsToTenant` trait |
| **nexus-workflow** | Leverages | Uses workflow engine for work order approvals and service contract workflows |
| **nexus-sequencing** | Depends On | Uses sequence generation for work order numbers, service report numbers |
| **nexus-settings** | Depends On | Retrieves field service settings (SLA thresholds, billing rates, scheduling rules) |
| **nexus-audit-log** | Depends On | Comprehensive audit trail for all field service operations (compliance, dispute resolution) |
| **nexus-backoffice** | Depends On | Links to technicians, teams, departments for scheduling and dispatching |
| **nexus-inventory** | Tightly Integrated | Consumes parts/materials, tracks stock levels, manages technician van inventory |
| **nexus-accounting** | Consumes From | Posts service billing (labor hours, parts, travel charges) to accounts receivable |
| **nexus-crm** | Integrates With | Links work orders to customers, service locations, assets |
| **nexus-project-management** | Optional | Links complex installation projects to field service work orders |

### Why This Is NOT an Atomic Package

**Field Service violates the independence criterion:**
- Cannot be meaningfully subdivided (splitting work orders from scheduling would break operational coherence)
- Mobile execution workflows are tightly coupled to work order lifecycle
- Asset management and maintenance scheduling are intrinsically linked
- Service contracts and SLA tracking require tight coupling between work orders and customer agreements
- Traceability requirements demand tight coupling between work orders, service reports, parts consumption, and billing

**This is a COHESIVE VERTICAL** where domain logic density justifies consolidation.

---

## Personas & User Stories

### Personas

| ID | Persona | Role | Primary Goal |
|-----|---------|------|--------------|
| **P1** | Service Manager | Dispatch center lead | "Efficiently schedule and dispatch technicians to maximize utilization and customer satisfaction" |
| **P2** | Dispatcher | Operations coordinator | "Assign technicians to jobs based on skills, location, and priority" |
| **P3** | Field Technician | Mobile workforce | "Receive clear job instructions, complete work efficiently, and document service accurately" |
| **P4** | Customer | Service recipient | "Request service, track technician arrival, and receive timely service completion reports" |
| **P5** | Service Coordinator | Customer service team | "Manage customer service contracts, track SLA compliance, and handle service requests" |
| **P6** | Maintenance Planner | Preventive maintenance team | "Schedule recurring maintenance activities and ensure asset upkeep" |

### User Stories

#### Level 1: Basic Field Service (Essential MVP)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-001** | P1 | As a service manager, I want to create work orders specifying service location, work type, and priority | **High** |
| **US-002** | P2 | As a dispatcher, I want to assign work orders to available technicians based on skills and location | **High** |
| **US-003** | P3 | As a field technician, I want to view my assigned jobs for the day on my mobile device | **High** |
| **US-004** | P3 | As a field technician, I want to start a job, capture time spent, and upload before/after photos | **High** |
| **US-005** | P3 | As a field technician, I want to record parts/materials used during service | **High** |
| **US-006** | P3 | As a field technician, I want to capture customer signature upon job completion | **High** |
| **US-007** | P3 | As a field technician, I want the system to auto-generate a service report (PDF) for customer | **High** |
| **US-008** | P4 | As a customer, I want to receive a service completion report via email with photos and technician notes | **High** |
| **US-009** | P1 | As a service manager, I want to view work order status (new, scheduled, in progress, completed, verified) | **High** |

#### Level 2: Advanced Field Service (Scheduling & Quality)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-010** | P2 | As a dispatcher, I want to view a calendar/map showing all technicians and their assignments | **High** |
| **US-011** | P2 | As a dispatcher, I want route optimization to minimize travel time between jobs | **High** |
| **US-012** | P2 | As a dispatcher, I want to reassign jobs when technicians call in sick or jobs take longer than expected | **High** |
| **US-013** | P3 | As a field technician, I want to fill out job-specific checklists (safety inspection, quality checks) | **High** |
| **US-014** | P3 | As a field technician, I want the app to capture my GPS location when I start/end a job | **High** |
| **US-015** | P6 | As a maintenance planner, I want to define preventive maintenance schedules (monthly/quarterly/yearly) | **High** |
| **US-016** | P6 | As a maintenance planner, I want the system to auto-generate PM work orders based on schedule | **High** |
| **US-017** | P5 | As a service coordinator, I want to manage customer service contracts with SLA terms | **High** |
| **US-018** | P5 | As a service coordinator, I want to track SLA compliance (response time, resolution time) | **High** |
| **US-019** | P1 | As a service manager, I want to link work orders to customer assets/equipment for service history | **High** |

#### Level 3: Enterprise Field Service (Contracts, Analytics & Automation)

| ID | Persona | Story | Priority |
|----|---------|-------|----------|
| **US-020** | P1 | As a service manager, I want to auto-assign work orders based on technician skills, proximity, and availability | **High** |
| **US-021** | P1 | As a service manager, I want to track technician productivity (jobs completed, avg time per job) | **High** |
| **US-022** | P1 | As a service manager, I want to analyze first-time fix rate and identify recurring issues | **High** |
| **US-023** | P4 | As a customer, I want to submit service requests via a customer portal | Medium |
| **US-024** | P4 | As a customer, I want to track technician en-route status in real-time | Medium |
| **US-025** | P5 | As a service coordinator, I want to receive SLA breach alerts before deadlines expire | **High** |
| **US-026** | P1 | As a service manager, I want to integrate with IoT devices for predictive maintenance alerts | Medium |
| **US-027** | P1 | As a service manager, I want to generate billing automatically based on service hours and parts used | **High** |

---

## Functional Requirements

### FR-L1: Level 1 - Basic Field Service (Essential MVP)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L1-001** | Create work order | **High** | • Work order number (auto-generated via nexus-sequencing)<br>• Customer and service location<br>• Work category (maintenance, installation, inspection, cleaning, repair, emergency)<br>• Priority level (low, normal, high, urgent)<br>• Description of work required<br>• Status (new, scheduled, in_progress, completed, verified, closed)<br>• Optional: link to asset/equipment |
| **FR-L1-002** | Technician assignment | **High** | • Assign work order to technician<br>• Reassign to different technician<br>• Notify technician via mobile app/email<br>• Track assignment history (audit trail) |
| **FR-L1-003** | Technician daily schedule | **High** | • Mobile app view of assigned jobs<br>• Sortable by priority, scheduled time<br>• Show customer address on map<br>• Navigation integration (Google Maps, Waze) |
| **FR-L1-004** | Mobile job execution | **High** | • Start job (capture start time, GPS location)<br>• Upload before photos<br>• Add work notes/findings<br>• Upload after photos<br>• End job (capture end time)<br>• Calculate labor hours automatically |
| **FR-L1-005** | Parts/materials consumption | **High** | • Search inventory for parts<br>• Add part to work order (part number, quantity)<br>• Track van inventory vs warehouse stock<br>• Auto-deduct from inventory on job completion<br>• Flag out-of-stock parts |
| **FR-L1-006** | Customer signature capture | **High** | • Digital signature pad on mobile device<br>• Capture customer name and date<br>• Store signature image with work order<br>• Optional: capture customer feedback/rating |
| **FR-L1-007** | Auto-generate service report | **High** | • PDF report generation<br>• Includes: customer info, work performed, time spent, parts used, photos, signature<br>• Branded template (company logo, colors)<br>• Email to customer automatically<br>• Store in document management system |
| **FR-L1-008** | Work order status tracking | **High** | • Dashboard view of all work orders<br>• Filter by status, technician, date range<br>• Visual status indicators (color-coded)<br>• Quick actions: schedule, assign, complete |

### FR-L2: Level 2 - Advanced Field Service (Scheduling & Quality)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L2-001** | Scheduling calendar view | **High** | • Daily/weekly/monthly calendar<br>• Drag-and-drop job assignment<br>• Technician availability view<br>• Color-coded by job priority<br>• Conflict detection (overlapping appointments) |
| **FR-L2-002** | Map-based dispatch view | **High** | • Interactive map showing all jobs and technicians<br>• Real-time technician location (GPS tracking)<br>• Visual route lines<br>• Distance/travel time calculations<br>• Cluster jobs by geographic area |
| **FR-L2-003** | Route optimization | **High** | • Calculate optimal job sequence for technician<br>• Minimize total travel distance/time<br>• Consider job priority and time windows<br>• Integration with routing APIs (Google Maps Directions API)<br>• Suggest job reassignment to reduce travel |
| **FR-L2-004** | Dynamic job reassignment | **High** | • Reassign job in real-time<br>• Notify both technicians (old and new)<br>• Update schedule automatically<br>• Capture reassignment reason<br>• Track reassignment metrics |
| **FR-L2-005** | Job-specific checklists | **High** | • Define checklist templates (HVAC inspection, safety check)<br>• Attach checklist to work order by job type<br>• Technician fills checklist on mobile (checkboxes, text, photos)<br>• Pass/fail criteria per checklist item<br>• Auto-fail job if critical items fail |
| **FR-L2-006** | GPS location tracking | **High** | • Capture GPS coordinates on job start/end<br>• Store location with work order<br>• Display job location on map<br>• Calculate distance traveled<br>• Geofencing: auto-start job when technician arrives at location |
| **FR-L2-007** | Preventive maintenance planning | **High** | • Define PM schedules (time-based: monthly/quarterly/yearly)<br>• Define PM schedules (meter-based: every 1000 hours)<br>• Link PM schedule to asset/equipment<br>• Auto-generate PM work orders based on schedule<br>• PM checklist templates |
| **FR-L2-008** | Asset/equipment management | **High** | • Asset master (asset ID, description, location, model, serial)<br>• Link work orders to assets<br>• Asset service history (all past jobs)<br>• Asset condition tracking<br>• Maintenance schedule by asset |
| **FR-L2-009** | Service contract management | **High** | • Customer service contract (contract number, start/end dates)<br>• SLA terms (response time: 4 hours, resolution time: 24 hours)<br>• Contract coverage (assets covered, services included)<br>• Contract status (active, expired, renewed)<br>• Link work orders to contracts |
| **FR-L2-010** | SLA compliance tracking | **High** | • SLA timer on work orders (response time, resolution time)<br>• Visual indicators (on track, at risk, breached)<br>• SLA breach alerts (email, SMS)<br>• SLA compliance dashboard<br>• Historical SLA metrics (% on-time) |
| **FR-L2-011** | Technician skills matrix | **High** | • Define skills (electrical, plumbing, HVAC, inspection)<br>• Assign skills to technicians<br>• Certification tracking (expiry dates, renewal alerts)<br>• Auto-assign jobs based on required skills<br>• Skills gap analysis |

### FR-L3: Level 3 - Enterprise Field Service (Contracts, Analytics & Automation)

| ID | Requirement | Priority | Acceptance Criteria |
|----|-------------|----------|---------------------|
| **FR-L3-001** | Auto-assign algorithm | **High** | • Evaluate technician skills, availability, proximity<br>• Consider job priority and SLA<br>• Consider travel time from current location<br>• Load balancing (distribute jobs evenly)<br>• Override auto-assignment if needed |
| **FR-L3-002** | Technician productivity analytics | **High** | • Jobs completed per day/week/month<br>• Average time per job<br>• Utilization rate (working hours / total hours)<br>• Overtime hours<br>• Jobs per technician comparison |
| **FR-L3-003** | First-time fix rate analysis | **High** | • Track jobs requiring follow-up visits<br>• Calculate first-time fix rate (% jobs completed on first visit)<br>• Identify recurring issues by asset/customer<br>• Root cause analysis workflow |
| **FR-L3-004** | Customer portal | Medium | • Customers submit service requests<br>• Track open jobs (status, assigned technician)<br>• View service history<br>• Download service reports/invoices<br>• Rate technician/service quality |
| **FR-L3-005** | Real-time technician tracking | Medium | • GPS tracking during working hours<br>• Show technician location on customer portal<br>• "Technician en route" notifications<br>• ETA calculations<br>• Privacy controls (GPS only during job hours) |
| **FR-L3-006** | SLA breach prevention | **High** | • Predictive alerts (30 minutes before SLA breach)<br>• Escalation workflow (notify manager, auto-reassign)<br>• SLA buffer time configuration<br>• Historical SLA performance reports |
| **FR-L3-007** | IoT integration for predictive maintenance | Medium | • Integrate with IoT sensors (temperature, vibration, pressure)<br>• Receive alerts when thresholds exceeded<br>• Auto-create work orders from IoT alerts<br>• Link IoT data to asset service history<br>• Trend analysis (predict failures before they occur) |
| **FR-L3-008** | Auto-billing integration | **High** | • Calculate labor cost (hours × billing rate)<br>• Add parts cost (from inventory)<br>• Add travel charges (distance × mileage rate)<br>• Auto-generate draft invoice after job completion<br>• Send invoice to nexus-accounting for posting |
| **FR-L3-009** | Webhook notifications | **High** | • Job created → notify customer<br>• Technician assigned → notify customer<br>• Technician en route → notify customer<br>• Job completed → notify customer<br>• Service report ready → notify customer |
| **FR-L3-010** | Advanced capacity planning | Medium | • Forecast future workload (historical trends)<br>• Identify technician capacity gaps<br>• Recommend hiring/outsourcing<br>• What-if analysis (add/remove technicians) |

---

## Non-Functional Requirements

### Performance Requirements

| ID | Requirement | Target | Notes |
|----|-------------|--------|-------|
| **PR-001** | Mobile app startup time | < 3 seconds | Including authentication and data sync |
| **PR-002** | Work order list loading (100 jobs) | < 1 second | With filtering and sorting |
| **PR-003** | Service report generation (with photos) | < 5 seconds | PDF generation with 10 photos |
| **PR-004** | Route optimization (20 jobs, 5 technicians) | < 10 seconds | Using Google Maps Directions API |
| **PR-005** | Auto-assignment algorithm | < 5 seconds | For single work order |
| **PR-006** | Offline mobile capability | Full functionality | Sync when connection restored |

### Security Requirements

| ID | Requirement | Scope |
|----|-------------|-------|
| **SR-001** | Tenant data isolation | All field service data MUST be tenant-scoped (via nexus-tenancy) |
| **SR-002** | Role-based access control | Enforce permissions: create-work-order, assign-technician, view-customer-data, manage-contracts |
| **SR-003** | Mobile app authentication | API token-based auth via Laravel Sanctum |
| **SR-004** | Customer signature security | Encrypted storage, tamper-proof (hashed with timestamp) |
| **SR-005** | GPS data privacy | Technician GPS data captured only during working hours, with consent |
| **SR-006** | Service report integrity | Completed service reports are immutable (audit trail) |
| **SR-007** | Customer portal access control | Customers see only their own work orders and service history |

### Reliability Requirements

| ID | Requirement | Notes |
|----|-------------|-------|
| **REL-001** | Mobile app offline mode | Technicians can work without internet, sync later |
| **REL-002** | Data sync conflict resolution | Last-write-wins with conflict logging |
| **REL-003** | Service report generation resilience | Retry failed PDF generation automatically |
| **REL-004** | GPS tracking fault tolerance | Continue operation if GPS unavailable |
| **REL-005** | Notification delivery guarantee | Queue notifications, retry failed deliveries |

### Usability Requirements

| ID | Requirement | Notes |
|----|-------------|-------|
| **US-001** | Mobile app simplicity | Technicians complete jobs with < 10 taps |
| **US-002** | Photo upload size limit | Max 5MB per photo, auto-compress if needed |
| **US-003** | Signature capture responsiveness | No lag when drawing signature |
| **US-004** | Offline indicator | Clear visual indicator when app is offline |
| **US-005** | Customer portal ease of use | Submit service request in < 2 minutes |

---

## Domain Model

### Core Entities

```
WorkOrder (Aggregate Root)
├── id (UUID)
├── tenant_id (UUID)
├── work_order_number (string) - via nexus-sequencing
├── customer_id (UUID) - from nexus-crm
├── service_location_id (UUID) - customer site/address
├── asset_id (UUID, nullable) - equipment/asset being serviced
├── work_category (enum: maintenance, installation, inspection, cleaning, repair, emergency)
├── priority (enum: low, normal, high, urgent)
├── description (text)
├── status (enum: new, scheduled, in_progress, on_hold, completed, verified, closed, cancelled)
├── created_by (UUID)
├── created_at (datetime)
├── scheduled_date (date, nullable)
├── scheduled_start_time (time, nullable)
├── scheduled_end_time (time, nullable)
├── assigned_technician_id (UUID, nullable) - from nexus-backoffice users
├── assigned_at (datetime, nullable)
├── started_at (datetime, nullable)
├── completed_at (datetime, nullable)
├── actual_duration_minutes (int, nullable)
├── service_contract_id (UUID, nullable)
├── sla_response_deadline (datetime, nullable)
├── sla_resolution_deadline (datetime, nullable)
├── sla_response_met (boolean, nullable)
├── sla_resolution_met (boolean, nullable)
├── customer_rating (int, nullable) - 1-5 stars
├── customer_feedback (text, nullable)
└── Parts Used (hasMany PartsConsumption)
└── Service Activities (hasMany ServiceActivity)
└── Service Report (hasOne ServiceReport)
└── Assignment History (hasMany WorkOrderAssignment)

ServiceLocation
├── id (UUID)
├── tenant_id (UUID)
├── customer_id (UUID)
├── location_name (string)
├── address_line_1 (string)
├── address_line_2 (string, nullable)
├── city (string)
├── state (string)
├── postal_code (string)
├── country (string)
├── latitude (decimal, nullable)
├── longitude (decimal, nullable)
├── contact_person (string, nullable)
├── contact_phone (string, nullable)
└── site_instructions (text, nullable) - access codes, parking, etc.

Asset
├── id (UUID)
├── tenant_id (UUID)
├── customer_id (UUID)
├── service_location_id (UUID, nullable)
├── asset_number (string)
├── asset_type (string) - HVAC unit, pump, elevator, etc.
├── manufacturer (string, nullable)
├── model (string, nullable)
├── serial_number (string, nullable)
├── installation_date (date, nullable)
├── warranty_expiry_date (date, nullable)
├── condition (enum: excellent, good, fair, poor, critical)
├── last_service_date (date, nullable)
├── next_service_due (date, nullable)
└── attachments (json) - manuals, diagrams
└── Service History (hasMany WorkOrder)
└── Maintenance Schedule (hasMany MaintenanceSchedule)

ServiceActivity
├── id (UUID)
├── work_order_id (UUID)
├── activity_type (enum: diagnosis, repair, replacement, inspection, cleaning, calibration)
├── description (text)
├── technician_id (UUID)
├── started_at (datetime)
├── ended_at (datetime)
├── duration_minutes (int)
├── latitude (decimal, nullable) - GPS location
├── longitude (decimal, nullable)
├── notes (text, nullable)
└── Photos (hasMany ServicePhoto)

ServicePhoto
├── id (UUID)
├── service_activity_id (UUID)
├── photo_type (enum: before, after, issue, resolution)
├── file_path (string)
├── file_size (int)
├── mime_type (string)
├── caption (string, nullable)
├── latitude (decimal, nullable)
├── longitude (decimal, nullable)
└── captured_at (datetime)

PartsConsumption
├── id (UUID)
├── work_order_id (UUID)
├── part_id (UUID) - from nexus-inventory
├── part_number (string)
├── part_description (string)
├── quantity_used (decimal)
├── unit_price (decimal)
├── total_cost (decimal)
├── consumed_from (enum: warehouse, van_stock)
└── consumed_at (datetime)

ServiceReport
├── id (UUID)
├── tenant_id (UUID)
├── work_order_id (UUID)
├── report_number (string) - via nexus-sequencing
├── generated_at (datetime)
├── report_format (enum: pdf, html)
├── file_path (string, nullable) - if PDF stored
├── customer_signature (text) - base64 encoded image
├── customer_name (string)
├── signature_captured_at (datetime)
├── emailed_to (string, nullable)
└── email_sent_at (datetime, nullable)

ServiceContract
├── id (UUID)
├── tenant_id (UUID)
├── customer_id (UUID)
├── contract_number (string) - via nexus-sequencing
├── contract_type (enum: time_and_materials, fixed_price, retainer, managed_service)
├── start_date (date)
├── end_date (date)
├── billing_frequency (enum: per_job, monthly, quarterly, annually)
├── contract_value (decimal)
├── status (enum: draft, active, expired, renewed, cancelled)
├── sla_response_hours (int, nullable)
├── sla_resolution_hours (int, nullable)
└── Covered Assets (manyToMany Asset via contract_assets pivot)
└── Work Orders (hasMany WorkOrder)

MaintenanceSchedule
├── id (UUID)
├── tenant_id (UUID)
├── asset_id (UUID)
├── schedule_name (string) - "Quarterly HVAC Maintenance"
├── schedule_type (enum: time_based, meter_based)
├── frequency_value (int) - e.g., 3 (months), 1000 (hours)
├── frequency_unit (enum: days, weeks, months, years, hours, cycles)
├── last_performed_date (date, nullable)
├── next_due_date (date, nullable)
├── checklist_template_id (UUID, nullable)
├── assigned_technician_id (UUID, nullable)
├── is_active (boolean)
└── auto_generate_work_order (boolean)

ChecklistTemplate
├── id (UUID)
├── tenant_id (UUID)
├── template_name (string)
├── work_category (enum) - same as WorkOrder
├── description (text, nullable)
└── Items (hasMany ChecklistItem)

ChecklistItem
├── id (UUID)
├── checklist_template_id (UUID)
├── item_number (int)
├── item_description (string)
├── item_type (enum: checkbox, text, number, photo, signature)
├── is_required (boolean)
├── is_critical (boolean) - if fail, entire checklist fails
├── expected_value (string, nullable) - for pass/fail criteria
└── display_order (int)

ServiceChecklist
├── id (UUID)
├── work_order_id (UUID)
├── checklist_template_id (UUID)
├── completed_by (UUID)
├── completed_at (datetime)
├── overall_result (enum: passed, failed)
└── Responses (hasMany ChecklistResponse)

ChecklistResponse
├── id (UUID)
├── service_checklist_id (UUID)
├── checklist_item_id (UUID)
├── response_value (text) - actual value entered by technician
├── passed (boolean, nullable)
├── photo_path (string, nullable)
└── notes (text, nullable)

TechnicianSchedule
├── id (UUID)
├── tenant_id (UUID)
├── technician_id (UUID)
├── date (date)
├── start_time (time)
├── end_time (time)
├── availability_status (enum: available, unavailable, on_leave, sick, training)
├── notes (text, nullable)
└── Work Orders (manyToMany WorkOrder - scheduled jobs for the day)

TechnicianSkill
├── id (UUID)
├── tenant_id (UUID)
├── technician_id (UUID)
├── skill_name (string) - electrical, plumbing, HVAC, refrigeration, etc.
├── proficiency_level (enum: beginner, intermediate, advanced, expert)
├── certification_number (string, nullable)
├── certification_expiry (date, nullable)
└── verified_by (UUID, nullable)

WorkOrderAssignment
├── id (UUID)
├── work_order_id (UUID)
├── assigned_to_technician_id (UUID)
├── assigned_from_technician_id (UUID, nullable) - for reassignments
├── assigned_by (UUID)
├── assigned_at (datetime)
├── reassignment_reason (text, nullable)
└── is_current (boolean) - only one current assignment per work order
```

### Aggregate Relationships

```
WorkOrder (Aggregate Root)
  └─> ServiceActivities (Entities)
      └─> ServicePhotos (Value Objects)
  └─> PartsConsumption (Entities)
  └─> ServiceReport (Entity)
      └─> CustomerSignature (Value Object)
  └─> ServiceChecklist (Entity)
      └─> ChecklistResponses (Value Objects)
  └─> WorkOrderAssignments (Value Objects)

Asset (Aggregate Root)
  └─> MaintenanceSchedules (Entities)
  └─> WorkOrders (via relationship)

ServiceContract (Aggregate Root)
  └─> CoveredAssets (via many-to-many)
  └─> WorkOrders (via relationship)
```

---

## Business Rules

| ID | Rule | Level |
|----|------|-------|
| **BR-001** | Work order must have a customer and service location | All levels |
| **BR-002** | Cannot assign work order to technician without required skills | Level 2+ |
| **BR-003** | Cannot start work order without assignment to technician | All levels |
| **BR-004** | Work order can only be completed if all critical checklist items pass | Level 2+ |
| **BR-005** | Parts consumption auto-deducts from technician van stock first, then warehouse | All levels |
| **BR-006** | Service report can only be generated after work order is completed | All levels |
| **BR-007** | Customer signature is required before work order can be marked verified | All levels |
| **BR-008** | SLA deadlines calculated from service contract terms | Level 2+ |
| **BR-009** | SLA breach triggers escalation workflow (notify manager, auto-reassign) | Level 3 |
| **BR-010** | Preventive maintenance work orders auto-generated 7 days before due date | Level 2+ |
| **BR-011** | Cannot schedule technician beyond their daily capacity (8 hours default) | Level 2+ |
| **BR-012** | GPS location capture required when starting/ending job | Level 2+ |
| **BR-013** | Asset must have maintenance schedule if covered by service contract | Level 2+ |
| **BR-014** | Expired service contracts prevent new work order creation (unless emergency) | Level 2+ |
| **BR-015** | Route optimization respects job time windows (scheduled start/end times) | Level 2+ |

---

## Workflow State Machines

### Work Order Workflow

```
States:
  - new (initial, draft state)
  - scheduled (date/time assigned, technician may or may not be assigned)
  - assigned (technician assigned and notified)
  - in_progress (technician started job)
  - on_hold (temporarily paused)
  - completed (job finished by technician)
  - verified (customer signed off)
  - closed (billing complete)
  - cancelled (terminated)

Transitions:
  schedule: new → scheduled
    - Guard: valid scheduled date/time
    - Action: update scheduled_date and times
    
  assign: [new, scheduled] → assigned
    - Guard: technician has required skills, is available
    - Action: assign_technician_id, notify technician
    - Triggers: TechnicianAssignedEvent
    
  reassign: assigned → assigned (change technician)
    - Guard: user has reassign-technician permission
    - Requires: reassignment reason
    - Action: update assignment history
    - Triggers: TechnicianReassignedEvent
    
  start: assigned → in_progress
    - Guard: technician confirmed arrival at location
    - Action: capture start time, GPS location
    - Triggers: JobStartedEvent
    
  pause: in_progress → on_hold
    - Requires: hold reason (awaiting parts, customer unavailable, etc.)
    - Action: capture hold time
    
  resume: on_hold → in_progress
    - Action: resume activity logging
    
  complete: in_progress → completed
    - Validates: all required checklist items completed
    - Validates: service activities logged
    - Action: capture end time, calculate duration
    - Action: auto-generate service report
    - Triggers: JobCompletedEvent, GenerateServiceReportEvent
    
  verify: completed → verified
    - Validates: customer signature captured
    - Action: finalize service report, email to customer
    - Triggers: ServiceReportSentEvent
    
  close: verified → closed
    - Validates: billing posted (if applicable)
    - Action: archive work order
    - Triggers: WorkOrderClosedEvent
    
  cancel: [new, scheduled, assigned] → cancelled
    - Guard: user has cancel-work-order permission
    - Requires: cancellation reason
    - Action: reverse parts allocation, notify customer
    - Triggers: WorkOrderCancelledEvent
```

### Service Contract Workflow

```
States:
  - draft (being prepared)
  - active (in effect)
  - expiring_soon (< 30 days remaining)
  - expired (past end date)
  - renewed (replaced by new contract)
  - cancelled (terminated early)

Transitions:
  activate: draft → active
    - Guard: customer approved
    - Action: set start_date, enable SLA tracking
    - Triggers: ContractActivatedEvent
    
  warn_expiring: active → expiring_soon
    - Guard: 30 days before end_date
    - Action: send renewal reminder email
    - Triggers: ContractExpiringEvent
    
  expire: expiring_soon → expired
    - Guard: end_date reached
    - Action: deactivate, block new work orders (unless emergency)
    - Triggers: ContractExpiredEvent
    
  renew: [expiring_soon, expired] → renewed
    - Action: create new contract, link old contract
    - Triggers: ContractRenewedEvent
    
  cancel: [active, expiring_soon] → cancelled
    - Guard: user has cancel-contract permission
    - Requires: cancellation reason
    - Action: pro-rate billing, close open work orders
    - Triggers: ContractCancelledEvent
```

### Preventive Maintenance Schedule

```
Trigger Conditions:
  - time_based: next_due_date reached (7 days before actual due date)
  - meter_based: asset usage hours/cycles exceed threshold
  
Actions:
  - Create new work order (auto-populated from schedule)
  - Assign to designated technician (if specified)
  - Attach checklist template
  - Set priority to normal (unless overdue, then high)
  - Update last_performed_date after completion
  - Calculate next_due_date based on frequency
  
Events:
  - MaintenanceScheduleTriggered
  - PMWorkOrderGenerated
```

---

## Integration Points

### With Core Packages

| Core Package | Integration Type | Usage |
|-------------|------------------|-------|
| **nexus-tenancy** | Direct Dependency | All models use `BelongsToTenant` trait for data isolation |
| **nexus-sequencing** | Service Call | Generate work order numbers, service report numbers, contract numbers |
| **nexus-settings** | Service Call | Retrieve field service settings (SLA defaults, billing rates, scheduling rules) |
| **nexus-audit-log** | Event Listener | Log ALL field service operations for compliance and dispute resolution |
| **nexus-workflow** | Engine Usage | Leverage workflow engine for contract approvals and escalations |
| **nexus-backoffice** | Data Reference | Link technicians to users, teams to dispatch groups |

### With Business Domain Packages

| Package | Integration Type | Data Flow |
|---------|------------------|-----------|
| **nexus-inventory** | Tightly Integrated | Consume parts from warehouse/van stock, track stock levels, reorder parts |
| **nexus-accounting** | Event-Driven | Post service billing (labor + parts + travel) to accounts receivable, invoice generation |
| **nexus-crm** | Data Reference | Link work orders to customers, service locations, track customer service history |
| **nexus-project-management** | Optional | Link complex installation projects to multiple field service work orders |
| **nexus-hrm** | Optional | Technician payroll integration (overtime, on-call pay) |

### External Integrations (Optional)

| System | Integration Method | Purpose |
|--------|-------------------|---------|
| **Mapping Services** | REST API | Google Maps, HERE Maps for routing, geocoding, traffic data |
| **GPS Tracking Devices** | API/Webhook | Fleet tracking systems (Geotab, Verizon Connect) |
| **SMS Gateways** | REST API | Twilio, Plivo for technician/customer notifications |
| **IoT Platforms** | MQTT/REST | Industrial IoT sensors for predictive maintenance |
| **Customer Apps** | Mobile SDK | Native iOS/Android apps for customers to request service |
| **Payment Gateways** | REST API | Stripe, Square for on-site payment collection |

---

## Testing Requirements

### Unit Tests
- Work order state machine transitions
- SLA deadline calculation
- Route optimization algorithm
- Auto-assignment logic (skill matching, proximity calculation)
- Parts consumption and inventory deduction
- Service report generation

### Feature Tests
- Complete work order lifecycle (create → schedule → assign → start → complete → verify → close)
- Preventive maintenance auto-generation
- Technician reassignment flow
- Customer signature capture and service report email
- SLA breach detection and escalation
- Checklist pass/fail logic

### Integration Tests
**Note:** Integration tests involving cross-package interactions should be orchestrated through the Nexus\Erp core package and implemented in the Edward demo application's test suite, per the architectural guidelines on independent testability.

The following integration scenarios are documented here for reference but will be tested at the orchestration layer:
- nexus-inventory integration: verify parts deduction and reorder triggers
- nexus-accounting integration: verify billing posts to AR
- nexus-crm integration: verify customer and location data sync

Package-level integration tests (testing within nexus-field-service boundaries):
- Map API integration: verify route optimization accuracy
- Mobile app sync: verify offline changes sync correctly

### Performance Tests
- Load test: 100 technicians with 1000 active work orders
- Stress test: route optimization for 50 technicians, 200 jobs
- Mobile app: offline data storage limit (1000 work orders)
- Service report generation: 100 concurrent PDF generations

### Mobile App Tests
- Offline mode: create work order, sync when online
- Photo upload: compress and upload 20 photos
- GPS accuracy: verify location within 10 meters
- Signature capture: smooth drawing on various screen sizes
- Battery consumption: 8-hour shift with GPS tracking

---

## Package Structure

```
packages/nexus-field-service/
├── src/
│   ├── Models/                      # Domain entities
│   │   ├── WorkOrder.php
│   │   ├── ServiceLocation.php
│   │   ├── Asset.php
│   │   ├── ServiceActivity.php
│   │   ├── ServicePhoto.php
│   │   ├── PartsConsumption.php
│   │   ├── ServiceReport.php
│   │   ├── ServiceContract.php
│   │   ├── MaintenanceSchedule.php
│   │   ├── ChecklistTemplate.php
│   │   ├── ChecklistItem.php
│   │   ├── ServiceChecklist.php
│   │   ├── ChecklistResponse.php
│   │   ├── TechnicianSchedule.php
│   │   ├── TechnicianSkill.php
│   │   └── WorkOrderAssignment.php
│   │
│   ├── Repositories/                # Data access layer
│   │   ├── WorkOrderRepository.php
│   │   ├── ServiceContractRepository.php
│   │   ├── AssetRepository.php
│   │   └── TechnicianScheduleRepository.php
│   │
│   ├── Services/                    # Business logic services
│   │   ├── WorkOrderService.php
│   │   ├── DispatchingService.php
│   │   ├── SchedulingService.php
│   │   ├── RouteOptimizationService.php
│   │   ├── MaintenancePlanningService.php
│   │   ├── ServiceReportService.php
│   │   ├── SLAManagementService.php
│   │   ├── TechnicianAssignmentService.php
│   │   └── BillingIntegrationService.php
│   │
│   ├── Contracts/                   # Interfaces for external consumption
│   │   ├── WorkOrderServiceContract.php
│   │   ├── DispatchingServiceContract.php
│   │   ├── SchedulingServiceContract.php
│   │   └── SLAManagementServiceContract.php
│   │
│   ├── Enums/                       # Domain enums
│   │   ├── WorkOrderStatus.php
│   │   ├── WorkCategory.php
│   │   ├── Priority.php
│   │   ├── ContractType.php
│   │   ├── ScheduleType.php
│   │   └── ChecklistItemType.php
│   │
│   ├── Events/                      # Domain events
│   │   ├── WorkOrderCreated.php
│   │   ├── TechnicianAssigned.php
│   │   ├── JobStarted.php
│   │   ├── JobCompleted.php
│   │   ├── ServiceReportGenerated.php
│   │   ├── SLABreachWarning.php
│   │   ├── PMWorkOrderGenerated.php
│   │   └── ContractExpiring.php
│   │
│   ├── Workflows/                   # Workflow definitions
│   │   ├── WorkOrderWorkflow.php
│   │   ├── ServiceContractWorkflow.php
│   │   └── MaintenanceScheduleWorkflow.php
│   │
│   ├── Rules/                       # Validation rules
│   │   ├── TechnicianSkillRequirement.php
│   │   ├── ScheduleCapacityCheck.php
│   │   └── SLADeadlineValidation.php
│   │
│   └── FieldServiceServiceProvider.php
│
├── database/
│   └── migrations/
│       ├── 2025_11_15_000001_create_work_orders_table.php
│       ├── 2025_11_15_000002_create_service_locations_table.php
│       ├── 2025_11_15_000003_create_assets_table.php
│       ├── 2025_11_15_000004_create_service_activities_table.php
│       ├── 2025_11_15_000005_create_service_photos_table.php
│       ├── 2025_11_15_000006_create_parts_consumption_table.php
│       ├── 2025_11_15_000007_create_service_reports_table.php
│       ├── 2025_11_15_000008_create_service_contracts_table.php
│       ├── 2025_11_15_000009_create_maintenance_schedules_table.php
│       ├── 2025_11_15_000010_create_checklist_templates_table.php
│       ├── 2025_11_15_000011_create_service_checklists_table.php
│       ├── 2025_11_15_000012_create_technician_schedules_table.php
│       ├── 2025_11_15_000013_create_technician_skills_table.php
│       └── 2025_11_15_000014_create_work_order_assignments_table.php
│
├── config/
│   └── field-service.php            # Package configuration
│
├── tests/
│   ├── Unit/
│   │   ├── Services/
│   │   │   ├── RouteOptimizationServiceTest.php
│   │   │   ├── SLAManagementServiceTest.php
│   │   │   └── TechnicianAssignmentServiceTest.php
│   │   └── Rules/
│   │       └── TechnicianSkillRequirementTest.php
│   │
│   └── Feature/
│       ├── WorkOrderLifecycleTest.php
│       ├── DispatchingFlowTest.php
│       ├── ServiceReportGenerationTest.php
│       ├── PreventiveMaintenanceTest.php
│       └── SLAComplianceTest.php
│
└── REQUIREMENTS.md                  # This document

Note: Controllers, Actions, Routes, and HTTP layer belong in Nexus\Erp orchestration layer.
See src/Actions/FieldService/ and src/Http/Controllers/Api/V1/FieldService/ in the main ERP package.
```

---

## Configuration

### field-service.php

```php
return [
    // Work order settings
    'work_orders' => [
        'auto_assign_enabled' => false,  // Enable auto-assignment algorithm
        'auto_close_after_days' => 7,    // Auto-close verified work orders after 7 days
        'require_customer_signature' => true,
        'allow_technician_self_assign' => false,
    ],
    
    // Scheduling settings
    'scheduling' => [
        'default_job_duration_minutes' => 60,
        'buffer_time_minutes' => 15,     // Travel time between jobs
        'max_jobs_per_technician_per_day' => 8,
        'work_hours_start' => '08:00',
        'work_hours_end' => '17:00',
        'enable_overtime' => true,
    ],
    
    // SLA settings
    'sla' => [
        'default_response_hours' => 4,
        'default_resolution_hours' => 24,
        'breach_warning_minutes' => 30,  // Alert 30 min before breach
        'enable_auto_escalation' => true,
    ],
    
    // Route optimization
    'routing' => [
        'enabled' => false,  // Enable route optimization
        'provider' => 'google_maps',  // google_maps, here_maps, mapbox
        'api_key' => env('ROUTING_API_KEY'),
        'consider_traffic' => true,
        'max_optimization_jobs' => 20,  // Limit for performance
    ],
    
    // GPS tracking
    'gps' => [
        'enabled' => false,  // Enable GPS location capture
        'capture_on_job_start' => true,
        'capture_on_job_end' => true,
        'geofencing_enabled' => false,
        'geofence_radius_meters' => 100,
    ],
    
    // Preventive maintenance
    'preventive_maintenance' => [
        'enabled' => false,  // Enable PM scheduling
        'auto_generate_days_before_due' => 7,
        'default_assigned_technician' => null,  // null = dispatcher assigns
        'skip_holidays' => true,
    ],
    
    // Service reports
    'service_reports' => [
        'format' => 'pdf',  // pdf or html
        'auto_email_customer' => true,
        'include_photos' => true,
        'max_photos_per_report' => 10,
        'template' => 'default',  // Blade template name
        'logo_path' => 'logo.png',
    ],
    
    // Mobile app
    'mobile' => [
        'offline_mode_enabled' => true,
        'max_offline_work_orders' => 100,
        'photo_max_size_mb' => 5,
        'photo_auto_compress' => true,
        'sync_interval_minutes' => 15,
    ],
    
    // Billing integration
    'billing' => [
        'auto_generate_invoice' => false,
        'default_labor_rate_per_hour' => 75.00,
        'default_travel_rate_per_km' => 0.50,
        'include_parts_markup_pct' => 20.0,
    ],
    
    // Notifications
    'notifications' => [
        'notify_customer_on_assignment' => true,
        'notify_customer_on_enroute' => false,
        'notify_customer_on_completion' => true,
        'notify_manager_on_sla_breach' => true,
    ],
];
```

---

## Success Metrics

| Metric | Target | Measurement Period | Why It Matters |
|--------|--------|-------------------|----------------|
| **Adoption Rate** | > 150 installations | 12 months | Validates field service solution viability |
| **Technician Utilization** | > 75% (working hours / available hours) | Ongoing | Measures operational efficiency |
| **First-Time Fix Rate** | > 85% of jobs completed on first visit | Ongoing | Measures service quality and efficiency |
| **SLA Compliance** | > 90% of jobs meet SLA deadlines | Ongoing | Measures customer satisfaction |
| **Customer Satisfaction** | > 4.2/5 average rating | Ongoing | Direct customer feedback |
| **Route Optimization Savings** | > 15% reduction in travel time | Ongoing | Cost savings from routing |
| **Mobile App Usability** | < 10 taps to complete job | Ongoing | Technician productivity |
| **Service Report Turnaround** | < 2 hours from job completion to customer email | Ongoing | Customer experience |

---

## Development Phases

### Phase 1: Core Field Service (Weeks 1-8)
- Implement work order management (create, assign, schedule)
- Implement basic technician scheduling (calendar view)
- Implement mobile job execution (start, photos, notes, end)
- Implement parts consumption tracking
- Implement service report generation (PDF)
- Basic customer signature capture
- Unit and feature tests for core flows

### Phase 2: Advanced Scheduling & Quality (Weeks 9-14)
- Implement map-based dispatch view
- Implement route optimization (Google Maps integration)
- Implement job-specific checklists
- Implement GPS location tracking
- Implement technician skills matrix and auto-assignment
- Implement service contract management
- Feature tests for scheduling and quality scenarios

### Phase 3: Preventive Maintenance & SLA (Weeks 15-20)
- Implement asset/equipment management
- Implement preventive maintenance scheduling
- Implement SLA tracking and breach alerts
- Implement customer portal (basic service request submission)
- Integration with nexus-inventory (parts management)
- Integration with nexus-accounting (billing posts)

### Phase 4: Enterprise Features & Analytics (Weeks 21-24)
- Implement advanced auto-assignment algorithm
- Implement real-time GPS tracking
- Implement IoT integration framework (predictive maintenance)
- Implement productivity analytics dashboard
- Implement first-time fix rate analysis
- Security audit and penetration testing

### Phase 5: Mobile App & Launch (Weeks 25-28)
- Develop native/hybrid mobile app (React Native or Flutter)
- Implement offline mode and sync
- Comprehensive mobile app testing
- Video tutorials and user guides
- Beta testing with 5-10 service companies
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
- nexus-backoffice (technician/user management)
- nexus-inventory (CRITICAL: parts consumption and van stock management)

### Optional (for full functionality)
- nexus-accounting (service billing and invoice generation)
- nexus-crm (customer and service location management)
- nexus-project-management (complex installation projects)
- Laravel Sanctum (mobile app authentication)
- Google Maps API or HERE Maps API (route optimization)
- Twilio or Plivo (SMS notifications)

---

## Glossary

- **Work Order:** Authorization to perform service at a customer location
- **Service Location:** Customer site/address where service is performed
- **Asset/Equipment:** Physical equipment being serviced (HVAC unit, pump, elevator, etc.)
- **Service Activity:** Individual task performed during a job (diagnosis, repair, replacement)
- **Parts Consumption:** Materials/parts used to complete a job
- **Service Report:** PDF/HTML document summarizing work performed, sent to customer
- **Service Contract:** Agreement defining scope, pricing, and SLA for ongoing service
- **SLA (Service Level Agreement):** Contractual commitment for response/resolution time
- **Preventive Maintenance (PM):** Scheduled maintenance to prevent breakdowns
- **Checklist:** Job-specific inspection or safety checklist
- **Route Optimization:** Algorithm to minimize technician travel time between jobs
- **First-Time Fix Rate:** Percentage of jobs completed on first visit (no follow-up needed)
- **Technician Utilization:** Percentage of working hours spent on billable jobs
- **Van Stock:** Parts inventory carried by technician in vehicle

---

**Document Version:** 1.0.0  
**Last Updated:** November 15, 2025  
**Status:** Ready for Review and Implementation Planning

---

## Notes on Bounded Context Coherence

This package intentionally violates the **Maximum Atomicity** principle because:

1. **Field Service is a tightly coupled business process** - splitting work orders, scheduling, dispatching, and mobile execution into separate packages would create excessive orchestration overhead and violate operational coherence.

2. **Mobile-first nature demands tight coupling** - technicians need seamless offline experience where work orders, checklists, parts, photos, and signatures are tightly integrated in a single mobile app workflow.

3. **Workflow states are field-service-specific** - while we leverage nexus-workflow engine, the actual states (new, scheduled, in_progress, verified) are domain concepts that belong in the bounded context.

4. **Data ownership is clear** - work orders, service activities, parts consumption, and service reports form a cohesive aggregate where entities reference each other tightly.

**This is intentional and aligns with Domain-Driven Design principles** where bounded contexts should be cohesive even if it means sacrificing some atomicity. The package remains **independently deployable and testable**, but it is **not atomically subdivided** into smaller packages.
