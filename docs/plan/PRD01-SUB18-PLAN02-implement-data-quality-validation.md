---
plan: Data Quality Rules, Validation & Scoring
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, master-data, data-quality, validation, scoring, metrics, business-rules]
---

# PRD01-SUB18-PLAN02: Implement Data Quality & Validation

![Status: Planned](https://img.shields.io/badge/Status-Planned-blue)

This implementation plan establishes comprehensive data quality management with rule-based validation, automated scoring, and quality metrics tracking. This plan ensures master data meets quality thresholds before publishing to consuming systems and provides continuous quality monitoring.

## 1. Requirements & Constraints

### Functional Requirements
- **FR-MDM-002**: Support data quality rules with validation, deduplication, and enrichment

### Business Rules
- **BR-MDM-003**: Data quality score must exceed threshold before publishing to consuming systems

### Data Requirements
- **DR-MDM-002**: Maintain data quality metrics (completeness, accuracy, timeliness)

### Performance Requirements
- **PR-MDM-001**: Master data queries must complete in < 100ms for single record

### Constraints
- **CON-001**: Depends on PLAN01 for MdmEntity and MdmAttribute models
- **CON-002**: Depends on SUB01 (Multi-Tenancy) for tenant isolation
- **CON-003**: Depends on SUB02 (Authentication) for user access control
- **CON-004**: Must support complex validation rules (regex, range, custom logic)
- **CON-005**: Quality score calculation must be performant (<50ms per entity)

### Guidelines
- **GUD-001**: Use strategy pattern for different validation rule types
- **GUD-002**: Cache quality scores for frequently accessed entities
- **GUD-003**: Validate asynchronously for bulk operations
- **GUD-004**: Provide clear, actionable validation error messages
- **GUD-005**: Track quality metrics over time for trend analysis
- **GUD-006**: Make quality thresholds configurable per entity type

### Patterns
- **PAT-001**: Strategy pattern for validation rule types
- **PAT-002**: Chain of responsibility for multiple validation rules
- **PAT-003**: Observer pattern for automatic quality score updates
- **PAT-004**: Repository pattern for quality rules data access
- **PAT-005**: Factory pattern for creating validators

## 2. Implementation Steps

### GOAL-001: Data Quality Rules Foundation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MDM-002 | Data quality rules with validation | | |
| DR-MDM-002 | Quality metrics storage | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000004_create_mdm_quality_rules_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK tenants), rule_name (VARCHAR 255), entity_type (VARCHAR 50: customer/vendor/item/employee), rule_type (VARCHAR 50: completeness/accuracy/format/range/custom), field_name (VARCHAR 100), validation_logic (JSONB: contains rule configuration), error_message (TEXT nullable), severity (VARCHAR 20: error/warning/info default error), weight (DECIMAL 5,2 default 1.0 for score calculation), is_active (BOOLEAN default true), timestamps; indexes: tenant_id, entity_type, rule_type, is_active, (tenant_id + entity_type + field_name); FK cascade on tenant deletion | | |
| TASK-002 | Create migration `2025_01_01_000005_create_mdm_quality_metrics_table.php` with columns: id (BIGSERIAL), entity_id (BIGINT FK mdm_entities cascade), completeness_score (DECIMAL 5,2 default 0), completeness_details (JSONB: field-level scores), accuracy_score (DECIMAL 5,2 default 0), accuracy_details (JSONB), timeliness_score (DECIMAL 5,2 default 0), timeliness_details (JSONB), overall_quality_score (DECIMAL 5,2 default 0), failed_rules (JSONB array: rule IDs and messages), warning_rules (JSONB array), last_validated_at (TIMESTAMP nullable), validation_status (VARCHAR 20: passed/failed/warning), timestamps; indexes: entity_id unique, overall_quality_score, validation_status, last_validated_at | | |
| TASK-003 | Create migration `2025_01_01_000006_create_mdm_validation_history_table.php` with columns: id (BIGSERIAL), entity_id (BIGINT FK mdm_entities cascade), validation_run_id (UUID), rule_id (BIGINT FK mdm_quality_rules nullable), rule_type (VARCHAR 50), field_name (VARCHAR 100), validation_result (VARCHAR 20: passed/failed/warning), error_message (TEXT nullable), validated_value (TEXT nullable), validated_at (TIMESTAMP), validated_by (BIGINT FK users nullable for manual validations), timestamps; indexes: entity_id, validation_run_id, rule_id, validation_result, validated_at; supports audit trail per DR-MDM-002 | | |
| TASK-004 | Create enum `RuleType` with values: COMPLETENESS (required fields check), ACCURACY (data correctness check), FORMAT (regex/pattern validation), RANGE (numeric/date range validation), REFERENCE (referential integrity check), CUSTOM (custom validation logic); label() method; requiresValidator() returning true for CUSTOM | | |
| TASK-005 | Create enum `Severity` with values: ERROR (blocks publishing), WARNING (allows publishing with notification), INFO (informational only); label() method; blocksPublishing() returning true for ERROR only | | |
| TASK-006 | Create enum `ValidationStatus` with values: PASSED, FAILED, WARNING, NOT_VALIDATED; label() method; canPublish() returning true for PASSED and WARNING | | |
| TASK-007 | Create model `MdmQualityRule.php` with traits: BelongsToTenant; fillable: rule_name, entity_type, rule_type, field_name, validation_logic, error_message, severity, weight, is_active; casts: entity_type → EntityType enum, rule_type → RuleType enum, severity → Severity enum, validation_logic → array, weight → float, is_active → boolean; relationships: tenant (belongsTo), validationHistory (hasMany MdmValidationHistory); scopes: active(), byType(EntityType $type), byRuleType(RuleType $type), bySeverity(Severity $severity); methods: validate(mixed $value): bool, getValidationError(): string | | |
| TASK-008 | Create model `MdmQualityMetrics.php` with fillable: entity_id, completeness_score, completeness_details, accuracy_score, accuracy_details, timeliness_score, timeliness_details, overall_quality_score, failed_rules, warning_rules, validation_status; casts: completeness_score → float, accuracy_score → float, timeliness_score → float, overall_quality_score → float, completeness_details → array, accuracy_details → array, timeliness_details → array, failed_rules → array, warning_rules → array, validation_status → ValidationStatus enum, last_validated_at → datetime; relationships: entity (belongsTo MdmEntity); scopes: highQuality(float $threshold = 80), lowQuality(float $threshold = 60), failed(), passed(); computed: quality_grade (A/B/C/D/F based on overall_quality_score), meets_threshold (compares to config threshold per BR-MDM-003) | | |
| TASK-009 | Create model `MdmValidationHistory.php` with fillable: entity_id, validation_run_id, rule_id, rule_type, field_name, validation_result, error_message, validated_value; casts: rule_type → RuleType enum, validation_result → enum (passed/failed/warning), validated_at → datetime; relationships: entity (belongsTo MdmEntity), rule (belongsTo MdmQualityRule), validator (belongsTo User as validated_by); scopes: byEntity(int $entityId), byRunId(string $runId), failed(), passed(), byDateRange(Carbon $from, Carbon $to); supports DR-MDM-002 metrics tracking | | |
| TASK-010 | Create factory `MdmQualityRuleFactory.php` with states: completeness(), accuracy(), format(), range(), custom(), forEntityType(EntityType $type), error(), warning(), active(), inactive() | | |
| TASK-011 | Create factory `MdmQualityMetricsFactory.php` with states: highQuality(), mediumQuality(), lowQuality(), failed(), forEntity(MdmEntity $entity) | | |

### GOAL-002: Validation Rule Strategies

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MDM-002 | Implement validation logic for different rule types | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-012 | Create contract `ValidationRuleContract.php` with methods: validate(mixed $value, array $config): ValidationResult, getName(): string, getDescription(): string, getConfigSchema(): array (JSON schema for validation_logic) | | |
| TASK-013 | Create DTO `ValidationResult` with properties: bool $passed, ?string $errorMessage, string $severity, array $details; methods: success(): static, failure(string $message, string $severity = 'error'): static, warning(string $message): static | | |
| TASK-014 | Create validator `CompletenessValidator.php` implementing ValidationRuleContract; validate required fields are not null/empty; support config: required_fields array, allow_whitespace bool; check string length > 0, arrays not empty, numbers not null; return ValidationResult with specific missing fields in details | | |
| TASK-015 | Create validator `FormatValidator.php`; validate value matches regex pattern; support config: pattern (regex), examples array; use preg_match for validation; return ValidationResult with pattern mismatch details; common patterns: email, phone, postal_code, tax_id, url | | |
| TASK-016 | Create validator `RangeValidator.php`; validate numeric values within min/max; validate date values within date range; support config: min, max, inclusive (bool); return ValidationResult with out-of-range details | | |
| TASK-017 | Create validator `AccuracyValidator.php`; validate data correctness using external checks; support config: check_type (checksum/lookup/calculation), reference_table, reference_column; validate checksums (e.g., credit card Luhn algorithm, ISBN); validate lookup values exist in reference tables; return ValidationResult with accuracy failure details | | |
| TASK-018 | Create validator `ReferenceValidator.php`; validate referential integrity; support config: reference_table, reference_column, nullable; check referenced record exists; return ValidationResult with missing reference details | | |
| TASK-019 | Create validator `CustomValidator.php`; support custom validation logic via callable; support config: validator_class (FQCN), validator_method, parameters array; invoke custom validator with reflection; return ValidationResult from custom validator | | |
| TASK-020 | Create factory `ValidationRuleFactory.php` with method: make(RuleType $type, array $config): ValidationRuleContract; map rule types to validator classes; cache validator instances; throw InvalidValidationRuleException if type not supported | | |

### GOAL-003: Quality Score Calculation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MDM-002 | Automated quality scoring | | |
| BR-MDM-003 | Quality threshold enforcement | | |
| DR-MDM-002 | Quality metrics maintenance | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-021 | Create service `QualityScoreCalculator.php` with methods: calculateCompleteness(MdmEntity $entity, Collection $rules): float (required fields present), calculateAccuracy(MdmEntity $entity, Collection $rules): float (data correctness), calculateTimeliness(MdmEntity $entity): float (based on last_updated_at vs current date), calculateOverall(array $scores, array $weights = [completeness: 0.4, accuracy: 0.4, timeliness: 0.2]): float (weighted average), getQualityGrade(float $score): string (A >= 90, B >= 80, C >= 70, D >= 60, F < 60); ensure calculation < 50ms per CON-005 | | |
| TASK-022 | Create service `DataQualityService.php` with methods: validateEntity(MdmEntity $entity, ?string $runId = null): MdmQualityMetrics, validateAttribute(MdmEntity $entity, string $attributeName, mixed $value): Collection<ValidationResult>, getRulesForEntity(MdmEntity $entity): Collection<MdmQualityRule>, applyRule(MdmQualityRule $rule, mixed $value): ValidationResult, calculateQualityScore(MdmEntity $entity): MdmQualityMetrics, meetsPublishingThreshold(MdmEntity $entity): bool (per BR-MDM-003), getQualityTrend(MdmEntity $entity, int $days = 30): array | | |
| TASK-023 | Create action `ValidateMasterDataAction.php` using AsAction; inject DataQualityService, QualityScoreCalculator; generate validation_run_id (UUID); retrieve active quality rules for entity type; validate each attribute against applicable rules; collect ValidationResult for each rule; create/update MdmQualityMetrics; create MdmValidationHistory records; update entity's quality scores (completeness, accuracy, timeliness, overall); cache quality metrics with 5-minute TTL; log activity "Entity {code} validated: {status}"; return MdmQualityMetrics with validation results | | |
| TASK-024 | Create action `CalculateQualityScoreAction.php`; retrieve entity attributes; retrieve quality rules for entity type; calculate completeness score (required fields / total fields * 100); calculate accuracy score (passed accuracy rules / total accuracy rules * 100); calculate timeliness score (100 - days_since_update with decay); calculate overall score (weighted average); update entity's data_quality_score, completeness_score, accuracy_score, timeliness_score; create/update MdmQualityMetrics; return MdmQualityMetrics | | |
| TASK-025 | Create action `CheckPublishingEligibilityAction.php`; retrieve entity quality metrics; check overall_quality_score >= threshold from config('mdm.quality_thresholds.publish') per BR-MDM-003; check validation_status is PASSED or WARNING; check all ERROR severity rules passed; return bool and array of blocking reasons if ineligible | | |
| TASK-026 | Update MdmEntity model from PLAN01: add method: calculateQualityScore(): float delegating to CalculateQualityScoreAction; add method: meetsPublishingThreshold(): bool delegating to CheckPublishingEligibilityAction; add scope: publishable() filtering entities meeting threshold | | |

### GOAL-004: Quality Rules Management

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MDM-002 | Quality rule configuration and management | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-027 | Create contract `QualityRuleRepositoryContract.php` with methods: findById(int $id): ?MdmQualityRule, create(array $data): MdmQualityRule, update(MdmQualityRule $rule, array $data): MdmQualityRule, delete(MdmQualityRule $rule): bool, getActiveRulesForEntity(EntityType $type): Collection, getByRuleType(RuleType $type): Collection, testRule(MdmQualityRule $rule, mixed $testValue): ValidationResult | | |
| TASK-028 | Implement `QualityRuleRepository.php` with caching of active rules per entity type; cache key "mdm:quality_rules:{entity_type}"; cache TTL 15 minutes; implement filters: entity_type, rule_type, severity, is_active; eager load tenant relationship | | |
| TASK-029 | Create action `CreateQualityRuleAction.php`; validate rule_name, entity_type, rule_type, field_name, validation_logic JSON schema; validate validation_logic against rule type's config schema; set default weight 1.0 if not provided; create MdmQualityRule; clear quality rules cache for entity type; log activity "Quality rule {name} created for {entity_type}"; return MdmQualityRule | | |
| TASK-030 | Create action `UpdateQualityRuleAction.php`; validate changes; re-validate validation_logic if changed; update MdmQualityRule; clear quality rules cache; optionally re-validate all entities of that type if requested; log activity "Quality rule {name} updated"; return MdmQualityRule | | |
| TASK-031 | Create action `DeleteQualityRuleAction.php`; soft delete or hard delete MdmQualityRule; clear quality rules cache; log activity "Quality rule {name} deleted"; return bool | | |
| TASK-032 | Create action `TestQualityRuleAction.php`; accept rule and test_value; apply ValidationRuleFactory to create validator; run validation; return ValidationResult without persisting; used for rule testing in UI | | |
| TASK-033 | Create action `BulkValidateEntitiesAction.php` for async validation; queue ValidateMasterDataAction for each entity; use job batching; report progress; used after creating new quality rules; return batch ID for status tracking | | |

### GOAL-005: API Controllers, Testing & Documentation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MDM-002 | Complete API for quality management | | |
| BR-MDM-003 | Quality threshold enforcement | | |
| PR-MDM-001 | Query performance < 100ms | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-034 | Create policy `MdmQualityRulePolicy.php` with methods: viewAny(User $user): bool requiring 'view-quality-rules'; create(User $user): bool requiring 'manage-quality-rules'; update/delete requiring 'manage-quality-rules'; enforce tenant scope | | |
| TASK-035 | Create API controller `QualityRuleController.php` with routes: index (GET /api/v1/mdm/quality-rules), store (POST), show (GET /quality-rules/{id}), update (PATCH /quality-rules/{id}), destroy (DELETE /quality-rules/{id}), test (POST /quality-rules/{id}/test), activeRules (GET /quality-rules/entity-type/{type}); authorize all actions via policy | | |
| TASK-036 | Create API controller `QualityValidationController.php` with routes: validate (POST /api/v1/mdm/entities/{id}/validate), qualityScore (GET /entities/{id}/quality-score), qualityMetrics (GET /entities/{id}/quality-metrics), publishingEligibility (GET /entities/{id}/publishing-eligibility), bulkValidate (POST /entities/bulk-validate), validationHistory (GET /entities/{id}/validation-history); authorize actions | | |
| TASK-037 | Create API controller `QualityReportController.php` with routes: dashboard (GET /api/v1/mdm/reports/quality-dashboard), qualityTrends (GET /reports/quality-trends), lowQualityEntities (GET /reports/low-quality-entities), rulePerformance (GET /reports/rule-performance); support date range filters | | |
| TASK-038 | Create form request `CreateQualityRuleRequest.php` with validation: rule_name (required, string, max:255), entity_type (required, in), rule_type (required, in), field_name (required, string, max:100), validation_logic (required, array), severity (nullable, in), weight (nullable, numeric, min:0, max:10), is_active (nullable, boolean); custom validation for validation_logic JSON schema based on rule_type | | |
| TASK-039 | Create form request `UpdateQualityRuleRequest.php` with similar validation, all fields nullable except partial updates | | |
| TASK-040 | Create form request `TestQualityRuleRequest.php` with validation: test_value (required), run_against_entities (nullable, boolean), entity_ids (nullable, array, exists:mdm_entities) | | |
| TASK-041 | Create form request `ValidateEntityRequest.php` with validation: revalidate (nullable, boolean), rules_to_apply (nullable, array, exists:mdm_quality_rules), create_history (nullable, boolean default true) | | |
| TASK-042 | Create API resource `MdmQualityRuleResource.php` with fields: id, tenant_id, rule_name, entity_type, rule_type, field_name, validation_logic, error_message, severity, weight, is_active, created_at, updated_at | | |
| TASK-043 | Create API resource `MdmQualityMetricsResource.php` with fields: id, entity (nested MdmEntityResource minimal), completeness_score, accuracy_score, timeliness_score, overall_quality_score, quality_grade (computed), meets_threshold (computed), validation_status, failed_rules (nested array), warning_rules (nested array), last_validated_at | | |
| TASK-044 | Create API resource `MdmValidationHistoryResource.php` with fields: id, validation_run_id, rule (nested MdmQualityRuleResource minimal), rule_type, field_name, validation_result, error_message, validated_at | | |
| TASK-045 | Write comprehensive unit tests for validators: test CompletenessValidator with various required fields, test FormatValidator with regex patterns, test RangeValidator with min/max, test AccuracyValidator with checksums, test ReferenceValidator with DB lookups, test CustomValidator with reflection | | |
| TASK-046 | Write comprehensive unit tests for services: test QualityScoreCalculator formulas, test DataQualityService validateEntity with multiple rules, test meetsPublishingThreshold logic (BR-MDM-003), test quality score caching | | |
| TASK-047 | Write comprehensive unit tests for actions: test ValidateMasterDataAction with various rule types, test CalculateQualityScoreAction weighted average, test CheckPublishingEligibilityAction threshold checking | | |
| TASK-048 | Write feature tests for quality workflows: test create quality rule via API, test validate entity against rules, test quality score updated after validation, test entity cannot publish if below threshold (BR-MDM-003), test bulk validation job dispatched | | |
| TASK-049 | Write integration tests: test quality metrics updated when entity attributes change, test validation history created, test quality rule cache invalidation on update, test threshold enforcement during entity publishing | | |
| TASK-050 | Write performance tests: test validation of single entity < 50ms (CON-005), test bulk validation of 100 entities, test quality score calculation performance, test query performance for quality metrics < 100ms (PR-MDM-001) | | |
| TASK-051 | Write acceptance tests: test complete quality management lifecycle, test quality rule creation and testing, test entity validation failing with clear error messages, test quality score calculation accuracy, test publishing blocked when below threshold (BR-MDM-003), test quality metrics maintained over time (DR-MDM-002) | | |
| TASK-052 | Set up Pest configuration for quality tests; configure validation rule factories, quality metrics factories, mock validators for testing | | |
| TASK-053 | Achieve minimum 80% code coverage for quality module; run `./vendor/bin/pest --coverage --min=80` | | |
| TASK-054 | Create API documentation: document quality rule endpoints, document validation endpoints, document quality report endpoints, document validation_logic JSON schemas per rule type, document threshold configuration | | |
| TASK-055 | Create user guide: how to create quality rules, understanding rule types, interpreting quality scores, viewing validation history, troubleshooting validation failures, configuring quality thresholds | | |
| TASK-056 | Create technical documentation: validation strategy architecture, quality score calculation formulas, rule type implementations, caching strategy for rules and scores, performance optimization techniques | | |
| TASK-057 | Create admin guide: configuring quality thresholds (BR-MDM-003), managing quality rules, bulk validation procedures, monitoring quality metrics (DR-MDM-002), troubleshooting validation performance | | |
| TASK-058 | Update package README with quality features: validation rules, quality scoring, threshold enforcement, metrics tracking | | |
| TASK-059 | Validate acceptance criteria: quality rules functional, validation working, scoring accurate, threshold enforcement working (BR-MDM-003), metrics maintained (DR-MDM-002) | | |
| TASK-060 | Conduct code review: verify FR-MDM-002 implementation, verify BR-MDM-003 threshold enforcement, verify DR-MDM-002 metrics storage, verify validation performance < 50ms | | |
| TASK-061 | Run full test suite for quality module; verify all tests pass; verify threshold enforcement works; verify validation history captured | | |
| TASK-062 | Deploy to staging; test quality rule creation; test entity validation; verify threshold blocking; test bulk validation; verify performance < 50ms per validation | | |
| TASK-063 | Create seeder `MdmQualityRuleSeeder.php` with sample rules: customer email format, vendor tax_id completeness, item price range, employee hire_date timeliness | | |
| TASK-064 | Create console command `php artisan mdm:validate-all-entities` for bulk validation; support --entity-type flag, --dry-run flag; report validation statistics | | |
| TASK-065 | Update config/mdm.php: add quality_thresholds section (publish: 75, warning: 60, fail: 40), add validation_logic_schemas per rule type, add default_weights (completeness: 0.4, accuracy: 0.4, timeliness: 0.2), add cache_ttl for rules and scores | | |

## 3. Alternatives

- **ALT-001**: Use third-party data quality library - rejected; custom implementation provides MDM-specific flexibility and control
- **ALT-002**: Real-time validation on every attribute change - rejected; batch validation more performant, async for bulk operations
- **ALT-003**: Store validation logic as code (not JSONB) - rejected; JSONB provides flexibility for dynamic rule configuration without code deployment
- **ALT-004**: Single quality score instead of separate completeness/accuracy/timeliness - rejected; separate scores provide actionable insights
- **ALT-005**: Hard-coded quality thresholds - rejected; configurable thresholds allow per-tenant customization
- **ALT-006**: Store all validation results forever - rejected; implement retention policy to manage database size

## 4. Dependencies

### Mandatory Dependencies
- **DEP-001**: PLAN01 (Master Data Entity Foundation) - MdmEntity and MdmAttribute models
- **DEP-002**: SUB01 (Multi-Tenancy) - Tenant isolation for quality rules
- **DEP-003**: SUB02 (Authentication) - User permissions for rule management
- **DEP-004**: SUB03 (Audit Logging) - Activity tracking for rule changes
- **DEP-005**: Laravel Validation - Built-in validation rules
- **DEP-006**: Laravel Queue - Async bulk validation jobs

### Optional Dependencies
- **DEP-007**: External data quality services - For advanced accuracy checking
- **DEP-008**: Machine learning services - For predictive quality scoring

### Package Dependencies
- **DEP-009**: lorisleiva/laravel-actions ^2.0 - Action pattern
- **DEP-010**: Laravel Cache - Rule and score caching
- **DEP-011**: Laravel Queue - Bulk validation jobs

## 5. Files

### Models & Enums
- `packages/mdm/src/Models/MdmQualityRule.php` - Quality rule model
- `packages/mdm/src/Models/MdmQualityMetrics.php` - Quality metrics model
- `packages/mdm/src/Models/MdmValidationHistory.php` - Validation history model
- `packages/mdm/src/Enums/RuleType.php` - Rule type enumeration
- `packages/mdm/src/Enums/Severity.php` - Severity enumeration
- `packages/mdm/src/Enums/ValidationStatus.php` - Validation status enumeration

### Contracts & DTOs
- `packages/mdm/src/Contracts/ValidationRuleContract.php` - Validator interface
- `packages/mdm/src/Contracts/QualityRuleRepositoryContract.php` - Repository interface
- `packages/mdm/src/DataTransferObjects/ValidationResult.php` - Validation result DTO

### Validators
- `packages/mdm/src/Validators/CompletenessValidator.php` - Required fields validator
- `packages/mdm/src/Validators/FormatValidator.php` - Regex pattern validator
- `packages/mdm/src/Validators/RangeValidator.php` - Min/max range validator
- `packages/mdm/src/Validators/AccuracyValidator.php` - Data correctness validator
- `packages/mdm/src/Validators/ReferenceValidator.php` - Referential integrity validator
- `packages/mdm/src/Validators/CustomValidator.php` - Custom logic validator
- `packages/mdm/src/Validators/ValidationRuleFactory.php` - Validator factory

### Services
- `packages/mdm/src/Services/QualityScoreCalculator.php` - Score calculation
- `packages/mdm/src/Services/DataQualityService.php` - Quality management

### Repositories
- `packages/mdm/src/Repositories/QualityRuleRepository.php` - Quality rule repository

### Actions
- `packages/mdm/src/Actions/ValidateMasterDataAction.php` - Validate entity
- `packages/mdm/src/Actions/CalculateQualityScoreAction.php` - Calculate score
- `packages/mdm/src/Actions/CheckPublishingEligibilityAction.php` - Check threshold
- `packages/mdm/src/Actions/CreateQualityRuleAction.php` - Create rule
- `packages/mdm/src/Actions/UpdateQualityRuleAction.php` - Update rule
- `packages/mdm/src/Actions/DeleteQualityRuleAction.php` - Delete rule
- `packages/mdm/src/Actions/TestQualityRuleAction.php` - Test rule
- `packages/mdm/src/Actions/BulkValidateEntitiesAction.php` - Bulk validation

### Controllers & Requests
- `packages/mdm/src/Http/Controllers/QualityRuleController.php` - Rule API controller
- `packages/mdm/src/Http/Controllers/QualityValidationController.php` - Validation API
- `packages/mdm/src/Http/Controllers/QualityReportController.php` - Reporting API
- `packages/mdm/src/Http/Requests/CreateQualityRuleRequest.php` - Create validation
- `packages/mdm/src/Http/Requests/UpdateQualityRuleRequest.php` - Update validation
- `packages/mdm/src/Http/Requests/TestQualityRuleRequest.php` - Test validation
- `packages/mdm/src/Http/Requests/ValidateEntityRequest.php` - Validate validation

### Resources & Policies
- `packages/mdm/src/Http/Resources/MdmQualityRuleResource.php` - Rule transformation
- `packages/mdm/src/Http/Resources/MdmQualityMetricsResource.php` - Metrics transformation
- `packages/mdm/src/Http/Resources/MdmValidationHistoryResource.php` - History transformation
- `packages/mdm/src/Policies/MdmQualityRulePolicy.php` - Authorization

### Database & Configuration
- `packages/mdm/database/migrations/2025_01_01_000004_create_mdm_quality_rules_table.php`
- `packages/mdm/database/migrations/2025_01_01_000005_create_mdm_quality_metrics_table.php`
- `packages/mdm/database/migrations/2025_01_01_000006_create_mdm_validation_history_table.php`
- `packages/mdm/database/factories/MdmQualityRuleFactory.php`
- `packages/mdm/database/factories/MdmQualityMetricsFactory.php`
- `packages/mdm/database/seeders/MdmQualityRuleSeeder.php`
- `packages/mdm/config/mdm.php` - Updated with quality configuration

### Commands
- `packages/mdm/src/Console/Commands/ValidateAllEntitiesCommand.php` - Bulk validation

### Tests
- `packages/mdm/tests/Unit/Validators/*Test.php` - Validator unit tests
- `packages/mdm/tests/Unit/Services/QualityScoreCalculatorTest.php`
- `packages/mdm/tests/Unit/Services/DataQualityServiceTest.php`
- `packages/mdm/tests/Feature/QualityManagementTest.php`
- `packages/mdm/tests/Integration/QualityValidationIntegrationTest.php`
- `packages/mdm/tests/Performance/ValidationPerformanceTest.php`

## 6. Testing

### Unit Tests (15 tests)
- **TEST-001**: CompletenessValidator with various required fields
- **TEST-002**: FormatValidator with regex patterns (email, phone, etc.)
- **TEST-003**: RangeValidator with numeric and date ranges
- **TEST-004**: AccuracyValidator with checksums and lookups
- **TEST-005**: ReferenceValidator with DB integrity checks
- **TEST-006**: CustomValidator with reflection invocation
- **TEST-007**: QualityScoreCalculator completeness formula
- **TEST-008**: QualityScoreCalculator weighted average
- **TEST-009**: DataQualityService validateEntity with multiple rules
- **TEST-010**: DataQualityService meetsPublishingThreshold (BR-MDM-003)

### Feature Tests (12 tests)
- **TEST-011**: Create quality rule via API
- **TEST-012**: Update quality rule clears cache
- **TEST-013**: Test quality rule without persisting
- **TEST-014**: Validate entity against rules
- **TEST-015**: Quality score updated after validation
- **TEST-016**: Entity cannot publish if below threshold (BR-MDM-003)
- **TEST-017**: Bulk validation job dispatched
- **TEST-018**: Validation history created (DR-MDM-002)

### Integration Tests (8 tests)
- **TEST-019**: Quality metrics updated on attribute change
- **TEST-020**: Validation history audit trail complete
- **TEST-021**: Quality rule cache invalidation
- **TEST-022**: Threshold enforcement during publishing

### Performance Tests (5 tests)
- **TEST-023**: Validation of single entity < 50ms (CON-005)
- **TEST-024**: Bulk validation of 100 entities
- **TEST-025**: Quality score calculation performance
- **TEST-026**: Quality metrics query < 100ms (PR-MDM-001)

### Acceptance Tests (8 tests)
- **TEST-027**: Complete quality rule lifecycle
- **TEST-028**: Entity validation with clear error messages
- **TEST-029**: Quality score calculation accuracy
- **TEST-030**: Publishing blocked when below threshold (BR-MDM-003)
- **TEST-031**: Quality metrics maintained over time (DR-MDM-002)
- **TEST-032**: Multiple validation rules applied correctly
- **TEST-033**: Validation history provides complete audit trail
- **TEST-034**: Quality dashboard displays accurate metrics

**Total Test Coverage:** 48 tests (15 unit + 12 feature + 8 integration + 5 performance + 8 acceptance)

## 7. Risks & Assumptions

### Risks
- **RISK-001**: Complex validation logic may cause performance issues - Mitigation: async validation, caching, optimization
- **RISK-002**: Quality rules may conflict or be contradictory - Mitigation: rule testing endpoint, clear documentation, validation
- **RISK-003**: Custom validators may have security vulnerabilities - Mitigation: whitelist allowed classes, sandboxing, code review
- **RISK-004**: Validation history table may grow large - Mitigation: implement retention policy, archival strategy
- **RISK-005**: Threshold configuration may be too restrictive - Mitigation: configurable per tenant, warning vs error severity

### Assumptions
- **ASSUMPTION-001**: Quality rules configured by administrators, not end users
- **ASSUMPTION-002**: Validation logic stored as JSON is sufficient (no complex code)
- **ASSUMPTION-003**: Quality scores cached for 5 minutes is acceptable freshness
- **ASSUMPTION-004**: Weighted average (40/40/20) appropriate for most entity types
- **ASSUMPTION-005**: Validation history retained for 90 days minimum
- **ASSUMPTION-006**: Publishing threshold default of 75% appropriate for most use cases
- **ASSUMPTION-007**: Custom validators are optional, not required for MVP

## 8. KIV for Future Implementations

- **KIV-001**: Machine learning for predictive quality scoring
- **KIV-002**: Automated quality rule suggestions based on data patterns
- **KIV-003**: Quality score benchmarking against industry standards
- **KIV-004**: Real-time quality monitoring dashboards
- **KIV-005**: Integration with external data enrichment services
- **KIV-006**: Automated data cleansing and correction
- **KIV-007**: Quality score API for external systems
- **KIV-008**: Advanced anomaly detection in validation
- **KIV-009**: Quality rule versioning and history
- **KIV-010**: Multi-language support for error messages
- **KIV-011**: Quality SLA monitoring and alerting
- **KIV-012**: A/B testing for quality rule effectiveness

## 9. Related PRD / Further Reading

- **Master PRD**: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD**: [../prd/prd-01/PRD01-SUB18-MASTER-DATA-MANAGEMENT.md](../prd/prd-01/PRD01-SUB18-MASTER-DATA-MANAGEMENT.md)
- **Related Plans**:
  - PRD01-SUB18-PLAN01 (Master Data Entity Foundation) - Base entity models
  - PRD01-SUB18-PLAN03 (Duplicate Detection & Merging) - Uses quality scores
  - PRD01-SUB18-PLAN04 (Data Stewardship & Bulk Operations) - Quality validation
- **Integration Documentation**:
  - SUB01 (Multi-Tenancy) - Tenant isolation
  - SUB02 (Authentication) - User permissions
  - SUB03 (Audit Logging) - Activity tracking
- **Architecture Documentation**: [../../architecture/](../../architecture/)
- **Coding Guidelines**: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
