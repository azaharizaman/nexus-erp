# Phase 2: Database-Driven Workflows & User Tasks

**Branch:** `developing-workflow` (continuing from Phase 1)  
**Status:** Planning → Implementation  
**Start Date:** November 14, 2025

---

## Overview

Phase 2 extends the code-based workflow engine (Phase 1) with database-driven workflow definitions, user task management, and multi-approver strategies.

### Key Objectives

1. **Database-Driven Workflows (Level 2)**
   - Store workflow definitions in database
   - Runtime workflow modification without code changes
   - Version control for workflow definitions
   - Import/export workflow definitions (JSON)

2. **User Task Inbox System**
   - Task assignment and routing
   - Task lifecycle management
   - Task inbox/outbox for users
   - Task completion tracking

3. **Multi-Approver Strategies**
   - Sequential approval (one after another)
   - Parallel approval (all must approve)
   - Quorum approval (N of M approvers)
   - Any approval (first approver wins)
   - Weighted approval (based on roles/levels)

4. **Advanced Guards & Conditions**
   - Role-based guards
   - Permission-based guards
   - Time-based guards (business hours, deadlines)
   - Data validation guards
   - External service guards (API calls)

---

## Architecture Design

### Database Schema

#### `workflow_definitions` table
```sql
- id (uuid, PK)
- name (string, unique)
- key (string, unique) -- machine-readable identifier
- description (text, nullable)
- version (integer)
- definition (jsonb) -- stores states, transitions, etc.
- is_active (boolean)
- created_by (uuid, nullable FK)
- created_at, updated_at, deleted_at
```

#### `workflow_instances` table (extends Phase 1)
```sql
- id (uuid, PK)
- workflow_definition_id (uuid, FK)
- subject_type (string) -- polymorphic
- subject_id (uuid) -- polymorphic
- current_state (string)
- data (jsonb) -- workflow context data
- started_at (timestamp)
- completed_at (timestamp, nullable)
- created_at, updated_at
```

#### `workflow_transitions` table (history)
```sql
- id (uuid, PK)
- workflow_instance_id (uuid, FK)
- transition (string)
- from_state (string)
- to_state (string)
- metadata (jsonb)
- performed_by (uuid, nullable FK to users)
- performed_at (timestamp)
- created_at
```

#### `user_tasks` table
```sql
- id (uuid, PK)
- workflow_instance_id (uuid, FK)
- transition (string) -- which transition this task is for
- assigned_to (uuid, FK to users)
- assigned_by (uuid, nullable FK to users)
- title (string)
- description (text, nullable)
- priority (enum: low, medium, high, urgent)
- due_at (timestamp, nullable)
- status (enum: pending, in_progress, completed, cancelled)
- result (jsonb, nullable) -- approval/rejection data
- completed_at (timestamp, nullable)
- completed_by (uuid, nullable FK to users)
- created_at, updated_at
```

#### `approver_groups` table
```sql
- id (uuid, PK)
- workflow_definition_id (uuid, FK)
- transition (string)
- name (string)
- strategy (enum: sequential, parallel, quorum, any, weighted)
- quorum_count (integer, nullable)
- created_at, updated_at
```

#### `approver_group_members` table
```sql
- id (uuid, PK)
- approver_group_id (uuid, FK)
- user_id (uuid, FK)
- sequence (integer, nullable) -- for sequential strategy
- weight (integer, nullable) -- for weighted strategy
- created_at, updated_at
```

---

## Phase 2 Implementation Checkpoints

### Checkpoint 1: Database Schema & Models (2-3 hours)
- [ ] Create migrations for all tables
- [ ] Create Eloquent models
- [ ] Define relationships
- [ ] Add UUID traits and soft deletes where needed
- [ ] Write model tests

### Checkpoint 2: Workflow Definition Service (3-4 hours)
- [ ] Create WorkflowDefinitionService
- [ ] Implement CRUD for workflow definitions
- [ ] Add version control logic
- [ ] Implement JSON import/export
- [ ] Add validation for workflow definitions
- [ ] Write service tests

### Checkpoint 3: User Task Management (3-4 hours)
- [ ] Create UserTaskService
- [ ] Implement task creation and assignment
- [ ] Add task inbox/outbox queries
- [ ] Implement task completion logic
- [ ] Add task reassignment
- [ ] Write task tests

### Checkpoint 4: Multi-Approver Engine (4-5 hours)
- [ ] Create ApproverGroupService
- [ ] Implement approval strategies:
  - [ ] Sequential approval
  - [ ] Parallel approval
  - [ ] Quorum approval
  - [ ] Any approval
  - [ ] Weighted approval
- [ ] Add strategy validation
- [ ] Write strategy tests

### Checkpoint 5: Database-Driven Engine Adapter (3-4 hours)
- [ ] Create DatabaseWorkflowEngine
- [ ] Implement WorkflowEngineContract
- [ ] Add runtime workflow loading from DB
- [ ] Integrate with Phase 1 StateTransitionService
- [ ] Add caching for workflow definitions
- [ ] Write integration tests

### Checkpoint 6: Laravel Integration (2-3 hours)
- [ ] Create HasDatabaseWorkflow trait
- [ ] Add Artisan commands:
  - [ ] `workflow:import` - Import workflow from JSON
  - [ ] `workflow:export` - Export workflow to JSON
  - [ ] `workflow:list` - List all workflows
  - [ ] `workflow:activate` - Activate a workflow version
- [ ] Update WorkflowServiceProvider
- [ ] Add config options

### Checkpoint 7: Edward CLI Demo (2-3 hours)
- [ ] Create workflow management menu
- [ ] Add task inbox viewer
- [ ] Add approval interface
- [ ] Demonstrate multi-approver scenarios
- [ ] Create demo workflows (PO approval, Invoice approval)

### Checkpoint 8: Documentation & Testing (2-3 hours)
- [ ] Update README with Phase 2 features
- [ ] Create Phase 2 usage examples
- [ ] Write comprehensive integration tests
- [ ] Update REQUIREMENTS-V3.md completion status
- [ ] Create PHASE_2_COMPLETE.md

---

## Total Estimated Time: 20-28 hours (3-4 days)

---

## Success Criteria

### Functional Requirements
- [ ] Workflow definitions stored in database
- [ ] Workflows can be imported/exported as JSON
- [ ] Multiple workflow versions supported
- [ ] User tasks created automatically on transitions
- [ ] Task inbox shows pending tasks per user
- [ ] All 5 approval strategies implemented
- [ ] Guards support roles and permissions
- [ ] Phase 1 workflows still work (backward compatibility)

### Technical Requirements
- [ ] All Phase 1 tests still passing
- [ ] 30+ new tests for Phase 2 features
- [ ] Code coverage > 85%
- [ ] Maximum Atomicity maintained
- [ ] ACID compliance for all operations
- [ ] No breaking changes to Phase 1 API

### Integration Requirements
- [ ] Tested in Edward CLI app
- [ ] Real PO approval workflow demonstrated
- [ ] Multi-approver scenarios validated
- [ ] Database queries optimized
- [ ] Caching implemented for performance

---

## Breaking Changes

**None Expected** - Phase 2 extends Phase 1 without breaking existing API.

### Backward Compatibility Strategy
- Code-based workflows (Phase 1) continue to work
- Database workflows are opt-in
- HasWorkflow trait remains unchanged
- New HasDatabaseWorkflow trait for DB workflows

---

## Risk Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Database schema changes break Phase 1 | High | Comprehensive testing, separate tables |
| Performance degradation with DB queries | Medium | Aggressive caching, query optimization |
| Complex approval logic causes bugs | High | Unit tests for each strategy, integration tests |
| Migration conflicts in production | Medium | Reversible migrations, staging testing |

---

## Next Actions

1. **Start Checkpoint 1:** Create database migrations and models
2. **Test incrementally:** Run tests after each checkpoint
3. **Commit frequently:** One commit per checkpoint
4. **Document as we go:** Update this plan with actual progress

---

**Ready to start Phase 2 implementation!** 🚀

Let's begin with Checkpoint 1: Database Schema & Models.
