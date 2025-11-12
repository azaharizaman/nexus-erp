---
plan: Duplicate Detection, Matching & Golden Records
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, master-data, duplicate-detection, deduplication, golden-record, data-matching, merge]
---

# PRD01-SUB18-PLAN03: Implement Duplicate Detection & Merging

![Status: Planned](https://img.shields.io/badge/Status-Planned-blue)

This implementation plan establishes duplicate detection algorithms, matching rules, golden record creation, and entity merge workflows. This plan ensures data consistency by identifying and resolving duplicate master data across source systems.

## 1. Requirements & Constraints

### Functional Requirements
- **FR-MDM-006**: Implement data matching algorithms to detect duplicates across systems
- **FR-MDM-007**: Provide golden record creation from multiple source systems

### Business Rules
- **BR-MDM-002**: Duplicate records must be merged, not overwritten to preserve history

### Event Requirements
- **EV-MDM-003**: Emit DuplicateDetectedEvent when potential duplicate is identified

### Performance Requirements
- **PR-MDM-001**: Master data queries must complete in < 100ms for single record
- **PR-MDM-002**: Bulk import must process 10,000+ records in < 60 seconds

### Constraints
- **CON-001**: Depends on PLAN01 for MdmEntity model
- **CON-002**: Depends on PLAN02 for quality scoring
- **CON-003**: Must support multiple matching algorithms (exact, fuzzy, phonetic, Levenshtein)
- **CON-004**: Duplicate detection must be performant (< 500ms per entity)
- **CON-005**: Merge operations must be transactional and reversible

### Guidelines
- **GUD-001**: Use strategy pattern for different matching algorithms
- **GUD-002**: Cache matching results for performance
- **GUD-003**: Preserve all source data when merging (BR-MDM-002)
- **GUD-004**: Require manual approval for high-impact merges
- **GUD-005**: Track golden record lineage from source entities
- **GUD-006**: Support confidence scoring for matches

### Patterns
- **PAT-001**: Strategy pattern for matching algorithms
- **PAT-002**: Factory pattern for creating matchers
- **PAT-003**: Builder pattern for golden record construction
- **PAT-004**: Command pattern for merge operations with undo
- **PAT-005**: Repository pattern for duplicate management

## 2. Implementation Steps

### GOAL-001: Duplicate Detection Rules & Matching

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MDM-006 | Data matching algorithms for duplicate detection | | |
| EV-MDM-003 | Duplicate detected event | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000007_create_mdm_duplicate_rules_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK tenants), rule_name (VARCHAR 255), entity_type (VARCHAR 50: customer/vendor/item/employee), matching_fields (JSONB array: [name, email, phone]), matching_algorithm (VARCHAR 50: exact/fuzzy/phonetic/levenshtein), match_threshold (DECIMAL 5,2 default 80: 0-100%), field_weights (JSONB: {name: 0.5, email: 0.3, phone: 0.2}), is_active (BOOLEAN default true), auto_merge (BOOLEAN default false: auto-merge if score >= threshold), timestamps; indexes: tenant_id, entity_type, is_active, (tenant_id + entity_type + rule_name) unique; FK cascade on tenant deletion | | |
| TASK-002 | Create migration `2025_01_01_000008_create_mdm_duplicate_matches_table.php` with columns: id (BIGSERIAL), rule_id (BIGINT FK mdm_duplicate_rules), entity_1_id (BIGINT FK mdm_entities cascade), entity_2_id (BIGINT FK mdm_entities cascade), match_score (DECIMAL 5,2: 0-100), match_details (JSONB: field-level scores and matched values), resolution_status (VARCHAR 20: pending/confirmed/rejected/merged default pending), resolution_reason (TEXT nullable), resolved_by (BIGINT FK users nullable), resolved_at (TIMESTAMP nullable), created_at, updated_at; indexes: rule_id, entity_1_id, entity_2_id, resolution_status, match_score DESC, (entity_1_id + entity_2_id) unique; check constraint entity_1_id < entity_2_id to prevent duplicates | | |
| TASK-003 | Create enum `MatchingAlgorithm` with values: EXACT (exact string match), FUZZY (Levenshtein distance), PHONETIC (Soundex/Metaphone), HYBRID (combination of algorithms); requiresThreshold() returning false for EXACT; label() method | | |
| TASK-004 | Create enum `ResolutionStatus` with values: PENDING (awaiting review), CONFIRMED (verified duplicate), REJECTED (not a duplicate), MERGED (entities merged), AUTO_MERGED (automatically merged); isPending() method; requiresApproval() returning true for PENDING/CONFIRMED | | |
| TASK-005 | Create model `MdmDuplicateRule.php` with traits: BelongsToTenant; fillable: rule_name, entity_type, matching_fields, matching_algorithm, match_threshold, field_weights, is_active, auto_merge; casts: entity_type → EntityType enum, matching_algorithm → MatchingAlgorithm enum, matching_fields → array, field_weights → array, match_threshold → float, is_active → boolean, auto_merge → boolean; relationships: tenant (belongsTo), matches (hasMany MdmDuplicateMatch); scopes: active(), byEntityType(EntityType $type), byAlgorithm(MatchingAlgorithm $algo), autoMergeable(); validation: matching_fields not empty, field_weights sum to 1.0, match_threshold 0-100 | | |
| TASK-006 | Create model `MdmDuplicateMatch.php` with fillable: rule_id, entity_1_id, entity_2_id, match_score, match_details, resolution_status, resolution_reason, resolved_by, resolved_at; casts: match_score → float, match_details → array, resolution_status → ResolutionStatus enum, resolved_at → datetime; relationships: rule (belongsTo MdmDuplicateRule), entity1 (belongsTo MdmEntity as entity_1_id), entity2 (belongsTo MdmEntity as entity_2_id), resolver (belongsTo User as resolved_by); scopes: pending(), confirmed(), rejected(), merged(), highScore(float $threshold), byRule(int $ruleId), byEntity(int $entityId); computed: can_auto_merge (rule.auto_merge && match_score >= rule.match_threshold), should_review (match_score >= 80 && !merged) | | |
| TASK-007 | Create factory `MdmDuplicateRuleFactory.php` with states: exact(), fuzzy(), phonetic(), forEntityType(EntityType $type), autoMergeable(), active() | | |
| TASK-008 | Create factory `MdmDuplicateMatchFactory.php` with states: highScore(), lowScore(), pending(), confirmed(), merged(), forEntities(MdmEntity $e1, MdmEntity $e2) | | |

### GOAL-002: Matching Algorithm Strategies

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MDM-006 | Implement various matching algorithms | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-009 | Create contract `MatchingStrategyContract.php` with methods: match(MdmEntity $entity1, MdmEntity $entity2, array $fields, array $weights): MatchResult, getName(): string, getDescription(): string, requiresThreshold(): bool, getRecommendedThreshold(): float | | |
| TASK-010 | Create DTO `MatchResult` with properties: float $score (0-100), array $fieldScores (field => score), array $matchedValues (field => [value1, value2]), string $algorithm; methods: isMatch(float $threshold): bool, getMatchedFields(): array, getUnmatchedFields(): array | | |
| TASK-011 | Create strategy `ExactMatchStrategy.php` implementing MatchingStrategyContract; compare strings for exact equality (case-insensitive); calculate field scores: 100 if match, 0 if not; calculate overall score as weighted average; return MatchResult with field-level details; getName() returns 'Exact Match'; requiresThreshold() returns false | | |
| TASK-012 | Create strategy `FuzzyMatchStrategy.php`; use Levenshtein distance algorithm; normalize strings (lowercase, trim, remove special chars); calculate similarity: (1 - distance/maxLength) * 100; calculate field scores using Levenshtein; weight by field importance; return MatchResult; getName() returns 'Fuzzy Match'; getRecommendedThreshold() returns 80.0 | | |
| TASK-013 | Create strategy `PhoneticMatchStrategy.php`; use Soundex or Metaphone algorithm for phonetic comparison; useful for names with spelling variations; calculate phonetic codes for string values; compare codes for exact match; field score 100 if phonetic match, 0 otherwise; weight by field importance; return MatchResult; getName() returns 'Phonetic Match'; getRecommendedThreshold() returns 70.0 | | |
| TASK-014 | Create strategy `HybridMatchStrategy.php`; combine multiple algorithms for robust matching; use exact match for emails/IDs, fuzzy for names, phonetic for names as fallback; calculate weighted average of algorithm scores; return MatchResult with details from each algorithm; getName() returns 'Hybrid Match'; getRecommendedThreshold() returns 85.0 | | |
| TASK-015 | Create factory `MatchingStrategyFactory.php` with method: make(MatchingAlgorithm $algorithm): MatchingStrategyContract; map algorithms to strategy classes; cache strategy instances; throw InvalidMatchingAlgorithmException if not supported | | |

### GOAL-003: Duplicate Detection Engine

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MDM-006 | Automated duplicate detection | | |
| EV-MDM-003 | DuplicateDetectedEvent dispatching | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-016 | Create service `DuplicateDetectionService.php` with methods: detectDuplicatesForEntity(MdmEntity $entity): Collection<MdmDuplicateMatch>, detectDuplicatesForType(EntityType $type, ?int $limit = null): Collection, compareEntities(MdmEntity $e1, MdmEntity $e2, MdmDuplicateRule $rule): MatchResult, findPotentialMatches(MdmEntity $entity, MdmDuplicateRule $rule): Collection<MdmEntity> (pre-filter candidates using index), recordMatch(MdmEntity $e1, MdmEntity $e2, MatchResult $result, MdmDuplicateRule $rule): MdmDuplicateMatch, getExistingMatches(MdmEntity $entity): Collection<MdmDuplicateMatch>; ensure detection < 500ms per entity per CON-004 | | |
| TASK-017 | Create action `DetectDuplicatesAction.php` using AsAction; inject DuplicateDetectionService, MatchingStrategyFactory; retrieve active duplicate rules for entity type; for each rule: get matching strategy; find potential candidates (optimize with indexing); compare entity against candidates; if match_score >= threshold: record MdmDuplicateMatch; dispatch DuplicateDetectedEvent (EV-MDM-003); return Collection of MdmDuplicateMatch | | |
| TASK-018 | Create action `CompareTwoEntitiesAction.php`; accept two entities and optional rule; if no rule, use default for entity type; get matching strategy; compare entities; return MatchResult without persisting; used for manual duplicate checking in UI | | |
| TASK-019 | Create action `BulkDetectDuplicatesAction.php` for async detection; queue DetectDuplicatesAction for entities; use job batching; filter entities not recently checked; report progress; return batch ID; used for periodic duplicate scans | | |
| TASK-020 | Create event `DuplicateDetectedEvent` with properties: MdmDuplicateMatch $match, MdmEntity $entity1, MdmEntity $entity2, float $matchScore, string $matchingAlgorithm; broadcastable on tenant-specific channel for real-time notifications | | |
| TASK-021 | Create listener `NotifyDuplicateDetectedListener.php` listening to DuplicateDetectedEvent; send notification to data stewards; log activity "Potential duplicate detected: {entity1} ~ {entity2} (score: {score})"; optionally auto-merge if rule.auto_merge and score >= threshold | | |

### GOAL-004: Golden Record Creation & Merge

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MDM-007 | Golden record creation from multiple sources | | |
| BR-MDM-002 | Preserve history when merging | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-022 | Create service `GoldenRecordService.php` with methods: createGoldenRecord(Collection $entities, ?array $fieldPriority = null): MdmEntity (merge multiple entities into golden), selectBestValue(string $field, Collection $values, ?array $priorities = null): mixed (select highest quality value), mergeAttributes(Collection $entities): Collection<MdmAttribute> (combine attributes from sources), calculateGoldenQuality(MdmEntity $golden): float (average quality of sources), promoteToGolden(MdmEntity $entity): MdmEntity (mark as golden record), demoteFromGolden(MdmEntity $entity): MdmEntity (remove golden flag) | | |
| TASK-023 | Create service `EntityMergeService.php` with methods: merge(MdmEntity $primary, MdmEntity $secondary, ?User $mergedBy = null): MdmEntity (merge secondary into primary), preparePreview(MdmEntity $primary, MdmEntity $secondary): array (preview merge result), validateMerge(MdmEntity $primary, MdmEntity $secondary): bool (check constraints), recordMerge(MdmEntity $primary, MdmEntity $secondary, User $mergedBy): void (create history), reverseMerge(int $mergeHistoryId): bool (undo merge); ensure transactional operations per CON-005; preserve all data per BR-MDM-002 | | |
| TASK-024 | Create migration `2025_01_01_000009_create_mdm_merge_history_table.php` with columns: id (BIGSERIAL), primary_entity_id (BIGINT FK mdm_entities), secondary_entity_id (BIGINT FK mdm_entities), golden_record_id (BIGINT FK mdm_entities nullable), merge_strategy (VARCHAR 50: manual/automatic/quality_based), merged_fields (JSONB: field => selected_value), rejected_fields (JSONB: fields not merged), merged_by (BIGINT FK users), merge_reason (TEXT nullable), is_reversed (BOOLEAN default false), reversed_at (TIMESTAMP nullable), reversed_by (BIGINT FK users nullable), timestamps; indexes: primary_entity_id, secondary_entity_id, golden_record_id, merged_by, is_reversed; supports BR-MDM-002 history preservation | | |
| TASK-025 | Create model `MdmMergeHistory.php` with fillable: primary_entity_id, secondary_entity_id, golden_record_id, merge_strategy, merged_fields, rejected_fields, merged_by, merge_reason, is_reversed, reversed_at, reversed_by; casts: merged_fields → array, rejected_fields → array, is_reversed → boolean, reversed_at → datetime; relationships: primaryEntity (belongsTo MdmEntity), secondaryEntity (belongsTo MdmEntity), goldenRecord (belongsTo MdmEntity nullable), mergedBy (belongsTo User), reversedBy (belongsTo User nullable); scopes: notReversed(), byEntity(int $entityId), recent(int $days = 30); computed: can_reverse (not is_reversed && within reversal window) | | |
| TASK-026 | Create action `MergeEntitiesAction.php` using AsAction; inject EntityMergeService, GoldenRecordService; validate merge constraints; begin DB transaction; mark secondary as merged (status = MERGED); set secondary.parent_entity_id = primary.id per BR-MDM-002; merge attributes (select best quality); update primary with merged data; if creating golden: set primary.is_golden_record = true; create MdmMergeHistory; update related MdmDuplicateMatch status = MERGED; create change history for both entities; dispatch EntitiesMergedEvent; commit transaction; log activity "Entities merged: {secondary} → {primary}"; return primary MdmEntity | | |
| TASK-027 | Create action `CreateGoldenRecordAction.php`; accept multiple entity IDs; validate all same entity_type; retrieve entities with attributes; use GoldenRecordService to create golden entity; set is_golden_record = true; link source entities via parent_entity_id; calculate golden quality score; create MdmChangeHistory; dispatch GoldenRecordCreatedEvent; return golden MdmEntity | | |
| TASK-028 | Create action `ReverseMergeAction.php`; retrieve MdmMergeHistory; check can_reverse (not already reversed, within window); begin DB transaction; restore secondary entity status to previous; clear secondary.parent_entity_id; revert primary entity to pre-merge state (use merged_fields to reconstruct); update MdmMergeHistory: is_reversed = true, reversed_at = now, reversed_by = user; create change history; dispatch MergeReversedEvent; commit transaction; return bool | | |
| TASK-029 | Create event `EntitiesMergedEvent` with properties: MdmEntity $primary, MdmEntity $secondary, ?MdmEntity $golden, User $mergedBy, array $mergedFields | | |
| TASK-030 | Create event `GoldenRecordCreatedEvent` with properties: MdmEntity $goldenRecord, Collection $sourceEntities, float $qualityScore | | |
| TASK-031 | Create event `MergeReversedEvent` with properties: MdmMergeHistory $mergeHistory, MdmEntity $restoredEntity, User $reversedBy | | |

### GOAL-005: API Controllers, Testing & Documentation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-MDM-006, FR-MDM-007 | Complete API for duplicate management | | |
| EV-MDM-003 | Event verification | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-032 | Create policy `MdmDuplicateRulePolicy.php` with methods: viewAny requiring 'view-duplicate-rules'; create/update/delete requiring 'manage-duplicate-rules'; enforce tenant scope | | |
| TASK-033 | Create policy `MdmDuplicateMatchPolicy.php` with methods: viewAny requiring 'view-duplicates'; confirm/reject/merge requiring 'manage-duplicates'; enforce tenant scope; check resolution_status allows action | | |
| TASK-034 | Create API controller `DuplicateRuleController.php` with routes: index (GET /api/v1/mdm/duplicate-rules), store (POST), show (GET /duplicate-rules/{id}), update (PATCH /duplicate-rules/{id}), destroy (DELETE /duplicate-rules/{id}), test (POST /duplicate-rules/{id}/test); authorize all actions | | |
| TASK-035 | Create API controller `DuplicateMatchController.php` with routes: index (GET /api/v1/mdm/duplicate-matches), show (GET /duplicate-matches/{id}), compare (POST /entities/compare), detectForEntity (POST /entities/{id}/detect-duplicates), bulkDetect (POST /entities/bulk-detect-duplicates), confirm (POST /duplicate-matches/{id}/confirm), reject (POST /duplicate-matches/{id}/reject), merge (POST /duplicate-matches/{id}/merge), mergePreview (POST /duplicate-matches/{id}/merge-preview); authorize actions | | |
| TASK-036 | Create API controller `GoldenRecordController.php` with routes: create (POST /api/v1/mdm/golden-records), promote (POST /entities/{id}/promote-golden), demote (POST /entities/{id}/demote-golden), goldenRecords (GET /golden-records), mergeHistory (GET /entities/{id}/merge-history), reverseMerge (POST /merge-history/{id}/reverse); authorize actions | | |
| TASK-037 | Create form request `CreateDuplicateRuleRequest.php` with validation: rule_name (required, string, max:255), entity_type (required, in), matching_fields (required, array, min:1), matching_fields.* (string, in:entity_fields), matching_algorithm (required, in), match_threshold (required, numeric, min:0, max:100), field_weights (required, array), field_weights.* (numeric, min:0, max:1), auto_merge (nullable, boolean); custom validation: field_weights sum to 1.0, matching_fields valid for entity_type | | |
| TASK-038 | Create form request `MergeEntitiesRequest.php` with validation: secondary_entity_id (required, exists:mdm_entities), merge_strategy (required, in:manual,automatic,quality_based), field_priority (nullable, array), merge_reason (nullable, string, max:1000), create_golden (nullable, boolean) | | |
| TASK-039 | Create form request `CompareEntitiesRequest.php` with validation: entity_1_id (required, exists:mdm_entities), entity_2_id (required, exists:mdm_entities, different:entity_1_id), rule_id (nullable, exists:mdm_duplicate_rules) | | |
| TASK-040 | Create API resource `MdmDuplicateRuleResource.php` with fields: id, tenant_id, rule_name, entity_type, matching_fields, matching_algorithm, match_threshold, field_weights, is_active, auto_merge, created_at, updated_at | | |
| TASK-041 | Create API resource `MdmDuplicateMatchResource.php` with fields: id, rule (nested minimal), entity1 (nested MdmEntityResource minimal), entity2 (nested MdmEntityResource minimal), match_score, match_details, resolution_status, can_auto_merge (computed), should_review (computed), resolved_by (nested UserResource minimal when resolved), resolved_at, created_at | | |
| TASK-042 | Create API resource `MdmMergeHistoryResource.php` with fields: id, primaryEntity (nested minimal), secondaryEntity (nested minimal), goldenRecord (nested minimal when present), merge_strategy, merged_fields, merged_by (nested UserResource), merge_reason, is_reversed, reversed_at, can_reverse (computed), created_at | | |
| TASK-043 | Write comprehensive unit tests for strategies: test ExactMatchStrategy with exact matches, test FuzzyMatchStrategy with Levenshtein, test PhoneticMatchStrategy with Soundex, test HybridMatchStrategy combining algorithms, test MatchingStrategyFactory creation | | |
| TASK-044 | Write comprehensive unit tests for services: test DuplicateDetectionService findPotentialMatches optimization, test GoldenRecordService selectBestValue logic, test EntityMergeService transactional merge, test EntityMergeService reverseMerge correctness | | |
| TASK-045 | Write comprehensive unit tests for actions: test DetectDuplicatesAction with various algorithms, test MergeEntitiesAction preserves history (BR-MDM-002), test CreateGoldenRecordAction from multiple sources, test ReverseMergeAction restores state | | |
| TASK-046 | Write feature tests for duplicate workflows: test create duplicate rule via API, test detect duplicates for entity, test compare two entities returns match score, test merge entities preserves history (BR-MDM-002), test DuplicateDetectedEvent dispatched (EV-MDM-003), test auto-merge when score >= threshold | | |
| TASK-047 | Write integration tests: test duplicate detection after entity creation, test golden record quality calculation, test merge updates related records, test reverse merge restores entities correctly | | |
| TASK-048 | Write performance tests: test duplicate detection < 500ms per entity (CON-004), test bulk detection for 1000 entities, test merge operation performance, test findPotentialMatches optimization with indexes | | |
| TASK-049 | Write acceptance tests: test complete duplicate detection lifecycle, test manual merge workflow with preview, test golden record creation from 3+ sources, test merge history preservation (BR-MDM-002), test reverse merge functionality, test DuplicateDetectedEvent emitted (EV-MDM-003), test various matching algorithms accuracy | | |
| TASK-050 | Set up Pest configuration for duplicate tests; configure duplicate rule factories, match factories, merge history factories; seed test entities with intentional duplicates | | |
| TASK-051 | Achieve minimum 80% code coverage for duplicate module; run `./vendor/bin/pest --coverage --min=80` | | |
| TASK-052 | Create API documentation: document duplicate rule endpoints, document matching endpoints, document golden record endpoints, document matching algorithms and thresholds, document merge operations and reversal | | |
| TASK-053 | Create user guide: how to configure duplicate rules, understanding matching algorithms, reviewing potential duplicates, merging entities, creating golden records, reversing merges | | |
| TASK-054 | Create technical documentation: matching algorithm implementations, duplicate detection optimization strategies, golden record best practices, merge transaction handling, performance tuning for bulk detection | | |
| TASK-055 | Create admin guide: configuring duplicate detection rules, setting match thresholds, enabling auto-merge, monitoring duplicate matches, managing golden records, troubleshooting detection issues | | |
| TASK-056 | Update package README with duplicate features: detection algorithms, matching rules, golden records, merge workflows, event notifications | | |
| TASK-057 | Validate acceptance criteria: duplicate detection functional, matching algorithms accurate, golden records created correctly, merges preserve history (BR-MDM-002), events dispatched (EV-MDM-003), detection < 500ms (CON-004) | | |
| TASK-058 | Conduct code review: verify FR-MDM-006 implementation, verify FR-MDM-007 golden records, verify BR-MDM-002 history preservation, verify EV-MDM-003 event dispatching, verify performance < 500ms | | |
| TASK-059 | Run full test suite for duplicate module; verify all tests pass; verify event dispatching works; verify merge transactions rollback on failure | | |
| TASK-060 | Deploy to staging; test duplicate detection with real data; test merge operations; verify golden record quality; test reverse merge; verify performance < 500ms per entity | | |
| TASK-061 | Create seeder `MdmDuplicateRuleSeeder.php` with sample rules: customer name+email fuzzy match (threshold 80), vendor name exact match, item SKU exact match, employee name+DOB phonetic match | | |
| TASK-062 | Create seeder `MdmDuplicateDataSeeder.php` with intentional duplicates for testing: similar customer names (John Smith / Jon Smith), vendor variations (Acme Corp / ACME Corporation), items with typos | | |
| TASK-063 | Create console command `php artisan mdm:detect-duplicates` for bulk detection; support --entity-type flag, --algorithm flag, --dry-run flag; report detection statistics | | |
| TASK-064 | Create console command `php artisan mdm:auto-merge-duplicates` for automated merging; only merge matches with auto_merge enabled and score >= threshold; require --confirm flag; report merge statistics | | |

## 3. Alternatives

- **ALT-001**: Use external deduplication service - rejected; custom implementation provides MDM-specific control and cost savings
- **ALT-002**: Always auto-merge duplicates - rejected; requires manual review for high-impact entities per BR-MDM-002
- **ALT-003**: Delete duplicate entities instead of merging - rejected; violates BR-MDM-002 requirement to preserve history
- **ALT-004**: Single matching algorithm only - rejected; different entity types require different algorithms
- **ALT-005**: Golden record from highest quality source only - rejected; merge from multiple sources provides more complete data
- **ALT-006**: No merge reversal capability - rejected; must support error correction per CON-005

## 4. Dependencies

### Mandatory Dependencies
- **DEP-001**: PLAN01 (Master Data Entity Foundation) - MdmEntity model
- **DEP-002**: PLAN02 (Data Quality & Validation) - Quality scoring for golden records
- **DEP-003**: SUB01 (Multi-Tenancy) - Tenant isolation for duplicate rules
- **DEP-004**: SUB02 (Authentication) - User permissions for merge operations
- **DEP-005**: SUB03 (Audit Logging) - Activity tracking for merges
- **DEP-006**: Laravel Queue - Async bulk duplicate detection

### Optional Dependencies
- **DEP-007**: Machine learning services - For advanced duplicate detection
- **DEP-008**: External data matching services - For enhanced accuracy

### Package Dependencies
- **DEP-009**: lorisleiva/laravel-actions ^2.0 - Action pattern
- **DEP-010**: Laravel Cache - Matching result caching
- **DEP-011**: Laravel Database - Transactional merges

## 5. Files

### Models & Enums
- `packages/mdm/src/Models/MdmDuplicateRule.php` - Duplicate detection rule model
- `packages/mdm/src/Models/MdmDuplicateMatch.php` - Duplicate match model
- `packages/mdm/src/Models/MdmMergeHistory.php` - Merge history model
- `packages/mdm/src/Enums/MatchingAlgorithm.php` - Algorithm enumeration
- `packages/mdm/src/Enums/ResolutionStatus.php` - Resolution status enumeration

### Contracts & DTOs
- `packages/mdm/src/Contracts/MatchingStrategyContract.php` - Matching strategy interface
- `packages/mdm/src/DataTransferObjects/MatchResult.php` - Match result DTO

### Matching Strategies
- `packages/mdm/src/Strategies/ExactMatchStrategy.php` - Exact string matching
- `packages/mdm/src/Strategies/FuzzyMatchStrategy.php` - Levenshtein distance
- `packages/mdm/src/Strategies/PhoneticMatchStrategy.php` - Soundex/Metaphone
- `packages/mdm/src/Strategies/HybridMatchStrategy.php` - Combined algorithms
- `packages/mdm/src/Strategies/MatchingStrategyFactory.php` - Strategy factory

### Services
- `packages/mdm/src/Services/DuplicateDetectionService.php` - Detection engine
- `packages/mdm/src/Services/GoldenRecordService.php` - Golden record management
- `packages/mdm/src/Services/EntityMergeService.php` - Merge operations

### Actions
- `packages/mdm/src/Actions/DetectDuplicatesAction.php` - Detect duplicates
- `packages/mdm/src/Actions/CompareTwoEntitiesAction.php` - Compare entities
- `packages/mdm/src/Actions/BulkDetectDuplicatesAction.php` - Bulk detection
- `packages/mdm/src/Actions/MergeEntitiesAction.php` - Merge entities
- `packages/mdm/src/Actions/CreateGoldenRecordAction.php` - Create golden record
- `packages/mdm/src/Actions/ReverseMergeAction.php` - Reverse merge

### Events & Listeners
- `packages/mdm/src/Events/DuplicateDetectedEvent.php` (EV-MDM-003)
- `packages/mdm/src/Events/EntitiesMergedEvent.php`
- `packages/mdm/src/Events/GoldenRecordCreatedEvent.php`
- `packages/mdm/src/Events/MergeReversedEvent.php`
- `packages/mdm/src/Listeners/NotifyDuplicateDetectedListener.php`

### Controllers & Requests
- `packages/mdm/src/Http/Controllers/DuplicateRuleController.php` - Rule API
- `packages/mdm/src/Http/Controllers/DuplicateMatchController.php` - Match API
- `packages/mdm/src/Http/Controllers/GoldenRecordController.php` - Golden record API
- `packages/mdm/src/Http/Requests/CreateDuplicateRuleRequest.php` - Create validation
- `packages/mdm/src/Http/Requests/MergeEntitiesRequest.php` - Merge validation
- `packages/mdm/src/Http/Requests/CompareEntitiesRequest.php` - Compare validation

### Resources & Policies
- `packages/mdm/src/Http/Resources/MdmDuplicateRuleResource.php` - Rule transformation
- `packages/mdm/src/Http/Resources/MdmDuplicateMatchResource.php` - Match transformation
- `packages/mdm/src/Http/Resources/MdmMergeHistoryResource.php` - History transformation
- `packages/mdm/src/Policies/MdmDuplicateRulePolicy.php` - Rule authorization
- `packages/mdm/src/Policies/MdmDuplicateMatchPolicy.php` - Match authorization

### Database & Commands
- `packages/mdm/database/migrations/2025_01_01_000007_create_mdm_duplicate_rules_table.php`
- `packages/mdm/database/migrations/2025_01_01_000008_create_mdm_duplicate_matches_table.php`
- `packages/mdm/database/migrations/2025_01_01_000009_create_mdm_merge_history_table.php`
- `packages/mdm/database/factories/MdmDuplicateRuleFactory.php`
- `packages/mdm/database/factories/MdmDuplicateMatchFactory.php`
- `packages/mdm/database/seeders/MdmDuplicateRuleSeeder.php`
- `packages/mdm/database/seeders/MdmDuplicateDataSeeder.php`
- `packages/mdm/src/Console/Commands/DetectDuplicatesCommand.php`
- `packages/mdm/src/Console/Commands/AutoMergeDuplicatesCommand.php`

### Tests
- `packages/mdm/tests/Unit/Strategies/*Test.php` - Strategy unit tests
- `packages/mdm/tests/Unit/Services/DuplicateDetectionServiceTest.php`
- `packages/mdm/tests/Unit/Services/GoldenRecordServiceTest.php`
- `packages/mdm/tests/Unit/Services/EntityMergeServiceTest.php`
- `packages/mdm/tests/Feature/DuplicateDetectionTest.php`
- `packages/mdm/tests/Feature/EntityMergeTest.php`
- `packages/mdm/tests/Integration/DuplicateMergeIntegrationTest.php`
- `packages/mdm/tests/Performance/DuplicateDetectionPerformanceTest.php`

## 6. Testing

### Unit Tests (15 tests)
- **TEST-001**: ExactMatchStrategy with exact/non-exact matches
- **TEST-002**: FuzzyMatchStrategy Levenshtein calculation
- **TEST-003**: PhoneticMatchStrategy Soundex comparison
- **TEST-004**: HybridMatchStrategy algorithm combination
- **TEST-005**: MatchingStrategyFactory creation and caching
- **TEST-006**: DuplicateDetectionService findPotentialMatches
- **TEST-007**: GoldenRecordService selectBestValue logic
- **TEST-008**: GoldenRecordService mergeAttributes
- **TEST-009**: EntityMergeService transactional merge
- **TEST-010**: EntityMergeService reverseMerge correctness

### Feature Tests (12 tests)
- **TEST-011**: Create duplicate rule via API
- **TEST-012**: Detect duplicates for entity
- **TEST-013**: Compare two entities returns match score
- **TEST-014**: Merge entities preserves history (BR-MDM-002)
- **TEST-015**: DuplicateDetectedEvent dispatched (EV-MDM-003)
- **TEST-016**: Auto-merge when score >= threshold
- **TEST-017**: Golden record creation from multiple sources
- **TEST-018**: Reverse merge functionality

### Integration Tests (8 tests)
- **TEST-019**: Duplicate detection after entity creation
- **TEST-020**: Golden record quality calculation
- **TEST-021**: Merge updates related duplicate matches
- **TEST-022**: Reverse merge restores entities correctly

### Performance Tests (5 tests)
- **TEST-023**: Duplicate detection < 500ms per entity (CON-004)
- **TEST-024**: Bulk detection for 1000 entities
- **TEST-025**: Merge operation performance
- **TEST-026**: FindPotentialMatches optimization with indexes

### Acceptance Tests (9 tests)
- **TEST-027**: Complete duplicate detection lifecycle
- **TEST-028**: Manual merge workflow with preview
- **TEST-029**: Golden record creation from 3+ sources
- **TEST-030**: Merge history preservation (BR-MDM-002)
- **TEST-031**: Reverse merge functionality complete
- **TEST-032**: DuplicateDetectedEvent emitted (EV-MDM-003)
- **TEST-033**: Various matching algorithms accuracy
- **TEST-034**: Auto-merge based on threshold
- **TEST-035**: Golden record selection logic

**Total Test Coverage:** 49 tests (15 unit + 12 feature + 8 integration + 5 performance + 9 acceptance)

## 7. Risks & Assumptions

### Risks
- **RISK-001**: False positives in duplicate detection - Mitigation: adjustable thresholds, manual review workflow
- **RISK-002**: Performance degradation with large entity counts - Mitigation: indexing, candidate pre-filtering, caching
- **RISK-003**: Merge reversal may not be perfect - Mitigation: comprehensive merge history, validation before reversal
- **RISK-004**: Conflicting field values in golden records - Mitigation: configurable field priority, quality-based selection
- **RISK-005**: Auto-merge may incorrectly merge entities - Mitigation: high threshold requirement, notification system

### Assumptions
- **ASSUMPTION-001**: Duplicate detection runs asynchronously for bulk operations
- **ASSUMPTION-002**: Match thresholds 80-100% appropriate for most use cases
- **ASSUMPTION-003**: Merge operations complete within 5 seconds
- **ASSUMPTION-004**: Golden records use highest quality source data by default
- **ASSUMPTION-005**: Merge history retained indefinitely (no automatic archival)
- **ASSUMPTION-006**: Reverse merge supported within 30 days of merge
- **ASSUMPTION-007**: Levenshtein distance sufficient for fuzzy matching

## 8. KIV for Future Implementations

- **KIV-001**: Machine learning for adaptive matching thresholds
- **KIV-002**: Real-time duplicate detection on entity creation
- **KIV-003**: Duplicate detection across entity types (e.g., customer vs vendor)
- **KIV-004**: Bulk merge operations with conflict resolution rules
- **KIV-005**: Visual merge preview with field-by-field comparison
- **KIV-006**: Automated entity splitting (reverse of merge for incorrect merges)
- **KIV-007**: Integration with external data matching services
- **KIV-008**: Advanced phonetic algorithms (Double Metaphone, NYSIIS)
- **KIV-009**: Duplicate detection API for external systems
- **KIV-010**: Merge confidence scoring and recommendations
- **KIV-011**: Partial golden records (only merge specific fields)
- **KIV-012**: Scheduled duplicate detection jobs with reporting

## 9. Related PRD / Further Reading

- **Master PRD**: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD**: [../prd/prd-01/PRD01-SUB18-MASTER-DATA-MANAGEMENT.md](../prd/prd-01/PRD01-SUB18-MASTER-DATA-MANAGEMENT.md)
- **Related Plans**:
  - PRD01-SUB18-PLAN01 (Master Data Entity Foundation) - Base entity models
  - PRD01-SUB18-PLAN02 (Data Quality & Validation) - Quality scoring for golden records
  - PRD01-SUB18-PLAN04 (Data Stewardship & Bulk Operations) - Bulk processing integration
- **Integration Documentation**:
  - SUB01 (Multi-Tenancy) - Tenant isolation
  - SUB02 (Authentication) - User permissions
  - SUB03 (Audit Logging) - Activity tracking
- **Architecture Documentation**: [../../architecture/](../../architecture/)
- **Coding Guidelines**: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
