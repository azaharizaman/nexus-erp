nexus-hrm Package Requirements
Version: 1.0.0 Last Updated: November 15, 2025 Status: Architecture Design - Progressive Disclosure Model

Executive Summary
nexus-hrm is a progressive, headless Human Resource Management package for PHP/Laravel that scales from a simple employee directory to a complete enterprise-grade HRIS without ever forcing you to refactor working code.
The Problem We Solve
Most HR solutions force a brutal choice:
	•	Lightweight packages (basic employee tables) → outgrown in months
	•	Full HRIS suites (Workday, BambooHR, SAP SuccessFactors) → overkill, expensive, and lock you into their UI and workflow
nexus-hrm solves both with true progressive disclosure in a fully headless, atomic package:
	1	Level 1: Employee Foundation (5 minutes) – Trait on your Employee model, zero new tables beyond nexus-backoffice, instant org hierarchy, personal data, contracts, and basic lifecycle states.
	2	Level 2: Core HR Processes (1–2 days) – Promote to database-driven modules: leave management, attendance tracking, performance reviews, disciplinary tracking with full workflow integration.
	3	Level 3: Enterprise HRIS (Production-ready) – Recruitment pipeline, onboarding/offboarding automation, training & certifications, compliance reporting, HR analytics, and global policy engine.
Core Philosophy
	1	Progressive Disclosure – You only learn what you need, exactly when you need it
	2	100% Backwards Compatible – Level 1 code continues working perfectly after promoting to Level 2/3
	3	Fully Headless Backend – Pure API-first, no views, no Blade, no frontend assumptions
	4	Framework-Agnostic Core – Zero Laravel dependencies in the actual HR engine
	5	Built for Nexus ERP Ecosystem – Seamless integration with nexus-backoffice, nexus-workflow, nexus-tenancy, nexus-audit-log, nexus-settings
	6	Extensible Everything – Policy plugins, document templates, compliance rules, calculation engines
Why This Approach Wins
For Mass Market (80%):
	•	Works immediately with nexus-backoffice staff entities
	•	Zero-migration upgrade path
	•	Fastest time-to-value in the entire Laravel HR space
For Enterprise/ERP (20%):
	•	Full multi-country compliance framework (MY, SG, UAE, EU-ready)
	•	Advanced leave entitlement formulas (pro-rata, carry-forward, negative balance)
	•	Recruitment ATS with configurable pipelines
	•	Performance module with 360° reviews, OKRs, calibration
	•	Complete audit trail & data retention policies

Personas & User Stories
Personas
ID
Persona
Role
Primary Goal
P1
Small Business Developer
Full-stack Laravel dev
“Store employees, departments, job titles in <10 minutes”
P2
Mid-Size ERP Developer
Backend architect
“Implement leave + attendance + performance with proper audit”
P3
HR Manager / Admin
Business user
“Manage leave balances, run payroll reports, handle onboarding”
P4
Employee
End user
“Apply for leave, clock in/out, view payslip (via future payroll)”
P5
Compliance Officer
Legal/audit
“Generate government reports, ensure data retention rules”
User Stories
Level 1: Employee Foundation (Mass Appeal)
ID
Persona
Story
Priority
US-001
P1
As a developer, I want my existing Employee model to instantly support org hierarchy via nexus-backoffice
High
US-002
P1
As a developer, I want basic personal, contract, and emergency contact data without new tables
High
US-003
P1
As a developer, I want employee lifecycle states (prospect → active → terminated) with zero config
High
US-004
P1
As a developer, I want to query reporting lines and subordinates easily
High
Level 2: Core HR Processes
ID
Persona
Story
Priority
US-010
P2
As a developer, I want to enable leave management module with entitlement rules and workflow approval
High
US-011
P2
As a developer, I want attendance tracking (clock-in/out, overtime, shifts) with geolocation option
High
US-012
P2
As a developer, I want performance review cycles with configurable templates and 360° feedback
High
US-013
P3
As an HR manager, I want a unified employee dashboard showing leave balance, attendance summary, active disciplinary cases
High
US-014
P3
As an HR manager, I want to adjust leave balances manually with audit reason
High
US-015
P4
As an employee, I want to apply for leave and see real-time balance impact
High
Level 3: Enterprise HRIS
ID
Persona
Story
Priority
US-020
P2
As a developer, I want a full recruitment ATS with pipelines, candidate scoring, and email automation
High
US-021
P2
As a developer, I want automated onboarding/offboarding checklists using nexus-workflow
High
US-022
P3
As an HR manager, I want training & certification tracking with expiry reminders
High
US-023
P5
As a compliance officer, I want built-in statutory reports (Malaysia EA Form, SOCSO, EIS, UAE gratuity, etc.)
High
US-024
P2
As a developer, I want global HR policy engine (leave, public holidays, working hours) scoped by country/company
High
US-025
P3
As an HR manager, I want HR analytics dashboards (headcount, turnover, diversity, absenteeism)
Medium

Functional Requirements
FR-L1: Level 1 - Employee Foundation
ID
Requirement
Priority
Acceptance Criteria
FR-L1-001
Extend nexus-backoffice Staff entity with HR attributes
High
No new migration needed if backoffice exists
FR-L1-002
Support personal info, emergency contacts, dependents
High
Stored in JSON or dedicated columns (configurable)
FR-L1-003
Employee lifecycle state machine via HasHrmStates trait
High
prospect → active → inactive → terminated
FR-L1-004
Automatic org chart methods (manager(), subordinates(), department())
High
Uses backoffice hierarchy
FR-L1-005
Basic contract tracking (start date, probation, position, salary grade)
High
Simple fields, no complex calculations yet
FR-L2: Level 2 - Core HR Processes
ID
Requirement
Priority
Acceptance Criteria
FR-L2-001
Leave Management module with entitlement policies
High
Annual, sick, maternity, unpaid, custom; pro-rata, carry-forward
FR-L2-002
Leave requests fully integrated with nexus-workflow
High
Uses workflow tasks, approval matrix, comments
FR-L2-003
Attendance & Time Tracking (clock-in/out, breaks, overtime)
High
Web + optional mobile geolocation/clock-in
FR-L2-004
Shift & roster management
High
Recurring schedules, shift swapping with approval
FR-L2-005
Performance Management (reviews, goals, 360°, calibration)
High
Template-based, scheduled cycles, scoring, comments
FR-L2-006
Disciplinary & Grievance tracking
High
Stages (verbal → written → termination), workflow-driven
FR-L2-007
Document management per employee (contracts, certificates)
High
Encrypted storage, expiry tracking
FR-L2-008
Employee self-service API endpoints (profile, leave balance, attendance)
High
Ready for headless frontend
FR-L3: Level 3 - Enterprise HRIS
ID
Requirement
Priority
Acceptance Criteria
FR-L3-001
Recruitment ATS with configurable pipelines
High
Stages, candidate scoring, interview scheduling, email/SMS
FR-L3-002
Onboarding/Offboarding automated checklists via workflow
High
Task assignment, document signing, asset allocation
FR-L3-003
Training & Certification module with expiry reminders
High
Assign courses, track completion, schedule renewals
FR-L3-004
Global Policy Engine (country/company-specific rules)
High
Leave types, public holidays, working hours, overtime rules
FR-L3-005
Compliance & Statutory Reporting (EA, CP39, SOCSO, etc.)
High
Export formats ready for Malaysia, UAE, Singapore
FR-L3-006
HR Analytics & Metrics (turnover, absenteeism, diversity)
High
Integrates with nexus-analytics
FR-L3-007
Bulk data import/export (CSV/Excel templates)
Medium
Employees, leave transactions, attendance
FR-L3-008
Advanced entitlement formulas (seniority-based, part-time proration)
High
Custom PHP expressions or plugin system
FR-EXT: Extensibility
ID
Requirement
Priority
Acceptance Criteria
FR-EXT-001
Plugin interface for custom leave entitlement calculators
High
Implement EntitlementCalculatorContract
FR-EXT-002
Plugin interface for custom compliance reports
High
Return PDF/Excel via strategy pattern
FR-EXT-003
Plugin interface for custom attendance devices/rules
High
Biometric, QR, facial recognition hooks
FR-EXT-004
Custom field system per module (employee, leave, etc.)
High
JSON storage + encrypted option

Non-Functional Requirements
Performance
ID
Requirement
Target
Notes
PR-001
Employee list query (10,000 employees)
< 800ms
With proper indexing
PR-002
Leave balance calculation
< 150ms
Even with complex seniority rules
PR-003
Monthly attendance report
< 5s
For 5,000 employees
Security & Compliance
ID
Requirement
Scope
SR-001
PII encryption at rest (personal data)
Mandatory
SR-002
Field-level access control
Integration with gates/policies
SR-003
Data retention & deletion policies
GDPR, PDPA compliant
SR-004
All changes immutable audit trail
Via nexus-audit-log
Reliability
ID
Requirement
Notes
REL-001
Leave balance changes in transactions
Never negative unless allowed
REL-002
Concurrent clock-in prevention
Database locking
Scalability
ID
Requirement
Notes
SCL-001
100,000+ employees supported
Multi-tenant ready

Data Requirements (Key Additional Tables)
Table
Purpose
Key Fields
hrm_leave_entitlements
Annual entitlement rules per employee/policy
employee_id, leave_type_id, year, days, carry_forward
hrm_leave_requests
Leave applications
employee_id, type, dates, status, workflow_instance_id
hrm_attendance_records
Clock-in/out events
employee_id, datetime_in/out, type, location
hrm_shifts
Shift definitions & assignments
employee_id, date, shift_start/end
hrm_performance_reviews
Review cycles & submissions
employee_id, cycle_id, reviewer_id, scores, comments
hrm_recruitment_pipelines
Candidate pipelines
vacancy_id, candidate_id, stage, score
hrm_training_records
Training & certifications
employee_id, course_id, completed_at, expiry_at
hrm_policies
Global/country/company HR policies
scope (system/tenant/company/country), config_json

Dependencies
Required
	•	PHP ≥ 8.2
	•	nexus-backoffice ≥ 2.0
	•	nexus-workflow ≥ 3.0
	•	nexus-tenancy
	•	nexus-audit-log
	•	nexus-settings
Optional
	•	nexus-analytics (for HR dashboards)
	•	nexus-payroll (future integration – employee salary data)

Ready for your review. Once you approve or give feedback on nexus-hrm, I’ll immediately deliver the full nexus-payroll requirements document following the exact same structure and quality.
