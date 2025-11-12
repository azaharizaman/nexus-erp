---
plan: Master Data Entity Foundation & Versioning
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, master-data, mdm, data-management, entity-management, versioning, audit]
---

# PRD01-SUB18-PLAN01: Implement Master Data Entity Foundation

![Status: Planned](https://img.shields.io/badge/Status-Planned-blue)

This implementation plan establishes the foundational master data repository for managing critical business entities (customers, vendors, items, employees) with versioning, change history tracking, and multi-tenant isolation. This plan provides the core infrastructure upon which data quality, duplicate detection, and stewardship workflows will be built.

## 1. Requirements & Constraints

### Functional Requirements
- **FR-MDM-001**: Provide centralized master data repository for customers, vendors, items, and employees
- **FR-MDM-003**: Implement master data versioning with effective-dated changes

### Business Rules
- **BR-MDM-001**: Master data cannot be deleted if referenced by transactional data

### Data Requirements
- **DR-MDM-001**: Store complete change history for all master data with before/after snapshots

### Integration Requirements
- **IR-MDM-001**: Integrate with all transactional modules as master data provider

### Security Requirements
- **SR-MDM-001**: Implement role-based access to master data by entity type and sensitivity
- **SR-MDM-002**: Encrypt sensitive master data (customer PII, vendor bank details)

### Performance Requirements
- **PR-MDM-001**: Master data queries must complete in < 100ms for single record

### Scalability Requirements
- **SCR-MDM-001**: Support 10 million+ master records per tenant

### Architecture Requirements
- **ARCH-MDM-001**: Use PostgreSQL Materialized Views or ClickHouse for analytics offload

### Event Requirements
- **EV-MDM-001**: Emit MasterDataCreatedEvent when new master record is created
- **EV-MDM-002**: Emit MasterDataUpdatedEvent when master record is modified

### Constraints
- **CON-001**: Depends on SUB01 (Multi-Tenancy) for tenant isolation
- **CON-002**: Depends on SUB02 (Authentication) for user access control
- **CON-003**: Depends on SUB03 (Audit Logging) for activity tracking
- **CON-004**: Must support PostgreSQL 14+ for JSONB and CDC features
- **CON-005**: Must use Redis for caching (PR-MDM-001 performance)

### Guidelines
- **GUD-001**: Follow repository pattern for all data access
- **GUD-002**: Use Laravel Actions for all business logic
- **GUD-003**: Use observer pattern for automatic change tracking
- **GUD-004**: Encrypt sensitive attributes at application level before storage
- **GUD-005**: Version all master data changes with effective dates
- **GUD-006**: Soft delete entities to preserve referential integrity

### Patterns
- **PAT-001**: Repository pattern with contracts for data access
- **PAT-002**: Observer pattern for automatic change history
- **PAT-003**: Strategy pattern for entity type-specific behavior
- **PAT-004**: Factory pattern for entity creation with attributes
- **PAT-005**: Decorator pattern for encryption/decryption

## 2. Implementation Steps

### GOAL-001: Master Data Entity Model & Repository

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MDM-001 | Centralized repository foundation | | |
| FR-MDM-003 | Entity versioning with effective dates | | |
| SCR-MDM-001 | Scalable entity storage for 10M+ records | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000001_create_mdm_entities_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK tenants), entity_type (VARCHAR 50: customer/vendor/item/employee), entity_code (VARCHAR 100 unique per tenant+type), entity_name (VARCHAR 255), is_golden_record (BOOLEAN default false), parent_entity_id (BIGINT FK mdm_entities nullable for duplicates), data_quality_score (DECIMAL 5,2 default 0), completeness_score (DECIMAL 5,2 default 0), accuracy_score (DECIMAL 5,2 default 0), timeliness_score (DECIMAL 5,2 default 0), status (VARCHAR 20: draft/pending_approval/approved/merged/deprecated), effective_from (DATE nullable), effective_to (DATE nullable), is_active (BOOLEAN default true), timestamps, soft deletes; indexes: tenant_id, entity_type, is_golden_record, status, data_quality_score, (tenant_id + entity_type + entity_code) unique; FK cascade on tenant deletion | | |
| TASK-002 | Create enum `EntityType` with values: CUSTOMER, VENDOR, ITEM, EMPLOYEE; label() method returning human-readable names | | |
| TASK-003 | Create enum `EntityStatus` with values: DRAFT, PENDING_APPROVAL, APPROVED, MERGED, DEPRECATED; label() method; isEditable() method returning true for DRAFT/PENDING_APPROVAL only | | |
| TASK-004 | Create model `MdmEntity.php` with traits: BelongsToTenant, SoftDeletes, HasActivityLogging, IsSearchable; fillable: entity_type, entity_code, entity_name, is_golden_record, parent_entity_id, status, effective_from, effective_to, is_active; casts: entity_type → EntityType enum, status → EntityStatus enum, is_golden_record → boolean, is_active → boolean, effective_from → date, effective_to → date, data_quality_score → float, completeness_score → float, accuracy_score → float, timeliness_score → float; relationships: tenant (belongsTo), parent (belongsTo MdmEntity), children (hasMany MdmEntity as parent_entity_id), attributes (hasMany MdmAttribute), changeHistory (hasMany MdmChangeHistory), sourceMappings (hasMany MdmSourceMapping); scopes: active(), golden(), byType(EntityType $type), byStatus(EntityStatus $status), effectiveAt(Carbon $date), withQualityScore(float $min); computed: is_current (effective_from <= today && (effective_to null OR effective_to >= today)), has_future_version (children with effective_from > today), is_referenced_by_transactions (check via CannotDeleteIfReferencedTrait); accessors: qualityGrade() returning A/B/C/D based on score | | |
| TASK-005 | Create trait `CannotDeleteIfReferencedTrait` for MdmEntity with method: checkReferences(): array returning entity types and counts that reference this entity; isReferencedByTransactions(): bool; preventDeletionIfReferenced() boot method; implement BR-MDM-001 by checking customers, vendors, items in sales_orders, purchase_orders, inventory_movements, etc. | | |
| TASK-006 | Create contract `MdmEntityRepositoryContract.php` with methods: findById(int $id): ?MdmEntity, findByCode(string $type, string $code): ?MdmEntity, create(array $data): MdmEntity, update(MdmEntity $entity, array $data): MdmEntity, softDelete(MdmEntity $entity): bool, restore(int $id): ?MdmEntity, paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator, getGoldenRecords(string $entityType): Collection, getEntitiesByType(string $entityType, array $filters = []): Collection, getEffectiveEntity(string $type, string $code, Carbon $date): ?MdmEntity, getVersionHistory(MdmEntity $entity): Collection, search(string $query, array $filters = []): Collection | | |
| TASK-007 | Implement `MdmEntityRepository.php` with eager loading: attributes, tenant; implement filters: entity_type, status, is_golden_record, effective_date range, quality_score range, search (entity_code or entity_name); cache single entity lookups with 5-minute TTL using pattern "mdm:entity:{type}:{code}"; implement query optimization: use indexes, LIMIT results, avoid N+1 with eager loading; ensure queries complete < 100ms per PR-MDM-001 | | |
| TASK-008 | Create factory `MdmEntityFactory.php` with states: draft(), approved(), golden(), forType(EntityType $type), withQualityScore(float $score), effectiveFrom(Carbon $date), effectiveTo(Carbon $date), customer(), vendor(), item(), employee() | | |

### GOAL-002: Master Data Attributes & Encryption

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MDM-001 | Flexible attribute storage for various entity types | | |
| SR-MDM-002 | Encrypt sensitive master data | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-009 | Create migration `2025_01_01_000002_create_mdm_attributes_table.php` with columns: id (BIGSERIAL), entity_id (BIGINT FK mdm_entities cascade), attribute_name (VARCHAR 100), attribute_value (TEXT nullable), attribute_type (VARCHAR 50: string/number/date/boolean/json), source_system (VARCHAR 100 nullable), confidence_score (DECIMAL 5,2 default 100), is_encrypted (BOOLEAN default false), timestamps; indexes: entity_id, attribute_name, (entity_id + attribute_name) for fast lookup | | |
| TASK-010 | Create enum `AttributeType` with values: STRING, NUMBER, DATE, BOOLEAN, JSON; castValue(mixed $value): mixed method for type coercion; validateValue(mixed $value): bool method | | |
| TASK-011 | Create model `MdmAttribute.php` with fillable: entity_id, attribute_name, attribute_value, attribute_type, source_system, confidence_score, is_encrypted; casts: attribute_type → AttributeType enum, confidence_score → float, is_encrypted → boolean; relationships: entity (belongsTo MdmEntity); accessors: decryptedValue() (decrypt if is_encrypted true), typedValue() (cast to proper type); mutators: setAttributeValueAttribute(mixed $value) encrypting if attribute_name in config('mdm.encrypted_attributes') | | |
| TASK-012 | Create service `AttributeEncryptionService.php` with methods: shouldEncrypt(string $entityType, string $attributeName): bool (check against config), encrypt(string $value): string (use Laravel Crypt), decrypt(string $value): string, bulkEncrypt(array $attributes): array, bulkDecrypt(array $attributes): array; use for customer email/phone, vendor bank_account, employee ssn/tax_id | | |
| TASK-013 | Create config file `config/mdm.php` with: encrypted_attributes array per entity type (customer: email/phone/address, vendor: bank_account/tax_id, employee: ssn/salary/tax_id, item: cost_price), default_entity_codes prefixes (CUST/VEND/ITEM/EMP), quality_thresholds (A >= 90, B >= 80, C >= 70, D < 70), versioning settings (auto_version: true, version_on_fields: [entity_name, status]), cache_ttl: 300 seconds | | |
| TASK-014 | Create action `CreateMasterDataAttributeAction.php` using AsAction; validate attribute_name and attribute_type; check if should encrypt; encrypt value if needed; set is_encrypted flag; create MdmAttribute; update parent entity's completeness_score; log activity "Attribute {name} added to {entity}"; return MdmAttribute | | |
| TASK-015 | Create action `UpdateMasterDataAttributeAction.php`; validate changes; handle encryption if attribute now requires it; update confidence_score if from different source; update parent entity completeness_score; log activity; return MdmAttribute | | |
| TASK-016 | Create action `BulkCreateAttributesAction.php` for creating multiple attributes efficiently; use DB transaction; batch insert where possible; update entity completeness_score once at end; return Collection of MdmAttribute | | |

### GOAL-003: Master Data Change History & Versioning

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MDM-003 | Master data versioning with effective dates | | |
| DR-MDM-001 | Complete change history with before/after snapshots | | |
| EV-MDM-002 | MasterDataUpdatedEvent dispatching | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-017 | Create migration `2025_01_01_000003_create_mdm_change_history_table.php` with columns: id (BIGSERIAL), entity_id (BIGINT FK mdm_entities cascade), change_type (VARCHAR 20: created/updated/merged/deprecated), before_snapshot (JSONB nullable), after_snapshot (JSONB nullable), changed_fields (JSONB nullable array of field names), changed_by (BIGINT FK users), change_reason (TEXT nullable), created_at (TIMESTAMP); indexes: entity_id, changed_by, created_at, change_type; supports audit compliance | | |
| TASK-018 | Create enum `ChangeType` with values: CREATED, UPDATED, MERGED, DEPRECATED, RESTORED; label() method; requiresReason() method returning true for MERGED/DEPRECATED | | |
| TASK-019 | Create model `MdmChangeHistory.php` with fillable: entity_id, change_type, before_snapshot, after_snapshot, changed_fields, changed_by, change_reason; casts: change_type → ChangeType enum, before_snapshot → array, after_snapshot → array, changed_fields → array; relationships: entity (belongsTo MdmEntity), user (belongsTo User as changed_by); scopes: byEntity(int $entityId), byUser(int $userId), byDateRange(Carbon $from, Carbon $to), byChangeType(ChangeType $type); computed: has_significant_changes (changed_fields not empty), change_summary (human-readable summary of changes) | | |
| TASK-020 | Create service `MdmVersioningService.php` with methods: shouldCreateVersion(MdmEntity $entity, array $changes): bool (based on config versioning.version_on_fields), createVersion(MdmEntity $entity, array $newData, ?Carbon $effectiveFrom = null): MdmEntity (create new entity with parent_entity_id set, set effective_from/effective_to), getVersionHistory(MdmEntity $entity): Collection, getCurrentVersion(string $entityType, string $entityCode): ?MdmEntity, getVersionAt(string $entityType, string $entityCode, Carbon $date): ?MdmEntity, mergeVersions(MdmEntity $oldVersion, MdmEntity $newVersion): MdmEntity | | |
| TASK-021 | Create observer `MdmEntityObserver.php` with methods: creating(MdmEntity $entity) generating entity_code if not provided using prefix from config; created(MdmEntity $entity) creating MdmChangeHistory with change_type CREATED; updating(MdmEntity $entity) storing original attributes; updated(MdmEntity $entity) creating MdmChangeHistory with before_snapshot, after_snapshot, changed_fields, dispatching MasterDataUpdatedEvent (EV-MDM-002); deleting(MdmEntity $entity) checking CannotDeleteIfReferencedTrait, throwing ReferencedEntityException if referenced per BR-MDM-001; deleted(MdmEntity $entity) creating MdmChangeHistory with change_type DEPRECATED | | |
| TASK-022 | Create action `CreateMasterDataVersionAction.php` using AsAction; validate effective_from in future or today; check if current version exists; set current version effective_to to day before new effective_from; create new entity with parent_entity_id pointing to current; copy attributes; log activity "New version created effective {date}"; dispatch MasterDataCreatedEvent (EV-MDM-001); return new MdmEntity | | |
| TASK-023 | Create event `MasterDataCreatedEvent` with properties: MdmEntity $entity, Collection $attributes, User $createdBy; broadcastable on tenant-specific channel | | |
| TASK-024 | Create event `MasterDataUpdatedEvent` with properties: MdmEntity $entity, array $changedFields, array $beforeSnapshot, array $afterSnapshot, User $updatedBy; broadcastable on tenant-specific channel | | |

### GOAL-004: Master Data CRUD Actions & Business Logic

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MDM-001 | Complete entity management operations | | |
| BR-MDM-001 | Prevent deletion if referenced | | |
| EV-MDM-001, EV-MDM-002 | Event dispatching | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-025 | Create action `CreateMasterDataAction.php` using AsAction; inject MdmEntityRepositoryContract, AttributeEncryptionService; validate entity_type, entity_code uniqueness, entity_name; auto-generate entity_code if not provided; validate attributes array structure; encrypt sensitive attributes; create MdmEntity with status DRAFT; create MdmAttribute records; calculate initial completeness_score; log activity "Master data {type} {code} created"; dispatch MasterDataCreatedEvent (EV-MDM-001); return MdmEntity with attributes relationship loaded | | |
| TASK-026 | Create action `UpdateMasterDataAction.php`; check entity.status.isEditable(); validate changes; handle versioning if significant fields changed; encrypt new/changed sensitive attributes; update MdmEntity; update MdmAttribute records; recalculate completeness_score; MdmEntityObserver handles change history and MasterDataUpdatedEvent (EV-MDM-002); return updated MdmEntity | | |
| TASK-027 | Create action `DeleteMasterDataAction.php`; check entity is not referenced (BR-MDM-001) using CannotDeleteIfReferencedTrait; if referenced throw ReferencedEntityException with list of referencing entities; soft delete MdmEntity; observer creates change history with type DEPRECATED; log activity "Master data {type} {code} deleted"; return bool | | |
| TASK-028 | Create action `RestoreMasterDataAction.php`; find soft-deleted entity; restore MdmEntity; create change history with type RESTORED; log activity "Master data {type} {code} restored"; return MdmEntity | | |
| TASK-029 | Create action `GetMasterDataAction.php`; retrieve entity by ID or by type+code; load attributes relationship; decrypt sensitive attributes; check user has permission to view; return MdmEntity with decrypted attributes | | |
| TASK-030 | Create action `SearchMasterDataAction.php`; validate search query and filters; use repository search method; apply user's entity type permissions; support filters: entity_type, status, is_golden_record, quality_score range, effective_date; implement full-text search on entity_code and entity_name; return paginated results with < 100ms query time per PR-MDM-001 | | |
| TASK-031 | Create action `GetEntityVersionHistoryAction.php`; retrieve all versions (parent_entity_id chain); load change_history for each version; calculate version diffs; return Collection with version timeline | | |
| TASK-032 | Create exception `ReferencedEntityException.php` with properties: MdmEntity $entity, array $references (entity types and counts); getMessage() returning detailed error with list of referencing records | | |

### GOAL-005: API Controllers, Testing & Documentation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MDM-001 | Complete API for entity management | | |
| SR-MDM-001 | Role-based access control | | |
| PR-MDM-001 | Query performance < 100ms | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-033 | Create policy `MdmEntityPolicy.php` with methods: viewAny(User $user): bool requiring 'view-master-data' permission; view(User $user, MdmEntity $entity): bool checking tenant scope and entity type permission; create(User $user): bool requiring 'create-master-data'; update(User $user, MdmEntity $entity): bool requiring 'update-master-data' and entity.status.isEditable(); delete(User $user, MdmEntity $entity): bool requiring 'delete-master-data' and not referenced; restore(User $user): bool requiring 'manage-master-data'; all methods enforce tenant scope | | |
| TASK-034 | Create API controller `MdmEntityController.php` with routes: index (GET /api/v1/mdm/entities), store (POST), show (GET /entities/{id}), update (PATCH /entities/{id}), destroy (DELETE /entities/{id}), restore (POST /entities/{id}/restore), history (GET /entities/{id}/history), qualityScore (GET /entities/{id}/quality-score); inject MdmEntityRepositoryContract; authorize all actions via policy; handle ReferencedEntityException in destroy returning 409 Conflict | | |
| TASK-035 | Create form request `CreateMasterDataRequest.php` with validation: entity_type (required, in:customer,vendor,item,employee), entity_code (nullable, string, max:100, unique:mdm_entities,tenant_id+entity_type), entity_name (required, string, max:255), status (nullable, in:draft,pending_approval), effective_from (nullable, date, after_or_equal:today), attributes (nullable, array), attributes.*.name (required_with:attributes, string, max:100), attributes.*.value (required_with:attributes), attributes.*.type (required_with:attributes, in:string,number,date,boolean,json) | | |
| TASK-036 | Create form request `UpdateMasterDataRequest.php` with validation: entity_name (nullable, string, max:255), status (nullable, in), effective_from (nullable, date), effective_to (nullable, date, after:effective_from), attributes (nullable, array), create_version (nullable, boolean); authorize() checking entity.status.isEditable() | | |
| TASK-037 | Create form request `SearchMasterDataRequest.php` with validation: query (nullable, string, max:255), entity_type (nullable, in), status (nullable, in), is_golden_record (nullable, boolean), quality_score_min (nullable, numeric, min:0, max:100), quality_score_max (nullable, numeric, min:0, max:100), effective_date (nullable, date), per_page (nullable, integer, min:1, max:100) | | |
| TASK-038 | Create API resource `MdmEntityResource.php` with fields: id, tenant_id, entity_type, entity_code, entity_name, is_golden_record, status, data_quality_score, completeness_score, accuracy_score, timeliness_score, quality_grade (computed), is_current (computed), effective_from, effective_to, is_active, attributes (nested MdmAttributeResource collection, conditional on request includes attributes), parent (nested MdmEntityResource minimal, when present), created_at, updated_at, deleted_at (when soft deleted) | | |
| TASK-039 | Create API resource `MdmAttributeResource.php` with fields: id, attribute_name, attribute_value (decrypted if user has permission), attribute_type, source_system, confidence_score, is_encrypted (flag only, not actual value), created_at, updated_at | | |
| TASK-040 | Create API resource `MdmChangeHistoryResource.php` with fields: id, change_type, changed_fields, before_snapshot (conditional), after_snapshot (conditional), change_summary (computed), changed_by (nested UserResource minimal), change_reason, created_at | | |
| TASK-041 | Write comprehensive unit tests for models: test MdmEntity scopes (active, golden, byType, effectiveAt), test computed properties (is_current, has_future_version, qualityGrade), test CannotDeleteIfReferencedTrait, test MdmAttribute encryption/decryption, test MdmChangeHistory relationships | | |
| TASK-042 | Write comprehensive unit tests for services: test MdmVersioningService shouldCreateVersion logic, test createVersion with effective dates, test getVersionHistory ordering, test getVersionAt date logic, test AttributeEncryptionService shouldEncrypt matching, test encrypt/decrypt round-trip | | |
| TASK-043 | Write comprehensive unit tests for actions: test CreateMasterDataAction with various entity types, test encryption of sensitive attributes, test UpdateMasterDataAction versioning trigger, test DeleteMasterDataAction throwing ReferencedEntityException when referenced, test SearchMasterDataAction filters and query optimization | | |
| TASK-044 | Write feature tests for entity workflows: test create customer entity with attributes via API, test update entity creating version when significant field changes, test cannot delete entity referenced by transaction (BR-MDM-001), test restore soft-deleted entity, test search entities with filters, test MasterDataCreatedEvent dispatched (EV-MDM-001), test MasterDataUpdatedEvent dispatched (EV-MDM-002) | | |
| TASK-045 | Write integration tests: test entity creation from transactional module event (e.g., CustomerCreatedEvent from SUB17), test change history captured by observer, test versioning creates new entity with parent reference, test attribute encryption for sensitive fields | | |
| TASK-046 | Write performance tests: test single entity query < 100ms (PR-MDM-001), test search query with 1000 entities < 100ms, test entity creation with 20 attributes < 200ms, test version history retrieval for entity with 10 versions < 150ms | | |
| TASK-047 | Write scalability tests: test paginated query with 100k entities, test database can handle 10M+ records (SCR-MDM-001) using factories, test index performance on large datasets | | |
| TASK-048 | Write security tests: test role-based access prevents unauthorized view (SR-MDM-001), test sensitive attributes encrypted at rest (SR-MDM-002), test tenant isolation prevents cross-tenant access, test policy prevents update of non-editable status | | |
| TASK-049 | Write acceptance tests: test complete entity lifecycle (create → update → version → restore → delete), test entity code auto-generation, test versioning workflow with effective dates, test change history captured for all operations, test cannot delete referenced entity with clear error message (BR-MDM-001) | | |
| TASK-050 | Set up Pest configuration for MDM tests; configure database transactions, entity/attribute factories, seed test tenants and users | | |
| TASK-051 | Achieve minimum 80% code coverage for entity foundation module; run `./vendor/bin/pest --coverage --min=80` | | |
| TASK-052 | Create API documentation using OpenAPI 3.0: document all entity management endpoints, document request/response schemas, document authentication requirements, document query parameters and filters, document error responses including ReferencedEntityException | | |
| TASK-053 | Create user guide: how to create master data entities, understanding entity types, managing attributes, working with versioning and effective dates, viewing change history, understanding data quality scores | | |
| TASK-054 | Create technical documentation: entity versioning architecture, change history tracking mechanism, attribute encryption implementation, query optimization strategies for PR-MDM-001, database schema design for SCR-MDM-001 | | |
| TASK-055 | Create admin guide: configuring entity code prefixes, managing encrypted attributes list, setting quality thresholds, understanding tenant isolation, troubleshooting reference check failures | | |
| TASK-056 | Update package README with entity foundation features: centralized repository, versioning support, change history, encryption, performance characteristics, usage examples | | |
| TASK-057 | Validate all acceptance criteria: entity CRUD functional, versioning with effective dates working, change history captured (DR-MDM-001), entities cannot be deleted if referenced (BR-MDM-001), events dispatched (EV-MDM-001, EV-MDM-002), performance < 100ms (PR-MDM-001) | | |
| TASK-058 | Conduct code review: verify FR-MDM-001 implementation, verify FR-MDM-003 versioning, verify BR-MDM-001 reference check, verify DR-MDM-001 change history, verify SR-MDM-002 encryption, verify event dispatching | | |
| TASK-059 | Run full test suite for entity foundation; verify all tests pass; verify event dispatching works; verify observer hooks execute | | |
| TASK-060 | Deploy to staging; test entity creation with real data; test versioning workflow; test reference check blocking deletion; verify query performance < 100ms; test with 100k entities | | |
| TASK-061 | Create seeder `MdmEntitySeeder.php` for development with sample entities: 100 customers, 50 vendors, 200 items, 30 employees; include versioned entities, golden records, various statuses | | |
| TASK-062 | Create console command `php artisan mdm:generate-entity-code` for manual entity code generation; support --type flag for entity type, --count flag for batch generation | | |

## 3. Alternatives

- **ALT-001**: Single table for entities and attributes (EAV pattern) - rejected; separate tables provide better query performance and type safety
- **ALT-002**: Store change history in separate database/service - rejected; keep history with entities for transactional consistency and simpler queries
- **ALT-003**: Use Laravel's native Eloquent versioning packages - rejected; custom versioning provides more control over effective dating and MDM-specific requirements
- **ALT-004**: Encrypt entire entity record - rejected; attribute-level encryption provides granular control and better query performance
- **ALT-005**: Store attributes as JSONB in entities table - rejected; separate table allows for better indexing, querying, and confidence scoring per attribute
- **ALT-006**: Hard delete entities instead of soft delete - rejected; soft delete preserves audit trail and prevents data loss

## 4. Dependencies

### Mandatory Dependencies
- **DEP-001**: SUB01 (Multi-Tenancy) - Tenant model and BelongsToTenant trait
- **DEP-002**: SUB02 (Authentication & Authorization) - User model, permission system
- **DEP-003**: SUB03 (Audit Logging) - ActivityLoggerContract for tracking
- **DEP-004**: PostgreSQL 14+ - JSONB support for snapshots, indexes for performance
- **DEP-005**: Redis - Caching for PR-MDM-001 performance requirements

### Optional Dependencies
- **DEP-006**: SUB14 (Inventory) - Item entity consumer (future integration)
- **DEP-007**: SUB16 (Purchasing) - Vendor entity consumer (future integration)
- **DEP-008**: SUB17 (Sales) - Customer entity consumer (future integration)
- **DEP-009**: SUB13 (HCM) - Employee entity consumer (future integration)

### Package Dependencies
- **DEP-010**: lorisleiva/laravel-actions ^2.0 - Action and job pattern
- **DEP-011**: Laravel Encryption - Attribute encryption
- **DEP-012**: Laravel Observer - Change tracking
- **DEP-013**: Laravel Cache - Query result caching

## 5. Files

### Models & Enums
- `packages/mdm/src/Models/MdmEntity.php` - Master data entity model
- `packages/mdm/src/Models/MdmAttribute.php` - Entity attribute model
- `packages/mdm/src/Models/MdmChangeHistory.php` - Change history model
- `packages/mdm/src/Enums/EntityType.php` - Entity type enumeration
- `packages/mdm/src/Enums/EntityStatus.php` - Entity status enumeration
- `packages/mdm/src/Enums/AttributeType.php` - Attribute type enumeration
- `packages/mdm/src/Enums/ChangeType.php` - Change type enumeration
- `packages/mdm/src/Traits/CannotDeleteIfReferencedTrait.php` - Reference checking

### Repositories & Contracts
- `packages/mdm/src/Contracts/MdmEntityRepositoryContract.php` - Entity repository interface
- `packages/mdm/src/Repositories/MdmEntityRepository.php` - Entity repository implementation

### Services
- `packages/mdm/src/Services/MdmVersioningService.php` - Versioning logic
- `packages/mdm/src/Services/AttributeEncryptionService.php` - Attribute encryption

### Actions
- `packages/mdm/src/Actions/CreateMasterDataAction.php` - Create entity
- `packages/mdm/src/Actions/UpdateMasterDataAction.php` - Update entity
- `packages/mdm/src/Actions/DeleteMasterDataAction.php` - Delete entity
- `packages/mdm/src/Actions/RestoreMasterDataAction.php` - Restore deleted entity
- `packages/mdm/src/Actions/GetMasterDataAction.php` - Retrieve entity
- `packages/mdm/src/Actions/SearchMasterDataAction.php` - Search entities
- `packages/mdm/src/Actions/GetEntityVersionHistoryAction.php` - Version history
- `packages/mdm/src/Actions/CreateMasterDataVersionAction.php` - Create new version
- `packages/mdm/src/Actions/CreateMasterDataAttributeAction.php` - Create attribute
- `packages/mdm/src/Actions/UpdateMasterDataAttributeAction.php` - Update attribute
- `packages/mdm/src/Actions/BulkCreateAttributesAction.php` - Bulk attribute creation

### Controllers & Requests
- `packages/mdm/src/Http/Controllers/MdmEntityController.php` - Entity API controller
- `packages/mdm/src/Http/Requests/CreateMasterDataRequest.php` - Create validation
- `packages/mdm/src/Http/Requests/UpdateMasterDataRequest.php` - Update validation
- `packages/mdm/src/Http/Requests/SearchMasterDataRequest.php` - Search validation

### Resources
- `packages/mdm/src/Http/Resources/MdmEntityResource.php` - Entity transformation
- `packages/mdm/src/Http/Resources/MdmAttributeResource.php` - Attribute transformation
- `packages/mdm/src/Http/Resources/MdmChangeHistoryResource.php` - History transformation

### Events, Observers & Policies
- `packages/mdm/src/Events/MasterDataCreatedEvent.php` (EV-MDM-001)
- `packages/mdm/src/Events/MasterDataUpdatedEvent.php` (EV-MDM-002)
- `packages/mdm/src/Observers/MdmEntityObserver.php` - Change tracking
- `packages/mdm/src/Policies/MdmEntityPolicy.php` - Authorization

### Exceptions
- `packages/mdm/src/Exceptions/ReferencedEntityException.php` - BR-MDM-001 enforcement

### Database
- `packages/mdm/database/migrations/2025_01_01_000001_create_mdm_entities_table.php`
- `packages/mdm/database/migrations/2025_01_01_000002_create_mdm_attributes_table.php`
- `packages/mdm/database/migrations/2025_01_01_000003_create_mdm_change_history_table.php`
- `packages/mdm/database/factories/MdmEntityFactory.php`
- `packages/mdm/database/seeders/MdmEntitySeeder.php`

### Configuration & Commands
- `packages/mdm/config/mdm.php` - Package configuration
- `packages/mdm/src/Console/Commands/GenerateEntityCodeCommand.php` - Entity code generator

### Tests (Total: 62 tasks with testing components)
- `packages/mdm/tests/Unit/Models/MdmEntityTest.php`
- `packages/mdm/tests/Unit/Models/MdmAttributeTest.php`
- `packages/mdm/tests/Unit/Services/MdmVersioningServiceTest.php`
- `packages/mdm/tests/Unit/Services/AttributeEncryptionServiceTest.php`
- `packages/mdm/tests/Feature/MasterDataManagementTest.php`
- `packages/mdm/tests/Feature/MasterDataVersioningTest.php`
- `packages/mdm/tests/Integration/MdmEntityIntegrationTest.php`
- `packages/mdm/tests/Performance/EntityQueryPerformanceTest.php`

## 6. Testing

### Unit Tests (15 tests)
- **TEST-001**: MdmEntity scopes (active, golden, byType, byStatus)
- **TEST-002**: MdmEntity computed properties (is_current, has_future_version, qualityGrade)
- **TEST-003**: CannotDeleteIfReferencedTrait checkReferences logic
- **TEST-004**: MdmAttribute encryption/decryption
- **TEST-005**: MdmChangeHistory relationships and scopes
- **TEST-006**: MdmVersioningService shouldCreateVersion logic
- **TEST-007**: MdmVersioningService createVersion with effective dates
- **TEST-008**: MdmVersioningService getVersionHistory ordering
- **TEST-009**: AttributeEncryptionService shouldEncrypt matching
- **TEST-010**: All action classes with mocked dependencies

### Feature Tests (15 tests)
- **TEST-011**: Create customer entity with attributes via API
- **TEST-012**: Update entity triggers versioning when configured fields change
- **TEST-013**: Cannot delete entity referenced by transaction (BR-MDM-001)
- **TEST-014**: Restore soft-deleted entity
- **TEST-015**: Search entities with multiple filters
- **TEST-016**: MasterDataCreatedEvent dispatched on creation (EV-MDM-001)
- **TEST-017**: MasterDataUpdatedEvent dispatched on update (EV-MDM-002)
- **TEST-018**: Entity code auto-generated from config prefix
- **TEST-019**: Versioning creates new entity with parent reference
- **TEST-020**: Change history captured by observer

### Integration Tests (10 tests)
- **TEST-021**: Entity creation from CustomerCreatedEvent listener
- **TEST-022**: Change history captured by MdmEntityObserver
- **TEST-023**: Versioning creates new entity with proper parent linkage
- **TEST-024**: Attribute encryption for sensitive fields

### Performance Tests (7 tests)
- **TEST-025**: Single entity query completes < 100ms (PR-MDM-001)
- **TEST-026**: Search query with 1000 entities < 100ms
- **TEST-027**: Entity creation with 20 attributes < 200ms
- **TEST-028**: Version history retrieval for 10 versions < 150ms

### Scalability Tests (3 tests)
- **TEST-029**: Paginated query with 100k entities performs well
- **TEST-030**: Database handles 10M+ records (SCR-MDM-001)
- **TEST-031**: Index performance on large datasets

### Security Tests (5 tests)
- **TEST-032**: Role-based access prevents unauthorized view (SR-MDM-001)
- **TEST-033**: Sensitive attributes encrypted at rest (SR-MDM-002)
- **TEST-034**: Tenant isolation prevents cross-tenant access
- **TEST-035**: Policy prevents update of non-editable status

### Acceptance Tests (7 tests)
- **TEST-036**: Complete entity lifecycle (create → update → version → delete)
- **TEST-037**: Entity code auto-generation functional
- **TEST-038**: Versioning workflow with effective dates works correctly
- **TEST-039**: Change history captured for all operations (DR-MDM-001)
- **TEST-040**: Cannot delete referenced entity with clear error message
- **TEST-041**: Events dispatched correctly (EV-MDM-001, EV-MDM-002)
- **TEST-042**: Encryption/decryption transparent to users (SR-MDM-002)

**Total Test Coverage:** 62 tests (15 unit + 15 feature + 10 integration + 7 performance + 3 scalability + 5 security + 7 acceptance)

## 7. Risks & Assumptions

### Risks
- **RISK-001**: Performance degradation with 10M+ records - Mitigation: proper indexing, query optimization, materialized views, caching
- **RISK-002**: Encryption key rotation complexity - Mitigation: document key management, implement rotation procedure, test re-encryption
- **RISK-003**: Version history size growing unbounded - Mitigation: implement archival strategy, compress old versions, retention policies
- **RISK-004**: Complex queries on JSONB fields may be slow - Mitigation: use GIN indexes, consider separate columns for frequently queried fields
- **RISK-005**: Reference checking across many tables may be slow - Mitigation: optimize with indexes, cache reference counts, use database triggers

### Assumptions
- **ASSUMPTION-001**: Entity codes are unique per tenant and entity type combination
- **ASSUMPTION-002**: Effective date versioning is date-only (no time component)
- **ASSUMPTION-003**: Encrypted attributes stored as text (Base64 encoded after encryption)
- **ASSUMPTION-004**: Change history JSONB snapshots include all entity data at time of change
- **ASSUMPTION-005**: Reference checking only prevents hard deletion (soft delete always allowed)
- **ASSUMPTION-006**: Entity type set cannot change after creation (immutable)
- **ASSUMPTION-007**: Quality scores calculated externally (not in this plan)

## 8. KIV for Future Implementations

- **KIV-001**: Implement automated quality score calculation based on completeness rules
- **KIV-002**: Add support for custom entity types beyond the four core types
- **KIV-003**: Implement time-based versioning (not just date-based)
- **KIV-004**: Add support for attribute-level versioning (not just entity-level)
- **KIV-005**: Implement change approval workflow before applying updates
- **KIV-006**: Add support for entity relationships (not just attributes)
- **KIV-007**: Implement materialized views for analytics (ARCH-MDM-001)
- **KIV-008**: Add support for bulk versioning operations
- **KIV-009**: Implement entity locking for concurrent edit prevention
- **KIV-010**: Add support for entity templates and cloning
- **KIV-011**: Implement change history compression for old records
- **KIV-012**: Add support for entity archival and restoration

## 9. Related PRD / Further Reading

- **Master PRD**: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD**: [../prd/prd-01/PRD01-SUB18-MASTER-DATA-MANAGEMENT.md](../prd/prd-01/PRD01-SUB18-MASTER-DATA-MANAGEMENT.md)
- **Related Plans**:
  - PRD01-SUB18-PLAN02 (Data Quality & Validation) - Quality rule enforcement
  - PRD01-SUB18-PLAN03 (Duplicate Detection & Merging) - Golden record creation
  - PRD01-SUB18-PLAN04 (Data Stewardship & Bulk Operations) - Bulk import/export
- **Integration Documentation**:
  - SUB01 (Multi-Tenancy) - Tenant isolation
  - SUB02 (Authentication) - User permissions
  - SUB03 (Audit Logging) - Activity tracking
- **Architecture Documentation**: [../../architecture/](../../architecture/)
- **Coding Guidelines**: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
