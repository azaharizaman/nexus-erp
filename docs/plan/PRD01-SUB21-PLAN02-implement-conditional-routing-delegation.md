---
plan: Implement Conditional Routing and Delegation
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, workflow-engine, conditional-routing, delegation, approval-rules, dynamic-routing]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan adds conditional routing and delegation capabilities to the Workflow Engine. It enables workflows to route approvals based on transaction amount, type, or custom business rules, and allows approvers to delegate their approval authority temporarily to other users with time-bound assignments and proper audit trails.

## 1. Requirements & Constraints

### Requirements

- **REQ-FR-WF-003**: Support conditional routing based on transaction amount, type, or custom rules
- **REQ-FR-WF-005**: Support delegation of approval authority with time-bound assignments
- **REQ-BR-WF-001**: Approvals must be executed in sequential order unless parallel routing is enabled
- **REQ-DR-WF-001**: Store workflow definitions with routing rules and conditions
- **REQ-DR-WF-003**: Track approval actions with timestamps, comments, and attachments
- **REQ-IR-WF-001**: Integrate with all transactional modules for approval workflows
- **REQ-PR-WF-001**: Workflow routing decision must complete in < 100 milliseconds
- **REQ-ARCH-WF-001**: Use SQL for workflow definitions with JSON configuration

### Security Constraints

- **SEC-001**: Conditional routing rules must not expose sensitive business logic to unauthorized users
- **SEC-002**: Delegation must require explicit permission and audit trail
- **SEC-003**: Delegated authority must auto-expire after time limit
- **SEC-004**: Routing conditions must be validated to prevent code injection

### Guidelines

- **GUD-001**: All PHP files must include `declare(strict_types=1);`
- **GUD-002**: Use Laravel 12+ conventions for all implementations
- **GUD-003**: Follow PSR-12 coding standards, enforced by Laravel Pint
- **GUD-004**: Use expression evaluator for safe condition evaluation
- **GUD-005**: All delegation actions must be logged comprehensively

### Patterns to Follow

- **PAT-001**: Use Strategy pattern for different routing condition types (amount, type, custom)
- **PAT-002**: Use Chain of Responsibility for condition evaluation pipeline
- **PAT-003**: Use Specification pattern for complex routing rules
- **PAT-004**: Use Proxy pattern for delegated approver access
- **PAT-005**: Use Observer pattern for delegation expiry notifications

### Constraints

- **CON-001**: Routing conditions limited to 10 rules maximum per workflow
- **CON-002**: Condition expressions limited to 500 characters
- **CON-003**: Delegation period limited to 90 days maximum
- **CON-004**: User can have maximum 5 active delegations at once
- **CON-005**: Condition evaluation must complete within 50ms

## 2. Implementation Steps

### GOAL-001: Conditional Routing Foundation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-WF-003, DR-WF-001, ARCH-WF-001 | Implement conditional routing service with support for amount-based, type-based, and custom rule-based routing decisions. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Update `workflow_definitions.steps_config` JSONB structure to include conditions: Each step can have `conditions` array with objects: [{condition_type: 'amount', operator: 'greater_than', value: 10000, target_step: 2}, {condition_type: 'custom', expression: 'entity.priority === "high"', target_step: 3}]. Add `default_step` for when no conditions match. Document JSON schema in migration comments. Create database migration `add_conditions_to_workflow_definitions.php` to add example documentation. | | |
| TASK-002 | Create `src/Contracts/RoutingConditionEvaluatorContract.php` interface: Define methods: `evaluate(array $condition, array $entityData): bool` (evaluates single condition), `evaluateAll(array $conditions, array $entityData): int` (evaluates all conditions, returns target step), `validateCondition(array $condition): bool` (validates condition structure), `getSupportedOperators(): array` (returns list of operators), `getSupportedConditionTypes(): array` (returns list of types). All methods with PHPDoc. | | |
| TASK-003 | Create `src/Services/RoutingConditionEvaluator.php` implementing `RoutingConditionEvaluatorContract`: Inject `ExpressionLanguage` (symfony/expression-language for safe evaluation). Implement `evaluate()`: Switch on condition_type: 'amount' uses operators (greater_than, less_than, equal, between), 'type' uses equals/in operators, 'custom' uses ExpressionLanguage::evaluate(). Extract value from entityData using dot notation (e.g., 'total_amount', 'items.0.quantity'). Compare using specified operator. Return true/false. Throw `InvalidConditionException` if condition malformed. Measure execution time, ensure < 50ms (CON-005). | | |
| TASK-004 | Implement `evaluateAll()` in `RoutingConditionEvaluator`: Accept conditions array and entityData. Iterate through conditions in order. For each condition, call `evaluate()`. If evaluate returns true, return condition's target_step. If no conditions match, return default_step from workflow definition. Log evaluation results for debugging (condition matched, target_step). Support condition priority: conditions evaluated in array order, first match wins. Return integer step number or null if no match and no default. | | |
| TASK-005 | Implement `validateCondition()` in `RoutingConditionEvaluator`: Validate condition structure: condition_type required and in supported types, operator required and in supported operators for that type, value required (type depends on condition_type), target_step required and integer > 0, expression required for custom type and max 500 chars (CON-002). Validate expression syntax via ExpressionLanguage::parse() without execution. Return true if valid, false if invalid. Called during workflow definition creation to validate conditions before saving. | | |
| TASK-006 | Update `ApprovalRoutingService::getNextStep()` to use conditional routing: After determining current_step, check if workflow definition has conditions for current step. If conditions exist, call `RoutingConditionEvaluator::evaluateAll()` with entity_data from workflow_instance. Use returned target_step instead of simple current_step+1. If no conditions or no match, fall back to sequential routing (current_step+1) or parallel logic. Support dynamic branching: workflow can skip steps or jump to different branches based on conditions. Log routing decision for audit. | | |

### GOAL-002: Routing Rule Types Implementation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-WF-003, PR-WF-001 | Implement specific routing rule types: amount-based, type-based, and custom expression-based with performance optimization. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create `src/Services/RoutingRules/AmountBasedRule.php` implementing `RoutingRuleContract`: Implement `evaluate(array $entityData, array $ruleConfig): bool` method. Extract amount from entityData using ruleConfig's field_path (e.g., 'total_amount', 'items_total'). Support operators: greater_than, less_than, equal, between, greater_than_or_equal, less_than_or_equal. Compare extracted amount with ruleConfig's value/values. Handle currency conversion if multiple currencies involved (use base currency). Return true if condition met, false otherwise. Optimize for common case (single comparison < 1ms). | | |
| TASK-008 | Create `src/Services/RoutingRules/TypeBasedRule.php` implementing `RoutingRuleContract`: Implement `evaluate()` method. Extract type field from entityData (e.g., 'document_type', 'category'). Support operators: equals, not_equals, in, not_in. Compare with ruleConfig's value/values array. Case-insensitive comparison for strings. Support nested field access (e.g., 'supplier.category'). Return boolean result. Cache type lookups if referencing database enum values. Execution time < 5ms. | | |
| TASK-009 | Create `src/Services/RoutingRules/CustomExpressionRule.php` implementing `RoutingRuleContract`: Implement `evaluate()` method using symfony/expression-language. Parse ruleConfig's expression string. Provide entityData as variables to expression. Support common functions: sum(), count(), contains(), startsWith(), endsWith(). Restrict access to dangerous functions (eval, exec, etc.) via custom expression language configuration. Set execution timeout 100ms per expression. Catch evaluation errors, log, return false. Support complex logic: (amount > 10000 AND priority === 'high') OR department === 'IT'. | | |
| TASK-010 | Create `src/Contracts/RoutingRuleContract.php` interface: Define method: `evaluate(array $entityData, array $ruleConfig): bool`. All implementing classes follow same signature. Enables Strategy pattern for different rule types. Add method `getName(): string` returning rule type identifier. Add method `getDescription(): string` for UI display. Add method `getConfigSchema(): array` returning JSON schema for rule configuration validation. | | |
| TASK-011 | Update `RoutingConditionEvaluator` to use rule strategy classes: Add method `getRuleInstance(string $conditionType): RoutingRuleContract`. Map condition_type to rule class: 'amount' => AmountBasedRule, 'type' => TypeBasedRule, 'custom' => CustomExpressionRule. In `evaluate()`, get rule instance and delegate to rule's `evaluate()` method. Register rules in service provider. Allow extensibility: additional rule types can be registered. Cache rule instances for performance. | | |
| TASK-012 | Create Form Request `src/Http/Requests/ValidateRoutingConditionsRequest.php`: Validation rules: conditions required|array|max:10 (CON-001), conditions.*.condition_type required|in:amount,type,custom, conditions.*.operator required|string, conditions.*.value required_unless:condition_type,custom, conditions.*.expression required_if:condition_type,custom|max:500 (CON-002), conditions.*.target_step required|integer|min:1. Custom validation: validate expression syntax for custom type, validate operator valid for condition_type, validate value type matches operator (e.g., between requires array of 2 numbers). | | |

### GOAL-003: Delegation System Foundation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-WF-005, DR-WF-003, SEC-002 | Implement delegation system allowing approvers to delegate approval authority to other users with time-bound assignments and audit trails. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-013 | Create migration `database/migrations/create_delegation_rules_table.php`: Define `delegation_rules` table with columns: id (BIGSERIAL), tenant_id (UUID/BIGINT, indexed, NOT NULL), delegator_user_id (BIGINT, foreign key to users.id, the user delegating), delegate_user_id (BIGINT, foreign key to users.id, the user receiving delegation), workflow_definition_id (BIGINT, nullable FK, null means all workflows), entity_type (VARCHAR 100, nullable, null means all entity types), effective_from (TIMESTAMP, NOT NULL), effective_to (TIMESTAMP, NOT NULL), reason (TEXT, nullable), is_active (BOOLEAN, default true), created_by (BIGINT, FK to users.id), created_at, updated_at. Add constraint: effective_to > effective_from. Add constraint: effective_to - effective_from <= 90 days (CON-003). Add unique constraint on (delegator_user_id, delegate_user_id, workflow_definition_id, effective_from) to prevent duplicates. Add index on (delegator_user_id, is_active, effective_from, effective_to) for active delegation queries. | | |
| TASK-014 | Create `src/Models/DelegationRule.php` Eloquent model: Include `declare(strict_types=1);`. Use traits: `BelongsToTenant`, `LogsActivity`. Define fillable: tenant_id, delegator_user_id, delegate_user_id, workflow_definition_id, entity_type, effective_from, effective_to, reason, is_active, created_by. Define casts: effective_from => 'datetime', effective_to => 'datetime', is_active => 'boolean'. Add relationships: belongsTo(Tenant), belongsTo(User, 'delegator_user_id'), belongsTo(User, 'delegate_user_id'), belongsTo(WorkflowDefinition, nullable), belongsTo(User, 'created_by'). Add scopes: scopeActive(), scopeEffectiveNow(), scopeForDelegator($userId), scopeForDelegate($userId). Add methods: `isEffective(): bool` (checks if current time between effective_from and effective_to), `isExpired(): bool`, `getRemainingDays(): int`. | | |
| TASK-015 | Create `src/Contracts/DelegationServiceContract.php` interface: Define methods: `createDelegation(int $delegatorId, int $delegateId, array $options): DelegationRule` (creates delegation), `revokeDelegation(int $delegationId): bool` (deactivates delegation), `getActiveDelegationsFor(int $userId): Collection` (returns active delegations where user is delegator), `getDelegatedTo(int $userId): Collection` (returns delegations where user is delegate), `canDelegate(User $delegator, User $delegate): bool` (validates delegation allowed), `resolveDelegation(User $user, WorkflowInstance $instance): ?User` (returns delegate if active delegation exists). All methods with PHPDoc. | | |
| TASK-016 | Create `src/Services/DelegationService.php` implementing `DelegationServiceContract`: Inject `DelegationRuleRepository`, `UserRepository`, `PermissionChecker`. Implement `createDelegation()`: 1) Validate delegator has 'delegate-approval' permission, 2) Validate delegate is active user in same tenant, 3) Validate delegator doesn't have 5+ active delegations (CON-004), 4) Validate effective_from <= effective_to and period <= 90 days (CON-003), 5) Create DelegationRule record, 6) Dispatch `DelegationCreatedEvent`, 7) Queue notification to delegate, 8) Return delegation rule. Use database transaction. | | |
| TASK-017 | Implement `resolveDelegation()` in `DelegationService`: Accept user and workflow_instance. Query DelegationRule where delegator_user_id=$user->id, is_active=true, effective_from <= now() <= effective_to. If workflow_definition_id specified in delegation, match instance's workflow_definition_id. If entity_type specified, match instance's entity_type. If match found, return delegate_user. Otherwise return null (no active delegation). Cache result for 5 minutes with key 'delegation:{user_id}:{workflow_id}'. Called before approval action to check if delegate should act instead. | | |
| TASK-018 | Update `ApprovalRoutingService::canUserApprove()` to check delegations: Before checking if user is assigned approver, call `DelegationService::resolveDelegation()` for assigned approver. If delegation exists and current user is the delegate, allow approval. Update approval_action to record original approver (delegator) and actual performer (delegate). Add fields to approval_actions: delegated_from_user_id (BIGINT nullable FK), is_delegated (BOOLEAN default false). Delegation transparent to workflow logic: workflow sees delegation but processes normally. | | |

### GOAL-004: Delegation Management API

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-WF-005, SEC-002, SEC-003 | Create API endpoints for delegation management with proper authorization and automatic expiry handling. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-019 | Create `src/Http/Controllers/Api/V1/DelegationController.php`: Include `declare(strict_types=1);`. Implement methods: `index(Request $request): JsonResponse` (list user's delegations with filter for delegator/delegate), `store(CreateDelegationRequest $request): JsonResponse` (create delegation), `show(int $id): JsonResponse` (get delegation details), `revoke(int $id): JsonResponse` (revoke delegation), `myDelegations(Request $request): JsonResponse` (delegations where I'm delegator), `delegatedToMe(Request $request): JsonResponse` (delegations where I'm delegate). Apply auth:sanctum and tenant middleware. Check 'delegate-approval' permission for store. Return DelegationResource. | | |
| TASK-020 | Create Form Request `src/Http/Requests/CreateDelegationRequest.php`: Validation rules: delegate_user_id required|exists:users,id|different:auth.user.id (can't delegate to self), workflow_definition_id nullable|exists:workflow_definitions,id, entity_type nullable|string|max:100, effective_from required|date|after_or_equal:now, effective_to required|date|after:effective_from, reason nullable|string|max:1000. Custom validation: validate effective_to - effective_from <= 90 days (CON-003), validate delegate is in same tenant, validate delegate is active user, validate user doesn't have 5 active delegations (CON-004). Authorization checks 'delegate-approval' permission. | | |
| TASK-021 | Create `src/Http/Resources/DelegationResource.php`: Transform DelegationRule model to JSON:API format: Return array with keys: id, type ('delegation'), attributes (delegator with user name/email, delegate with user name/email, workflow_definition with code/name if specified, entity_type, effective_from, effective_to, remaining_days, is_active, is_expired, reason, created_at), relationships (delegator user if loaded, delegate user if loaded, workflow_definition if loaded), links (self, revoke), meta (can_revoke boolean based on current user). Format dates in ISO 8601. Include status indicator: 'active', 'expired', 'revoked'. | | |
| TASK-022 | Create `src/Commands/ExpireDelegationsCommand.php`: Artisan command `delegations:expire` with signature. Query DelegationRule where is_active=true AND effective_to < now(). For each expired delegation, set is_active=false, dispatch `DelegationExpiredEvent`, queue notification to delegator. Log expiration activity. Schedule command to run hourly in Kernel.php: `$schedule->command('delegations:expire')->hourly()`. Add --force flag to manually expire specific delegation by ID. Return count of expired delegations. | | |
| TASK-023 | Create `src/Events/DelegationCreatedEvent.php` and `DelegationExpiredEvent.php` implementing ShouldQueue: DelegationCreatedEvent properties: delegation_id, delegator_id, delegate_id, effective_from, effective_to. DelegationExpiredEvent properties: delegation_id, delegator_id, delegate_id, expired_at. Queue on 'workflows' queue. Used by notification listeners to send alerts. Include delegation details in event for listener access. | | |

### GOAL-005: Testing and Integration

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-WF-003, FR-WF-005, PR-WF-001, CON-005 | Create comprehensive tests for conditional routing and delegation, validate performance requirements. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-024 | Create Feature test `tests/Feature/ConditionalRoutingTest.php`: Use Pest syntax. Test scenarios: 1) Amount-based routing (PO > $10k goes to CFO, <= $10k goes to Manager), 2) Type-based routing (expense type determines approver), 3) Custom expression routing (complex logic with AND/OR), 4) No condition match defaults to sequential routing, 5) Multiple conditions evaluated in order (first match wins), 6) Invalid condition expression (expect validation error), 7) Routing decision completes < 100ms (PR-WF-001), 8) Condition evaluation < 50ms (CON-005). Use factories to create workflows with conditions. Assert correct step assignment based on entity data. | | |
| TASK-025 | Create Feature test `tests/Feature/DelegationTest.php`: Test scenarios: 1) Create delegation with valid parameters (expect delegation created, notification sent), 2) Delegate approves on behalf of delegator (expect approval recorded with delegation info), 3) Delegation expires automatically after effective_to (expect is_active=false), 4) Revoke active delegation (expect is_active=false), 5) Attempt to create 6th delegation (expect validation error per CON-004), 6) Attempt to delegate beyond 90 days (expect validation error per CON-003), 7) Delegate access revoked after delegation expires, 8) Delegation specific to workflow_definition only applies to that workflow, 9) Cross-tenant delegation blocked. Assert database state and delegation resolution logic. | | |
| TASK-026 | Create Unit test `tests/Unit/RoutingConditionEvaluatorTest.php`: Test `evaluate()` with amount conditions (greater_than, less_than, equal, between), Test `evaluate()` with type conditions (equals, in, not_in), Test `evaluate()` with custom expressions (simple and complex), Test `validateCondition()` with valid and invalid structures, Test condition evaluation performance (< 50ms per CON-005), Test expression safety (dangerous functions blocked), Test nested field access in entity data, Test null value handling in conditions. Mock dependencies. Assert boolean results and validation outcomes. | | |
| TASK-027 | Create Unit test `tests/Unit/DelegationServiceTest.php`: Test `resolveDelegation()` with active delegation (expect delegate returned), Test `resolveDelegation()` with expired delegation (expect null), Test `resolveDelegation()` with workflow-specific delegation (match and non-match cases), Test `canDelegate()` validation logic, Test delegation creation validation (5 active limit, 90 day limit), Test delegation caching behavior. Mock repositories and user data. Assert delegation resolution correctness. | | |
| TASK-028 | Update existing `WorkflowExecutionTest` to include delegation scenarios: Test approve action by delegate (expect approval recorded with delegated_from_user_id), Test workflow completion via delegation, Test delegation resolution in routing logic, Test approval history shows delegated actions clearly. Ensure backward compatibility with non-delegated workflows. | | |
| TASK-029 | Create integration test `tests/Feature/WorkflowIntegrationTest.php`: Test end-to-end workflow with conditional routing and delegation: Create workflow definition with amount-based conditions, Start workflow for high-value PO (expect routed to CFO step), Delegator delegates to another user, Delegate approves on delegator's behalf, Workflow completes successfully. Assert complete flow from start to approval with all database records (workflow_instance, workflow_steps, approval_actions, delegation_rules) correct. Test realistic business scenario. | | |

## 3. Alternatives

- **ALT-001**: Use business rules engine (Drools, Easy Rules) for conditional routing
  - *Pros*: More powerful, support complex rules, externalized business logic
  - *Cons*: Additional dependency, Java-based (Drools), overkill for simple conditions
  - *Decision*: Not chosen - symfony/expression-language sufficient for ERP approval conditions

- **ALT-002**: Store delegation as workflow step reassignment instead of separate table
  - *Pros*: Simpler schema, delegation part of workflow state
  - *Cons*: Can't pre-define delegations, hard to track delegation history, no time-bound control
  - *Decision*: Not chosen - Separate delegation_rules table provides better control and audit trail

- **ALT-003**: Use Redis for delegation caching instead of database
  - *Pros*: Faster lookups, reduced database load
  - *Cons*: Cache invalidation complexity, potential inconsistency
  - *Decision*: Partial adoption - Database as source of truth, Redis for caching with short TTL

- **ALT-004**: Support permanent delegation instead of time-bound only
  - *Pros*: Simpler for permanent role changes
  - *Cons*: Security risk, users forget to revoke, requires cleanup
  - *Decision*: Not chosen - Time-bound with maximum 90 days enforces review and prevents forgotten delegations

## 4. Dependencies

**Package Dependencies:**
- `azaharizaman/erp-workflow-engine` (PLAN01) - Foundation layer required
- `azaharizaman/erp-multitenancy` (PRD01-SUB01) - Tenant isolation
- `azaharizaman/erp-authentication` (PRD01-SUB02) - User and role data
- `symfony/expression-language` - Safe expression evaluation for custom routing rules

**Internal Dependencies:**
- PLAN01 foundation (WorkflowDefinition, WorkflowInstance, ApprovalRoutingService)
- User and Role repositories from authentication module
- Permission system for delegation authorization

**Infrastructure Dependencies:**
- Cron daemon for delegation expiry command (hourly execution)
- Queue worker for delegation notifications

## 5. Files

**Migrations:**
- `packages/workflow-engine/database/migrations/add_conditions_to_workflow_definitions.php` - Add conditions documentation
- `packages/workflow-engine/database/migrations/create_delegation_rules_table.php` - Delegation rules schema
- `packages/workflow-engine/database/migrations/add_delegation_to_approval_actions.php` - Add delegated_from_user_id, is_delegated columns

**Models:**
- `packages/workflow-engine/src/Models/DelegationRule.php` - Delegation rule model

**Contracts:**
- `packages/workflow-engine/src/Contracts/RoutingConditionEvaluatorContract.php` - Condition evaluator interface
- `packages/workflow-engine/src/Contracts/RoutingRuleContract.php` - Routing rule strategy interface
- `packages/workflow-engine/src/Contracts/DelegationServiceContract.php` - Delegation service interface

**Services:**
- `packages/workflow-engine/src/Services/RoutingConditionEvaluator.php` - Condition evaluation logic
- `packages/workflow-engine/src/Services/RoutingRules/AmountBasedRule.php` - Amount-based routing
- `packages/workflow-engine/src/Services/RoutingRules/TypeBasedRule.php` - Type-based routing
- `packages/workflow-engine/src/Services/RoutingRules/CustomExpressionRule.php` - Custom expression routing
- `packages/workflow-engine/src/Services/DelegationService.php` - Delegation management

**Controllers:**
- `packages/workflow-engine/src/Http/Controllers/Api/V1/DelegationController.php` - Delegation API

**Form Requests:**
- `packages/workflow-engine/src/Http/Requests/ValidateRoutingConditionsRequest.php` - Condition validation
- `packages/workflow-engine/src/Http/Requests/CreateDelegationRequest.php` - Create delegation validation

**API Resources:**
- `packages/workflow-engine/src/Http/Resources/DelegationResource.php` - Delegation transformation

**Events:**
- `packages/workflow-engine/src/Events/DelegationCreatedEvent.php` - Delegation created event
- `packages/workflow-engine/src/Events/DelegationExpiredEvent.php` - Delegation expired event

**Commands:**
- `packages/workflow-engine/src/Commands/ExpireDelegationsCommand.php` - Expire delegations

**Tests:**
- `packages/workflow-engine/tests/Feature/ConditionalRoutingTest.php` - Conditional routing tests
- `packages/workflow-engine/tests/Feature/DelegationTest.php` - Delegation tests
- `packages/workflow-engine/tests/Unit/RoutingConditionEvaluatorTest.php` - Evaluator unit tests
- `packages/workflow-engine/tests/Unit/DelegationServiceTest.php` - Delegation unit tests
- `packages/workflow-engine/tests/Feature/WorkflowIntegrationTest.php` - Integration tests

## 6. Testing

- **TEST-001**: Amount-based routing: PO > $10k routed to CFO, <= $10k to Manager
- **TEST-002**: Type-based routing: expense type 'travel' routed to Travel Manager
- **TEST-003**: Custom expression routing: (amount > $5k AND department === 'IT') routed to IT Director
- **TEST-004**: Multiple conditions: First matching condition wins, others ignored
- **TEST-005**: No condition match: Defaults to sequential routing (current_step+1)
- **TEST-006**: Routing decision completes < 100ms (PR-WF-001)
- **TEST-007**: Condition evaluation completes < 50ms (CON-005)
- **TEST-008**: Create delegation: Delegation created, notification sent to delegate
- **TEST-009**: Delegate approves: Approval recorded with delegated_from_user_id set
- **TEST-010**: Delegation expires: Auto-deactivated after effective_to, delegate access revoked
- **TEST-011**: Attempt 6th delegation: Validation error (max 5 active per CON-004)
- **TEST-012**: Delegation > 90 days: Validation error (max 90 days per CON-003)
- **TEST-013**: Integration: End-to-end workflow with conditional routing and delegation succeeds

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Complex custom expressions could cause performance issues or security vulnerabilities
  - *Mitigation*: Use symfony/expression-language with restricted functions, set execution timeout, validate expressions before saving
- **RISK-002**: Delegation abuse: Users creating unnecessary delegations or circular delegations
  - *Mitigation*: Enforce 5 active delegation limit, require 'delegate-approval' permission, audit all delegations, prevent self-delegation
- **RISK-003**: Condition evaluation errors could block workflow progress
  - *Mitigation*: Catch evaluation errors, log, fall back to default routing, alert administrators
- **RISK-004**: Forgotten delegations after employee leaves organization
  - *Mitigation*: Auto-expire after 90 days maximum, periodic review reports, disable delegations when user deactivated

**Assumptions:**
- **ASSUMPTION-001**: Business rules for conditional routing are relatively stable and don't change frequently
- **ASSUMPTION-002**: Users understand delegation implications and use responsibly
- **ASSUMPTION-003**: Entity data available for condition evaluation contains necessary fields
- **ASSUMPTION-004**: symfony/expression-language provides sufficient power for business logic expressions
- **ASSUMPTION-005**: Delegation periods rarely exceed 90 days (typical absences, projects)

## 8. KIV for future implementations

- **KIV-001**: Visual workflow designer with drag-and-drop condition builder (currently JSON configuration)
- **KIV-002**: Machine learning-based routing suggestions based on historical approval patterns
- **KIV-003**: Support for delegation chains (delegate to delegate to delegate)
- **KIV-004**: Bulk delegation management (delegate all workflows to user for date range)
- **KIV-005**: Delegation approval workflow (require approval before delegation takes effect)
- **KIV-006**: Conditional delegation (only delegate if certain conditions met)
- **KIV-007**: Delegation analytics dashboard (delegation frequency, duration, outcomes)
- **KIV-008**: Support for partial delegation (delegate specific entity types or amounts only)

## 9. Related PRD / Further Reading

- Master PRD: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- Sub-PRD: [../prd/prd-01/PRD01-SUB21-WORKFLOW-ENGINE.md](../prd/prd-01/PRD01-SUB21-WORKFLOW-ENGINE.md)
- Related PLAN: [PRD01-SUB21-PLAN01-implement-workflow-engine-foundation.md](PRD01-SUB21-PLAN01-implement-workflow-engine-foundation.md)
- Coding Guidelines: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- Symfony Expression Language: https://symfony.com/doc/current/components/expression_language.html
- Strategy Pattern: https://refactoring.guru/design-patterns/strategy
