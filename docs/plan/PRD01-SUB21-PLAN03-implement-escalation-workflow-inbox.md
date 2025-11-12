---
plan: Implement Escalation Rules and Workflow Inbox
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, workflow-engine, escalation, deadline, workflow-inbox, overdue, approval-dashboard]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan adds automatic escalation rules for overdue approvals and a comprehensive workflow inbox for users to manage their pending approval tasks. It enables deadline enforcement with automatic escalation to supervisors, provides real-time status tracking, and delivers a user-friendly inbox interface with filtering, sorting, and bulk operations.

## 1. Requirements & Constraints

### Requirements

- **REQ-FR-WF-004**: Implement escalation rules for overdue approvals with deadline enforcement
- **REQ-FR-WF-006**: Provide workflow status tracking with real-time progress visualization
- **REQ-FR-WF-008**: Provide workflow inbox for pending approvals with filtering and sorting
- **REQ-BR-WF-003**: Escalations occur automatically when approval deadlines are exceeded
- **REQ-DR-WF-001**: Store workflow definitions with routing rules and conditions
- **REQ-DR-WF-002**: Maintain workflow instance state tracking current step and history
- **REQ-DR-WF-003**: Track approval actions with timestamps, comments, and attachments
- **REQ-PR-WF-002**: Support 1,000+ concurrent workflow instances
- **REQ-ARCH-WF-002**: Use Redis Queue for asynchronous workflow execution

### Security Constraints

- **SEC-001**: Escalation targets must be validated for appropriate authority
- **SEC-002**: Inbox must enforce tenant isolation (users only see their tenant's workflows)
- **SEC-003**: Workflow status must respect role-based visibility permissions
- **SEC-004**: Bulk operations must validate user has permission for each item

### Guidelines

- **GUD-001**: All PHP files must include `declare(strict_types=1);`
- **GUD-002**: Use Laravel 12+ queued jobs for escalation processing
- **GUD-003**: Follow PSR-12 coding standards, enforced by Laravel Pint
- **GUD-004**: Use cache for frequently accessed inbox queries
- **GUD-005**: All escalation actions must be logged for audit trail

### Patterns to Follow

- **PAT-001**: Use Command pattern for escalation execution
- **PAT-002**: Use Strategy pattern for different escalation types (email, reassign, notify)
- **PAT-003**: Use Repository pattern for inbox data access with optimized queries
- **PAT-004**: Use DTO pattern for inbox item representation
- **PAT-005**: Use Observer pattern for escalation event notifications

### Constraints

- **CON-001**: Escalation rules limited to 5 levels maximum (avoid infinite loops)
- **CON-002**: Inbox query must return results within 500ms for 1000 items
- **CON-003**: Bulk operations limited to 100 items maximum per request
- **CON-004**: Escalation check runs every 15 minutes minimum (configurable)
- **CON-005**: Workflow status cache TTL maximum 60 seconds for real-time feel

## 2. Implementation Steps

### GOAL-001: Escalation Rules Engine

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-WF-004, BR-WF-003, DR-WF-001 | Implement escalation rules engine with automatic deadline enforcement and multi-level escalation support. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Update `workflow_definitions.escalation_rules` JSONB structure: Define schema: [{level: 1, deadline_hours: 48, escalate_to_role_id: 5, escalate_to_user_id: null, escalation_action: 'notify'}, {level: 2, deadline_hours: 72, escalate_to_role_id: 6, escalation_action: 'reassign'}]. Add escalation_action enum: 'notify' (send alert), 'reassign' (change approver), 'auto_approve' (system auto-approval), 'reject' (auto-reject). Add notification_channels array: ['email', 'sms', 'in_app']. Document JSON schema in migration comments. Support up to 5 escalation levels (CON-001). | | |
| TASK-002 | Create migration `database/migrations/create_escalation_log_table.php`: Define `escalation_log` table with columns: id (BIGSERIAL), tenant_id (UUID/BIGINT, indexed, NOT NULL), workflow_step_id (BIGINT, foreign key, indexed), workflow_instance_id (BIGINT, foreign key, indexed), escalation_level (INTEGER), escalation_action (ENUM: 'notify', 'reassign', 'auto_approve', 'reject'), escalated_from_user_id (BIGINT, nullable FK), escalated_to_user_id (BIGINT, nullable FK), escalated_to_role_id (BIGINT, nullable FK), escalated_at (TIMESTAMP, indexed), reason (TEXT), notification_sent (BOOLEAN), created_at, updated_at. Add index on (workflow_instance_id, escalated_at). Add index on (escalated_to_user_id, escalated_at) for user inbox. | | |
| TASK-003 | Create `src/Contracts/EscalationServiceContract.php` interface: Define methods: `checkEscalations(): int` (checks all overdue steps, returns count processed), `escalateStep(WorkflowStep $step): bool` (escalates single step), `getEscalationRule(WorkflowDefinition $definition, WorkflowStep $step): ?array` (gets applicable escalation rule), `executeEscalation(WorkflowStep $step, array $rule): bool` (executes escalation action), `logEscalation(WorkflowStep $step, array $rule, string $result): void` (logs escalation). All methods with full PHPDoc. | | |
| TASK-004 | Create `src/Services/EscalationService.php` implementing `EscalationServiceContract`: Inject `WorkflowStepRepository`, `WorkflowDefinitionRepository`, `NotificationService`, `EventDispatcher`. Implement `checkEscalations()`: 1) Query workflow_steps where status='pending' AND due_at < now() AND has not been escalated at current level, 2) For each overdue step, call `escalateStep()`, 3) Count successful escalations, 4) Return count. Log execution statistics. Optimize query with indexes. Should handle 1000+ steps efficiently per PR-WF-002. | | |
| TASK-005 | Implement `escalateStep()` in `EscalationService`: Accept workflow_step. Get workflow_instance and workflow_definition. Calculate hours overdue: (now() - due_at) in hours. Get escalation_rules from definition. Find applicable rule based on hours overdue and escalation level. If no rule, log warning and return false. Call `executeEscalation()` with step and rule. If successful, update step's escalation_level, log in escalation_log. Dispatch `StepEscalatedEvent`. Return true on success. Use database transaction. | | |
| TASK-006 | Implement `executeEscalation()` in `EscalationService`: Accept workflow_step and escalation_rule. Switch on escalation_action: 'notify' sends notification to escalated_to_user/role via NotificationService, 'reassign' updates step's approver_user_id/approver_role_id and resets due_at (now + deadline_hours), 'auto_approve' calls WorkflowExecutorService::approveStep() with system user, 'reject' calls WorkflowExecutorService::rejectStep() with escalation reason. Log each action. Return true if action executed successfully, false on error. Handle errors gracefully (log, alert admin, continue to next step). | | |

### GOAL-002: Escalation Command and Scheduling

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| BR-WF-003, ARCH-WF-002, CON-004 | Implement escalation check command with scheduled execution and queued processing for scalability. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create `src/Commands/CheckWorkflowEscalationsCommand.php`: Artisan command `workflows:check-escalations` with signature. Call `EscalationService::checkEscalations()`. Log count of escalations processed. Add options: --dry-run (check but don't escalate), --workflow-id (check specific workflow), --level (check specific escalation level). Schedule command in Kernel.php: `$schedule->command('workflows:check-escalations')->everyFifteenMinutes()` (CON-004). Add cron expression customization via config. Support manual execution via artisan command. | | |
| TASK-008 | Create `src/Jobs/EscalateWorkflowStepJob.php` queued job: Implement `handle(int $stepId)` method. Inject `EscalationService`. Load workflow_step by ID. Call `EscalationService::escalateStep()`. Handle errors: log, dispatch `EscalationFailedEvent`, alert administrators. Queue job on 'workflows' queue for isolation. Use queue delay if batch processing (stagger jobs). Implement ShouldQueue interface. Support job retry with exponential backoff (3 attempts). Unique job per workflow_step to prevent duplicate escalations. | | |
| TASK-009 | Update `CheckWorkflowEscalationsCommand` to use queued jobs: Instead of processing escalations synchronously, dispatch `EscalateWorkflowStepJob` for each overdue step. This improves performance for large volumes (1000+ steps per PR-WF-002). Command becomes dispatcher: finds overdue steps, queues jobs, logs queue count. Jobs process asynchronously via queue workers. Add --sync flag to process synchronously for small volumes or debugging. Monitor queue depth to prevent backlog. | | |
| TASK-010 | Create `src/Events/StepEscalatedEvent.php` and `EscalationFailedEvent.php` implementing ShouldQueue: StepEscalatedEvent properties: workflow_step_id, workflow_instance_id, escalation_level, escalation_action, escalated_to, escalated_at. EscalationFailedEvent properties: workflow_step_id, error_message, failed_at. Queue on 'workflows' queue. Used by notification listeners and monitoring systems. Include escalation context for troubleshooting. | | |
| TASK-011 | Create `src/Listeners/NotifyEscalationListener.php`: Listen to StepEscalatedEvent. Extract escalated_to user/role. Send notification via notification channels (email, in-app). Notification content: workflow details, overdue duration, escalation level, action required. Include direct link to workflow inbox. Queue notification for asynchronous sending. Handle notification failures gracefully (log, retry). Support notification preferences per user. | | |

### GOAL-003: Workflow Inbox Data Layer

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-WF-008, PR-WF-002, CON-002 | Implement workflow inbox repository with optimized queries for listing, filtering, and sorting pending approvals. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-012 | Create `src/DTOs/WorkflowInboxItemDTO.php`: Define data transfer object with properties: workflow_instance_id, workflow_definition_code, workflow_definition_name, entity_type, entity_id, entity_display_name (e.g., PO number, expense claim title), current_step_number, current_step_name, status, assigned_at, due_at, is_overdue (boolean), overdue_hours (integer), priority (enum: 'high', 'medium', 'low'), initiated_by_user_name, delegation_info (if applicable), can_approve (boolean). Use readonly properties (PHP 8.2). Factory method `fromWorkflowStep(WorkflowStep $step): self`. Used for API responses and internal processing. | | |
| TASK-013 | Create `src/Contracts/WorkflowInboxRepositoryContract.php` interface: Define methods: `getInboxForUser(int $userId, array $filters = []): Collection` (returns inbox items for user), `getOverdueCount(int $userId): int` (count of overdue items), `getInboxStatistics(int $userId): array` (returns stats: total, overdue, by_entity_type), `markAsRead(int $workflowStepId, int $userId): bool` (marks step as viewed), `bulkReassign(array $stepIds, int $newApproverId): int` (bulk reassign). All methods with PHPDoc. | | |
| TASK-014 | Create `src/Repositories/WorkflowInboxRepository.php` implementing `WorkflowInboxRepositoryContract`: Inject `WorkflowStep`, `DelegationService`, `Cache`. Implement `getInboxForUser()`: 1) Query workflow_steps where (approver_user_id=$userId OR user has delegations to them) AND status='pending', 2) Eager load workflow_instance, workflow_definition, entity relationships, 3) Apply filters: entity_type, status, overdue, priority, date_range, 4) Apply sorting: due_at ASC (default), assigned_at DESC, priority DESC, 5) Return Collection of WorkflowInboxItemDTO. Cache result for 60 seconds (CON-005). Query must complete < 500ms for 1000 items (CON-002). Use database indexes for optimization. | | |
| TASK-015 | Implement `getInboxStatistics()` in `WorkflowInboxRepository`: Accept userId. Query aggregations: total count of pending items, overdue count (due_at < now()), grouped by entity_type, grouped by priority, oldest item age. Return array: {total: 45, overdue: 8, by_entity_type: {purchase_order: 20, expense_claim: 15, journal_entry: 10}, by_priority: {high: 5, medium: 30, low: 10}, oldest_overdue_hours: 72}. Cache for 5 minutes. Used for inbox dashboard widgets. Optimize with single query using CASE statements for grouping. | | |
| TASK-016 | Add database indexes for inbox performance: Create index on workflow_steps(approver_user_id, status, due_at) for inbox queries. Create index on workflow_steps(status, due_at) for escalation queries. Create index on workflow_instances(entity_type, entity_id) for entity lookups. Create composite index on (tenant_id, status, created_at) for history queries. Document index strategy in migration comments. Test query performance with 10,000+ workflow_step records. Use EXPLAIN ANALYZE to validate index usage. | | |

### GOAL-004: Workflow Inbox API

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-WF-008, SEC-002, CON-003 | Create RESTful API endpoints for workflow inbox with filtering, sorting, bulk operations, and proper authorization. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-017 | Create `src/Http/Controllers/Api/V1/WorkflowInboxController.php`: Include `declare(strict_types=1);`. Implement methods: `index(InboxRequest $request): JsonResponse` (list pending approvals with filters), `statistics(Request $request): JsonResponse` (inbox stats), `show(int $stepId): JsonResponse` (get workflow detail for approval), `approve(ApproveRequest $request, int $stepId): JsonResponse` (approve step), `reject(RejectRequest $request, int $stepId): JsonResponse` (reject step), `bulkApprove(BulkApproveRequest $request): JsonResponse` (bulk approve), `bulkReassign(BulkReassignRequest $request): JsonResponse` (bulk reassign). Apply auth:sanctum and tenant middleware. Return WorkflowInboxItemResource. | | |
| TASK-018 | Create Form Requests: `InboxRequest.php` (validation: filters optional|array, filters.entity_type optional|string, filters.overdue optional|boolean, filters.priority optional|in:high,medium,low, sort_by optional|in:due_at,assigned_at,priority, sort_direction optional|in:asc,desc, per_page optional|integer|min:1|max:100), `ApproveRequest.php` (validation: comments optional|string|max:1000, attachments optional|array|max:5, attachments.* file|max:10240), `RejectRequest.php` (validation: reason required|string|min:10|max:1000, comments optional|string), `BulkApproveRequest.php` (validation: step_ids required|array|min:1|max:100 per CON-003, comments optional|string), `BulkReassignRequest.php` (validation: step_ids required|array|min:1|max:100, new_approver_id required|exists:users,id). Authorization checks 'approve-workflows' permission. | | |
| TASK-019 | Create `src/Http/Resources/WorkflowInboxItemResource.php`: Transform WorkflowInboxItemDTO to JSON:API format: Return array with keys: id (workflow_step_id), type ('workflow_inbox_item'), attributes (workflow_instance_id, workflow_code, workflow_name, entity_type, entity_id, entity_display_name, current_step_number, current_step_name, status, assigned_at, due_at, is_overdue, overdue_hours, priority, initiated_by, delegation_info, can_approve), relationships (workflow_instance if loaded, entity if loaded), links (self, approve, reject, detail), meta (escalation_level if applicable, next_escalation_in_hours if applicable). Format dates in ISO 8601. Include visual indicators for overdue status. | | |
| TASK-020 | Implement `bulkApprove()` in `WorkflowInboxController`: Accept step_ids array and optional comments. Validate all step_ids belong to current user or user has delegation. Loop through step_ids, call `WorkflowExecutorService::approveStep()` for each. Collect results: {approved: [], failed: []}. Use database transaction for atomicity. If any approval fails, rollback all. Return summary: {total: 10, approved: 8, failed: 2, errors: [...]}. Queue notifications for approved items. Log bulk operation for audit. Limit to 100 items per CON-003. Validate user has permission for each item individually (SEC-004). | | |
| TASK-021 | Implement `bulkReassign()` in `WorkflowInboxController`: Accept step_ids array and new_approver_id. Validate new approver is active user in same tenant with appropriate role. Update workflow_steps.approver_user_id for specified step_ids. Reset due_at (extend deadline by 24 hours for new approver). Create approval_actions with action='reassign', comments='Bulk reassigned'. Queue notifications to old and new approvers. Return summary with count. Use transaction. Validate user has 'reassign-approvals' permission. Log reassignment in escalation_log or approval_actions. | | |

### GOAL-005: Workflow Status Tracking and Testing

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-WF-006, CON-005, PR-WF-002 | Implement real-time workflow status tracking API with caching and create comprehensive tests for escalation and inbox functionality. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-022 | Create `src/Http/Controllers/Api/V1/WorkflowStatusController.php`: Implement methods: `show(int $instanceId): JsonResponse` (get workflow progress), `history(int $instanceId): JsonResponse` (get approval history), `timeline(int $instanceId): JsonResponse` (get timeline visualization data). Apply auth and tenant middleware. Check user has access to workflow (initiated_by or current/past approver or admin). Return WorkflowStatusResource with complete workflow state, current step, completed steps, pending steps, approval history with user actions. Cache response for 60 seconds (CON-005). Support real-time updates via polling or WebSocket (optional). | | |
| TASK-023 | Create `src/Http/Resources/WorkflowStatusResource.php`: Transform workflow_instance to status visualization format: Return array with keys: workflow_instance_id, status, progress_percentage (completed_steps / total_steps * 100), total_steps, completed_steps, current_step, steps (array of step objects with step_number, step_name, status, approver, assigned_at, completed_at, duration), timeline (array for visualization: events ordered chronologically), approval_history (array of all actions with user, action, timestamp, comments). Format for frontend timeline/progress bar components. Include next_action_required (for current user if applicable). | | |
| TASK-024 | Create Feature test `tests/Feature/EscalationTest.php`: Use Pest syntax. Test scenarios: 1) Overdue step escalates to manager (level 1), 2) Still overdue after 24h escalates to director (level 2), 3) Escalation with action='notify' sends notification, 4) Escalation with action='reassign' changes approver, 5) Escalation with action='auto_approve' completes step, 6) CheckWorkflowEscalationsCommand finds and processes overdue steps, 7) Escalation logged in escalation_log table, 8) User receives escalation notification, 9) Maximum 5 escalation levels enforced (CON-001), 10) Escalation for 1000 steps completes within reasonable time. Use Carbon::setTestNow() to manipulate time. Assert database state after escalation. | | |
| TASK-025 | Create Feature test `tests/Feature/WorkflowInboxTest.php`: Test scenarios: 1) User inbox shows pending approvals assigned to user, 2) Inbox includes delegated approvals, 3) Filter inbox by entity_type (expect filtered results), 4) Filter by overdue status (expect only overdue items), 5) Sort by due_at ASC (expect correct order), 6) Inbox statistics correct (total, overdue, by_entity_type), 7) Approve item via inbox (expect step approved, next step assigned), 8) Reject item via inbox (expect workflow rejected), 9) Bulk approve 10 items (expect all approved), 10) Bulk reassign 5 items (expect new approver assigned), 11) Inbox query < 500ms for 1000 items (CON-002), 12) Cross-tenant inbox access blocked. Use factories to create large dataset. Assert API response structure and data correctness. | | |
| TASK-026 | Create Performance test `tests/Feature/WorkflowPerformanceTest.php`: Test scenarios: 1) Inbox query with 1000 pending items returns < 500ms (CON-002), 2) Escalation check for 1000 overdue steps completes within acceptable time (PR-WF-002), 3) Bulk approve 100 items completes within 10 seconds, 4) Workflow status query with caching < 100ms, 5) Concurrent inbox access by 50 users (simulate load). Use database seeding for large datasets. Measure with microtime(). Tag with @group performance. Compare with and without caching. Verify database query count (N+1 prevention). | | |
| TASK-027 | Create Unit test `tests/Unit/EscalationServiceTest.php`: Test `getEscalationRule()` returns correct rule based on overdue hours and level, Test `executeEscalation()` with each action type (notify, reassign, auto_approve, reject), Test escalation level increment, Test maximum 5 levels enforcement, Test escalation log creation, Test error handling in escalation execution. Mock dependencies (repositories, notification service). Assert escalation logic correctness and error recovery. | | |
| TASK-028 | Create Unit test `tests/Unit/WorkflowInboxRepositoryTest.php`: Test `getInboxForUser()` filtering and sorting, Test `getInboxStatistics()` aggregations, Test delegation resolution in inbox query, Test cache behavior (cache hit/miss), Test query performance with large dataset. Mock database responses. Assert DTO creation and data transformation correctness. | | |

## 3. Alternatives

- **ALT-001**: Use dedicated task queue service (AWS SQS, RabbitMQ) for escalation jobs
  - *Pros*: More reliable, better at-least-once delivery, distributed processing
  - *Cons*: Additional infrastructure, increased complexity, cost
  - *Decision*: Deferred - Laravel Queue with Redis sufficient for MVP; can migrate later

- **ALT-002**: Send escalation notifications immediately instead of queuing
  - *Pros*: Simpler logic, no queue delay
  - *Cons*: Escalation command blocks on notification delivery, slower for bulk processing
  - *Decision*: Not chosen - Queued notifications provide better performance and reliability

- **ALT-003**: Store inbox as materialized view instead of query on demand
  - *Pros*: Faster queries, pre-aggregated data
  - *Cons*: Refresh complexity, stale data between refreshes, storage overhead
  - *Decision*: Not chosen - Query with caching provides good balance; materialized view if needed later

- **ALT-004**: Use WebSocket for real-time inbox updates instead of polling
  - *Pros*: True real-time, no polling overhead, better UX
  - *Cons*: Additional infrastructure (WebSocket server), connection management complexity
  - *Decision*: Deferred - HTTP polling with 60s cache sufficient for MVP; WebSocket can be added later

## 4. Dependencies

**Package Dependencies:**
- `azaharizaman/erp-workflow-engine` (PLAN01, PLAN02) - Foundation and conditional routing required
- `azaharizaman/erp-multitenancy` (PRD01-SUB01) - Tenant isolation
- `azaharizaman/erp-authentication` (PRD01-SUB02) - User and role data
- `azaharizaman/erp-notifications` (PRD01-SUB22) - Notification delivery (to be created)

**Internal Dependencies:**
- PLAN01: WorkflowInstance, WorkflowStep, WorkflowExecutorService
- PLAN02: DelegationService, conditional routing
- Notification module for escalation and approval alerts

**Infrastructure Dependencies:**
- Cron daemon for escalation check command (every 15 minutes)
- Queue worker for escalation jobs and notifications
- Redis for caching inbox queries and workflow status

## 5. Files

**Migrations:**
- `packages/workflow-engine/database/migrations/create_escalation_log_table.php` - Escalation log schema
- `packages/workflow-engine/database/migrations/add_inbox_indexes.php` - Performance indexes

**Models:**
- `packages/workflow-engine/src/Models/EscalationLog.php` - Escalation log model (optional, for queries)

**DTOs:**
- `packages/workflow-engine/src/DTOs/WorkflowInboxItemDTO.php` - Inbox item data transfer object

**Contracts:**
- `packages/workflow-engine/src/Contracts/EscalationServiceContract.php` - Escalation service interface
- `packages/workflow-engine/src/Contracts/WorkflowInboxRepositoryContract.php` - Inbox repository interface

**Services:**
- `packages/workflow-engine/src/Services/EscalationService.php` - Escalation logic

**Repositories:**
- `packages/workflow-engine/src/Repositories/WorkflowInboxRepository.php` - Inbox data access

**Controllers:**
- `packages/workflow-engine/src/Http/Controllers/Api/V1/WorkflowInboxController.php` - Inbox API
- `packages/workflow-engine/src/Http/Controllers/Api/V1/WorkflowStatusController.php` - Status tracking API

**Form Requests:**
- `packages/workflow-engine/src/Http/Requests/InboxRequest.php` - Inbox query validation
- `packages/workflow-engine/src/Http/Requests/ApproveRequest.php` - Approve validation
- `packages/workflow-engine/src/Http/Requests/RejectRequest.php` - Reject validation
- `packages/workflow-engine/src/Http/Requests/BulkApproveRequest.php` - Bulk approve validation
- `packages/workflow-engine/src/Http/Requests/BulkReassignRequest.php` - Bulk reassign validation

**API Resources:**
- `packages/workflow-engine/src/Http/Resources/WorkflowInboxItemResource.php` - Inbox item transformation
- `packages/workflow-engine/src/Http/Resources/WorkflowStatusResource.php` - Status visualization transformation

**Commands:**
- `packages/workflow-engine/src/Commands/CheckWorkflowEscalationsCommand.php` - Escalation check command

**Jobs:**
- `packages/workflow-engine/src/Jobs/EscalateWorkflowStepJob.php` - Escalation processing job

**Events:**
- `packages/workflow-engine/src/Events/StepEscalatedEvent.php` - Step escalated event
- `packages/workflow-engine/src/Events/EscalationFailedEvent.php` - Escalation failed event

**Listeners:**
- `packages/workflow-engine/src/Listeners/NotifyEscalationListener.php` - Escalation notification

**Tests:**
- `packages/workflow-engine/tests/Feature/EscalationTest.php` - Escalation tests
- `packages/workflow-engine/tests/Feature/WorkflowInboxTest.php` - Inbox tests
- `packages/workflow-engine/tests/Feature/WorkflowPerformanceTest.php` - Performance tests
- `packages/workflow-engine/tests/Unit/EscalationServiceTest.php` - Escalation unit tests
- `packages/workflow-engine/tests/Unit/WorkflowInboxRepositoryTest.php` - Inbox repository tests

## 6. Testing

- **TEST-001**: Overdue step escalates to manager after deadline_hours
- **TEST-002**: Escalation level 2 triggers after additional deadline_hours
- **TEST-003**: Escalation action='notify' sends notification to escalated_to
- **TEST-004**: Escalation action='reassign' changes approver and resets due_at
- **TEST-005**: Escalation action='auto_approve' completes step automatically
- **TEST-006**: CheckWorkflowEscalationsCommand finds and processes all overdue steps
- **TEST-007**: Maximum 5 escalation levels enforced (CON-001)
- **TEST-008**: Escalation logged in escalation_log table with complete details
- **TEST-009**: User inbox shows all pending approvals (direct and delegated)
- **TEST-010**: Filter inbox by entity_type returns only matching items
- **TEST-011**: Inbox statistics correct: total, overdue, by_entity_type counts
- **TEST-012**: Bulk approve 10 items: all approved, next steps assigned
- **TEST-013**: Bulk reassign 5 items: new approver assigned, notifications sent
- **TEST-014**: Inbox query < 500ms for 1000 items (CON-002)
- **TEST-015**: Workflow status tracking shows progress, current step, history

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Escalation command processing could fall behind with high workflow volume
  - *Mitigation*: Use queued jobs for asynchronous processing, monitor queue depth, scale workers horizontally
- **RISK-002**: Inbox queries could slow down with large datasets (10,000+ pending items)
  - *Mitigation*: Aggressive caching (60s TTL), database indexes, pagination, consider materialized view if needed
- **RISK-003**: Escalation loops: item escalates, reassigns, becomes overdue again
  - *Mitigation*: Track escalation_level in workflow_step, limit to 5 levels, alert on excessive escalations
- **RISK-004**: Bulk operations could timeout or fail partially
  - *Mitigation*: Transaction rollback on failure, limit to 100 items, use queued jobs for large batches

**Assumptions:**
- **ASSUMPTION-001**: Escalation targets (users/roles) are active and have proper permissions
- **ASSUMPTION-002**: Notification system delivers escalation alerts reliably
- **ASSUMPTION-003**: Users check inbox regularly and respond to approvals within reasonable timeframe
- **ASSUMPTION-004**: Workflow volume manageable with 15-minute escalation check interval
- **ASSUMPTION-005**: Inbox with 1000 pending items is reasonable upper bound for single user

## 8. KIV for future implementations

- **KIV-001**: Real-time inbox updates via WebSocket instead of polling
- **KIV-002**: Mobile push notifications for escalations
- **KIV-003**: Slack/Teams integration for approval notifications
- **KIV-004**: AI-powered escalation prediction (predict which items will escalate)
- **KIV-005**: Approval delegation during escalation (auto-delegate if primary approver unavailable)
- **KIV-006**: Escalation analytics dashboard (escalation rate, average time to escalate, etc.)
- **KIV-007**: Custom escalation actions (call external API, trigger custom workflow)
- **KIV-008**: Inbox mobile app with offline approval capability

## 9. Related PRD / Further Reading

- Master PRD: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- Sub-PRD: [../prd/prd-01/PRD01-SUB21-WORKFLOW-ENGINE.md](../prd/prd-01/PRD01-SUB21-WORKFLOW-ENGINE.md)
- Related PLAN: [PRD01-SUB21-PLAN01-implement-workflow-engine-foundation.md](PRD01-SUB21-PLAN01-implement-workflow-engine-foundation.md)
- Related PLAN: [PRD01-SUB21-PLAN02-implement-conditional-routing-delegation.md](PRD01-SUB21-PLAN02-implement-conditional-routing-delegation.md)
- Related Sub-PRD: [../prd/prd-01/PRD01-SUB22-NOTIFICATIONS-EVENTS.md](../prd/prd-01/PRD01-SUB22-NOTIFICATIONS-EVENTS.md) - Notification delivery
- Coding Guidelines: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- Command Pattern: https://refactoring.guru/design-patterns/command
