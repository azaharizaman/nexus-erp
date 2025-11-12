---
plan: Implement Sync Configuration & Field Mapping
version: 1.0
date_created: 2025-01-15
last_updated: 2025-01-15
owner: Development Team
status: Planned
tags: [feature, integration, sync, field-mapping, business-logic]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This plan implements sync configuration management and flexible field mapping with transformation rules for data synchronization between external systems and the ERP. This plan addresses FR-IC-004, FR-IC-005, DR-IC-002, and provides foundation for bi-directional sync.

## 1. Requirements & Constraints

- **FR-IC-004**: Implement field mapping configuration with transformation rules
- **FR-IC-005**: Support scheduled sync and real-time event-driven integration
- **DR-IC-002**: Maintain field mapping configurations with versioning
- **SEC-001**: Sync configurations must respect tenant isolation
- **SEC-002**: Transformation rules must be validated to prevent code injection
- **CON-001**: Sync schedules use Laravel's cron expression format
- **CON-002**: Field mappings support one-to-one, one-to-many, and computed fields
- **CON-003**: Transformation rules use safe PHP functions only (no eval())
- **GUD-001**: Follow repository pattern for all data access operations
- **GUD-002**: Use Laravel Actions for all business operations
- **GUD-003**: All models must use strict type declarations and PHPDoc
- **PAT-001**: Use Strategy pattern for different sync modes (scheduled, event-driven, manual)
- **PAT-002**: Use Chain of Responsibility for transformation pipeline
- **PAT-003**: Use Observer pattern for sync lifecycle events

## 2. Implementation Steps

### GOAL-001: Create Database Schema for Sync Configurations

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-IC-005 | Creates SQL tables for sync configuration with schedule and mode settings | | |
| DR-IC-002 | Creates field mapping table with versioning support | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000002_create_sync_configurations_table.php` with tenant_id, connector_id FK, sync_name, source_entity, target_entity, sync_direction enum (inbound/outbound/bidirectional), sync_mode enum (scheduled/event_driven/manual), schedule_cron nullable, field_mappings JSONB, transformation_rules JSONB, conflict_resolution_strategy enum, is_active, timestamps, indexes on tenant_id/connector_id/mode, FK to connectors with cascade delete | | |
| TASK-002 | Create migration `2025_01_01_000005_create_field_mappings_table.php` with tenant_id, mapping_name, source_entity, target_entity, mappings JSONB (array of field rules), transformation_functions JSONB, version integer, is_active, timestamps, indexes on tenant_id and entities | | |
| TASK-003 | Create SyncConfiguration model in `packages/integration-connectors/src/Models/SyncConfiguration.php` with BelongsToTenant trait, HasActivityLogging trait, relationships (connector, syncHistory), scopes (active, byMode, scheduled), accessor for parsed cron schedule, mutator for validating transformation rules | | |
| TASK-004 | Create FieldMapping model in `packages/integration-connectors/src/Models/FieldMapping.php` with BelongsToTenant trait, versioning support, relationships, methods for applying mappings (applyMapping, validateMapping), transformation rule executor | | |

### GOAL-002: Implement Field Mapping Service

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-IC-004 | Implements flexible field mapping with transformation rules and validation | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-005 | Create MappingServiceContract interface in `packages/integration-connectors/src/Contracts/MappingServiceContract.php` with methods: applyMapping(FieldMapping $mapping, array $sourceData): array, validateMapping(FieldMapping $mapping): bool, applyTransformations(array $data, array $rules): array, previewMapping(FieldMapping $mapping, array $sampleData): array | | |
| TASK-006 | Create MappingService class in `packages/integration-connectors/src/Services/MappingService.php` implementing MappingServiceContract with methods for field mapping (one-to-one, one-to-many, computed), transformation pipeline using Chain of Responsibility, safe transformation execution (whitelist functions: trim, strtoupper, strtolower, number_format, date_format, substr, str_replace), nested field access using dot notation, default value handling, type casting | | |
| TASK-007 | Create transformation rule validators in MappingService: validateTransformationRule() checking for disallowed functions (eval, exec, system, shell_exec, passthru), syntax validation, parameter validation, return type checking | | |
| TASK-008 | Create CreateFieldMappingAction in `packages/integration-connectors/src/Actions/CreateFieldMappingAction.php` with validation for source/target entities, mapping rules validation, transformation rules validation, version increment logic | | |
| TASK-009 | Create ApplyFieldMappingAction in `packages/integration-connectors/src/Actions/ApplyFieldMappingAction.php` using MappingService to transform data, error handling for invalid mappings, logging of transformation errors | | |

### GOAL-003: Implement Sync Configuration Management

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-IC-005 | Implements sync configuration with scheduled and event-driven modes | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-010 | Create SyncConfigurationRepository in `packages/integration-connectors/src/Repositories/SyncConfigurationRepository.php` with methods: findByConnector(Connector $connector), findScheduled(), findEventDriven(), findByEntity(string $entity), create/update/delete operations | | |
| TASK-011 | Create CreateSyncConfigurationAction in `packages/integration-connectors/src/Actions/CreateSyncConfigurationAction.php` with validation for connector existence, entity validation, cron expression validation using Cron/CronExpression library, field mapping association, default conflict resolution strategy | | |
| TASK-012 | Create UpdateSyncConfigurationAction in `packages/integration-connectors/src/Actions/UpdateSyncConfigurationAction.php` with validation, audit logging for configuration changes, notification if sync_mode or schedule changes | | |
| TASK-013 | Create DeleteSyncConfigurationAction in `packages/integration-connectors/src/Actions/DeleteSyncConfigurationAction.php` with validation preventing deletion if active sync schedules exist (BR-IC-003), soft delete support, cleanup of associated resources | | |

### GOAL-004: Implement Scheduled Sync Dispatcher

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-IC-005 | Implements scheduled sync execution using Laravel's scheduler | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-014 | Create ScheduleSyncJobsCommand in `packages/integration-connectors/src/Console/Commands/ScheduleSyncJobsCommand.php` to query active scheduled sync configs, evaluate cron expressions against current time, dispatch ExecuteSyncJob for due syncs, logging of scheduled syncs | | |
| TASK-015 | Register command in IntegrationConnectorsServiceProvider schedule() method to run every minute, register dynamic schedules based on SyncConfiguration cron expressions, handle timezone considerations | | |
| TASK-016 | Create ExecuteSyncJob in `packages/integration-connectors/src/Jobs/ExecuteSyncJob.php` accepting SyncConfiguration, implements ShouldQueue, logic to instantiate connector, call sync operation, create SyncHistory record, handle failures with retry | | |
| TASK-017 | Add job queue configuration in `packages/integration-connectors/config/integration-connectors.php` with queue name (integrations), retry attempts (3), backoff strategy (exponential), timeout (300s) | | |

### GOAL-005: Implement Event-Driven Sync Listeners

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-IC-005 | Implements real-time event-driven sync when ERP entities change | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-018 | Create TriggerEventDrivenSyncListener in `packages/integration-connectors/src/Listeners/TriggerEventDrivenSyncListener.php` using #[Listen] attribute for generic model events (created, updated, deleted), query SyncConfiguration for matching entity and event_driven mode, dispatch ExecuteSyncJob for matching configs | | |
| TASK-019 | Register event listeners in IntegrationConnectorsServiceProvider boot() method, support for wildcard entity matching (e.g., 'App\Models\*' matches all models), configuration for event types to trigger sync (created, updated, deleted) | | |
| TASK-020 | Create SyncTriggerService in `packages/integration-connectors/src/Services/SyncTriggerService.php` with methods: shouldTriggerSync(Model $model, string $event): bool, getSyncConfigurations(Model $model): Collection, triggerSync(SyncConfiguration $config, Model $model): void | | |

## 3. Alternatives

- **ALT-001**: Use visual drag-and-drop field mapping UI - Deferred to future enhancement due to complexity (KIV-004)
- **ALT-002**: Store transformation rules as PHP code files - Rejected due to security risks and code injection vulnerabilities
- **ALT-003**: Use Laravel Scheduler directly without custom command - Rejected as it doesn't support dynamic cron expressions from database
- **ALT-004**: Use database triggers for event-driven sync - Rejected as Laravel events provide better integration and testability

## 4. Dependencies

- **DEP-001**: Laravel Framework ^12.0 (for scheduler, events, queue)
- **DEP-002**: dragonmantank/cron-expression ^3.0 (for cron validation and parsing)
- **DEP-003**: PLAN01 (Connector Framework) - requires Connector model and services
- **DEP-004**: SUB01 Multi-Tenancy (for tenant isolation in sync configs)
- **DEP-005**: SUB03 Audit Logging (for tracking configuration changes)

## 5. Files

**Migrations:**
- `packages/integration-connectors/database/migrations/2025_01_01_000002_create_sync_configurations_table.php`: Sync configuration schema
- `packages/integration-connectors/database/migrations/2025_01_01_000005_create_field_mappings_table.php`: Field mapping schema

**Models:**
- `packages/integration-connectors/src/Models/SyncConfiguration.php`: Sync configuration model
- `packages/integration-connectors/src/Models/FieldMapping.php`: Field mapping model with versioning

**Contracts:**
- `packages/integration-connectors/src/Contracts/MappingServiceContract.php`: Mapping service interface

**Services:**
- `packages/integration-connectors/src/Services/MappingService.php`: Field mapping and transformation logic
- `packages/integration-connectors/src/Services/SyncTriggerService.php`: Sync trigger evaluation

**Repositories:**
- `packages/integration-connectors/src/Repositories/SyncConfigurationRepository.php`: Sync config data access

**Actions:**
- `packages/integration-connectors/src/Actions/CreateFieldMappingAction.php`: Create field mapping
- `packages/integration-connectors/src/Actions/ApplyFieldMappingAction.php`: Apply field transformation
- `packages/integration-connectors/src/Actions/CreateSyncConfigurationAction.php`: Create sync config
- `packages/integration-connectors/src/Actions/UpdateSyncConfigurationAction.php`: Update sync config
- `packages/integration-connectors/src/Actions/DeleteSyncConfigurationAction.php`: Delete sync config

**Jobs:**
- `packages/integration-connectors/src/Jobs/ExecuteSyncJob.php`: Queued sync execution

**Commands:**
- `packages/integration-connectors/src/Console/Commands/ScheduleSyncJobsCommand.php`: Schedule sync dispatcher

**Listeners:**
- `packages/integration-connectors/src/Listeners/TriggerEventDrivenSyncListener.php`: Event-driven sync trigger

**Configuration:**
- `packages/integration-connectors/config/integration-connectors.php`: Job queue configuration

## 6. Testing

- **TEST-001**: Unit test for FieldMapping applyMapping with various transformation rules
- **TEST-002**: Unit test for MappingService transformation pipeline with chained transformations
- **TEST-003**: Unit test for transformation rule validation (whitelist enforcement)
- **TEST-004**: Unit test for cron expression validation in CreateSyncConfigurationAction
- **TEST-005**: Feature test for scheduled sync execution at correct intervals
- **TEST-006**: Feature test for event-driven sync triggered by entity changes
- **TEST-007**: Feature test for field mapping versioning and rollback
- **TEST-008**: Integration test for end-to-end sync with field transformation
- **TEST-009**: Security test verifying code injection prevention in transformation rules
- **TEST-010**: Test that sync configs cannot be deleted with active schedules

## 7. Risks & Assumptions

- **RISK-001**: Complex transformation rules may cause performance issues - Mitigation: Implement timeout for transformations, optimize transformation executor
- **RISK-002**: Cron expression parsing errors may prevent scheduled syncs - Mitigation: Validate cron on config creation, provide default safe expressions
- **RISK-003**: High-frequency events may overwhelm queue - Mitigation: Implement debouncing for event-driven sync, rate limiting
- **RISK-004**: Field mapping versioning may cause confusion - Mitigation: Clear UI showing active version, migration tools for updating mappings
- **ASSUMPTION-001**: Cron expressions use server timezone unless specified
- **ASSUMPTION-002**: Transformation rules are stateless (no side effects)
- **ASSUMPTION-003**: Field mappings are validated before first sync execution
- **ASSUMPTION-004**: Queue workers are running and processing jobs

## 8. KIV for future implementations

- **KIV-001**: Visual drag-and-drop field mapping builder UI
- **KIV-002**: AI-powered transformation rule suggestions based on data patterns
- **KIV-003**: Field mapping templates for common entity types (Customer, Product, Invoice)
- **KIV-004**: Bulk field mapping import/export for easy configuration migration
- **KIV-005**: Real-time field mapping preview with live data samples
- **KIV-006**: Transformation rule debugger with step-by-step execution

## 9. Related PRD / Further Reading

- [PRD01-SUB24: Integration Connectors](../prd/prd-01/PRD01-SUB24-INTEGRATION-CONNECTORS.md)
- [Master PRD](../prd/PRD01-MVP.md)
- [PLAN01: Connector Framework](PRD01-SUB24-PLAN01-implement-connector-framework.md)
- [Laravel Scheduler Documentation](https://laravel.com/docs/12.x/scheduling)
- [Laravel Events Documentation](https://laravel.com/docs/12.x/events)
- [Cron Expression Format](https://crontab.guru/)
