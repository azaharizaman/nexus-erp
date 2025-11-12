---
plan: Vendor Performance Tracking & Reporting
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, purchasing, vendor-performance, analytics, reporting, metrics, business-intelligence, procurement]
---

# PRD01-SUB16-PLAN04: Implement Vendor Performance Tracking & Reporting

![Status: Planned](https://img.shields.io/badge/Status-Planned-blue)

This implementation plan covers vendor performance evaluation, rating systems, automated metrics calculation, and reporting analytics. This plan enables data-driven vendor management by tracking delivery performance, quality metrics, and pricing competitiveness to support strategic sourcing decisions.

## 1. Requirements & Constraints

### Functional Requirements
- **FR-PO-008**: Track and rate vendor performance (on-time delivery, quality, pricing)

### Data Requirements
- **DR-PO-003**: Record vendor evaluation data for performance analysis

### Event Requirements
- **EV-PO-004**: Emit VendorRatingUpdatedEvent when vendor rating changes

### Constraints
- **CON-001**: Depends on SUB01 (Multi-Tenancy) for tenant isolation
- **CON-002**: Depends on SUB02 (Authentication) for user access control
- **CON-003**: Depends on SUB03 (Audit Logging) for activity tracking
- **CON-004**: Depends on PLAN01 for vendor data
- **CON-005**: Depends on PLAN02 for PO data
- **CON-006**: Depends on PLAN03 for GRN data

### Guidelines
- **GUD-001**: Follow repository pattern for all data access
- **GUD-002**: Use Laravel Actions for all business logic
- **GUD-003**: Calculate performance metrics automatically from transactional data
- **GUD-004**: Log all rating changes and evaluations
- **GUD-005**: Provide real-time and historical performance analytics

### Patterns
- **PAT-001**: Observer pattern for automatic metric updates
- **PAT-002**: Repository pattern with contracts for data access
- **PAT-003**: Strategy pattern for different performance calculation methods
- **PAT-004**: Aggregation pattern for performance reporting

## 2. Implementation Steps

### GOAL-001: Vendor Performance Data Model & Tracking

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-PO-008 | Implement vendor performance tracking | | |
| DR-PO-003 | Record vendor evaluation data | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000014_create_vendor_performance_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), vendor_id (BIGINT FK vendors), evaluation_period_start (DATE), evaluation_period_end (DATE), total_pos (INT default 0), total_po_value (DECIMAL 15,2 default 0), on_time_deliveries (INT default 0), late_deliveries (INT default 0), on_time_delivery_rate (DECIMAL 5,2 default 0), quality_accepted (INT default 0), quality_rejected (INT default 0), quality_pass_rate (DECIMAL 5,2 default 0), price_competitiveness_score (DECIMAL 3,2 default 0: 1-5 scale), response_time_avg_days (DECIMAL 5,2 default 0), overall_rating (VARCHAR 20: excellent/good/average/poor/unrated), last_calculated_at (TIMESTAMP nullable), notes (TEXT nullable), timestamps; indexes: tenant_id, vendor_id, evaluation_period_start, evaluation_period_end, overall_rating; unique: tenant_id + vendor_id + evaluation_period_start + evaluation_period_end | | |
| TASK-002 | Create migration `2025_01_01_000015_create_vendor_evaluations_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), vendor_id (BIGINT FK vendors), evaluated_by (BIGINT FK users), evaluation_date (DATE), on_time_delivery_score (INT: 1-5), quality_score (INT: 1-5), price_competitiveness_score (INT: 1-5), communication_score (INT: 1-5), overall_score (DECIMAL 3,2), comments (TEXT nullable), timestamps; indexes: tenant_id, vendor_id, evaluated_by, evaluation_date | | |
| TASK-003 | Create migration `2025_01_01_000016_create_delivery_performance_logs_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), po_id (BIGINT FK purchase_orders), grn_id (BIGINT FK goods_receipt_notes), vendor_id (BIGINT FK vendors), promised_delivery_date (DATE), actual_delivery_date (DATE), days_late (INT default 0), is_on_time (BOOLEAN), line_items_count (INT), accepted_items_count (INT), rejected_items_count (INT), timestamps; indexes: tenant_id, vendor_id, po_id, grn_id, promised_delivery_date, actual_delivery_date, is_on_time | | |
| TASK-004 | Create enum `VendorOverallRating` with values: EXCELLENT (>= 4.5), GOOD (>= 3.5), AVERAGE (>= 2.5), POOR (>= 1.5), UNRATED | | |
| TASK-005 | Create model `VendorPerformance.php` with traits: BelongsToTenant; fillable: vendor_id, evaluation_period_start, evaluation_period_end, total_pos, total_po_value, on_time_deliveries, late_deliveries, on_time_delivery_rate, quality_accepted, quality_rejected, quality_pass_rate, price_competitiveness_score, response_time_avg_days, overall_rating, notes; casts: evaluation_period_start → date, evaluation_period_end → date, total_po_value → float, on_time_delivery_rate → float, quality_pass_rate → float, price_competitiveness_score → float, response_time_avg_days → float, overall_rating → VendorOverallRating enum, last_calculated_at → datetime; relationships: vendor (belongsTo); scopes: byVendor(int $vendorId), byPeriod(Carbon $start, Carbon $end), byRating(VendorOverallRating $rating), excellent(), good(), poor(); computed: total_deliveries (on_time_deliveries + late_deliveries), total_quality_inspections (quality_accepted + quality_rejected), average_po_value (total_po_value / total_pos if total_pos > 0) | | |
| TASK-006 | Create model `VendorEvaluation.php` with traits: BelongsToTenant; fillable: vendor_id, evaluated_by, evaluation_date, on_time_delivery_score, quality_score, price_competitiveness_score, communication_score, overall_score, comments; casts: evaluation_date → date, on_time_delivery_score → int, quality_score → int, price_competitiveness_score → int, communication_score → int, overall_score → float; relationships: vendor (belongsTo), evaluator (belongsTo User); scopes: byVendor(int $vendorId), byEvaluator(int $userId), byDateRange(Carbon $from, Carbon $to) | | |
| TASK-007 | Create model `DeliveryPerformanceLog.php` with traits: BelongsToTenant; fillable: po_id, grn_id, vendor_id, promised_delivery_date, actual_delivery_date, days_late, is_on_time, line_items_count, accepted_items_count, rejected_items_count; casts: promised_delivery_date → date, actual_delivery_date → date, days_late → int, is_on_time → boolean, line_items_count → int, accepted_items_count → int, rejected_items_count → int; relationships: purchaseOrder (belongsTo), grn (belongsTo GoodsReceiptNote), vendor (belongsTo); scopes: onTime(), late(), byVendor(int $vendorId), byDateRange(Carbon $from, Carbon $to) | | |
| TASK-008 | Create factory `VendorPerformanceFactory.php` with states: excellent(), good(), average(), poor(), forPeriod(Carbon $start, Carbon $end) | | |
| TASK-009 | Create factory `VendorEvaluationFactory.php` with states: highScore(), lowScore(), forVendor(int $vendorId) | | |
| TASK-010 | Create factory `DeliveryPerformanceLogFactory.php` with states: onTime(), late(int $days), forVendor(int $vendorId) | | |
| TASK-011 | Create contract `VendorPerformanceRepositoryContract.php` with methods: findById(int $id): ?VendorPerformance, findByVendorAndPeriod(int $vendorId, Carbon $start, Carbon $end): ?VendorPerformance, create(array $data): VendorPerformance, update(VendorPerformance $perf, array $data): VendorPerformance, getByVendor(int $vendorId): Collection, getByRating(VendorOverallRating $rating): Collection, getTopPerformers(int $limit = 10): Collection, getPoorPerformers(int $limit = 10): Collection | | |
| TASK-012 | Implement `VendorPerformanceRepository.php` with eager loading for vendor; implement filters: rating, period, vendor_id; cache performance metrics with 1-hour TTL | | |
| TASK-013 | Update Vendor model from PLAN01: add relationship: performanceRecords (hasMany VendorPerformance), latestPerformance (hasOne VendorPerformance latest), evaluations (hasMany VendorEvaluation), deliveryLogs (hasMany DeliveryPerformanceLog); add computed property: current_rating (from latest performance record) | | |

### GOAL-002: Automated Performance Metrics Calculation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-PO-008 | Calculate performance metrics from transactional data | | |
| EV-PO-004 | Emit VendorRatingUpdatedEvent | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-014 | Create service `VendorPerformanceService.php` with methods: calculatePerformanceForPeriod(Vendor $vendor, Carbon $start, Carbon $end): VendorPerformance (aggregates PO/GRN data), calculateOnTimeDeliveryRate(Vendor $vendor, Carbon $start, Carbon $end): float, calculateQualityPassRate(Vendor $vendor, Carbon $start, Carbon $end): float, calculatePriceCompetitiveness(Vendor $vendor): float (compare against market average or other vendors), calculateOverallRating(VendorPerformance $perf): VendorOverallRating (weighted average of scores), updateAllVendorPerformance(): void (batch calculate for all vendors) | | |
| TASK-015 | Create service `DeliveryPerformanceService.php` with methods: logDeliveryPerformance(GoodsReceiptNote $grn): DeliveryPerformanceLog, calculateDaysLate(Carbon $promised, Carbon $actual): int, isOnTime(Carbon $promised, Carbon $actual): bool (considering grace period), getVendorDeliveryTrend(Vendor $vendor, int $months = 6): array | | |
| TASK-016 | Create service `PerformanceAggregationService.php` with methods: aggregatePOData(Vendor $vendor, Carbon $start, Carbon $end): array (total POs, total value, average value), aggregateGRNData(Vendor $vendor, Carbon $start, Carbon $end): array (on-time deliveries, quality metrics), aggregateEvaluationScores(Vendor $vendor, Carbon $start, Carbon $end): array (manual evaluation scores) | | |
| TASK-017 | Create action `CalculateVendorPerformanceAction.php` using AsAction; inject VendorPerformanceService, PerformanceAggregationService, VendorPerformanceRepositoryContract; calculate or retrieve performance for period; aggregate PO data (total POs, value); aggregate GRN data (delivery performance, quality); calculate rates: on_time_delivery_rate = on_time / total * 100, quality_pass_rate = accepted / total * 100; calculate price competitiveness; calculate overall rating based on weighted scores; create or update VendorPerformance record; log activity "Vendor performance calculated for {period}"; dispatch VendorPerformanceCalculatedEvent; return VendorPerformance | | |
| TASK-018 | Create action `UpdateVendorRatingAction.php`; validate new rating value (1-5 scale or enum); update vendor rating in vendor record; log activity "Vendor rating updated: {old} → {new}"; dispatch VendorRatingUpdatedEvent (EV-PO-004); return Vendor | | |
| TASK-019 | Create action `LogDeliveryPerformanceAction.php`; triggered when GRN is posted; extract promised_delivery_date from PO; extract actual_delivery_date from GRN; calculate days_late and is_on_time; count line items, accepted, rejected; create DeliveryPerformanceLog; log activity "Delivery performance logged for PO {po_number}"; return DeliveryPerformanceLog | | |
| TASK-020 | Create action `RecalculateAllVendorPerformanceAction.php` (scheduled job); iterate all active vendors; calculate performance for current period (e.g., last 90 days); update performance records; dispatch batch VendorPerformanceBatchUpdatedEvent; return int (count of vendors updated) | | |
| TASK-021 | Create event `VendorPerformanceCalculatedEvent` with properties: VendorPerformance $performance, Vendor $vendor | | |
| TASK-022 | Create event `VendorRatingUpdatedEvent` with properties: Vendor $vendor, VendorOverallRating $oldRating, VendorOverallRating $newRating, User $updatedBy (EV-PO-004) | | |
| TASK-023 | Create event `VendorPerformanceBatchUpdatedEvent` with properties: int $vendorsUpdated, Carbon $calculatedAt | | |
| TASK-024 | Create listener `UpdateVendorRatingOnPerformanceChangeListener.php` listening to VendorPerformanceCalculatedEvent; if overall_rating changed: update vendor rating; dispatch VendorRatingUpdatedEvent (EV-PO-004) | | |
| TASK-025 | Create listener `LogDeliveryPerformanceOnGRNPostedListener.php` listening to GoodsReceivedEvent (from PLAN03); log delivery performance automatically; update vendor performance metrics if threshold reached (e.g., every 10 deliveries) | | |

### GOAL-003: Manual Vendor Evaluation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-PO-008 | Support manual vendor evaluation by users | | |
| DR-PO-003 | Record evaluation data | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-026 | Create action `EvaluateVendorAction.php` using AsAction; validate evaluation scores (1-5 range); validate evaluator has 'evaluate-vendors' permission; calculate overall_score = (on_time_delivery_score + quality_score + price_competitiveness_score + communication_score) / 4; create VendorEvaluation record; log activity "Vendor evaluated by {user}"; dispatch VendorEvaluatedEvent; return VendorEvaluation | | |
| TASK-027 | Create action `GetVendorEvaluationHistoryAction.php`; retrieve all evaluations for vendor; calculate average scores per criterion; return Collection with statistics | | |
| TASK-028 | Create action `GetVendorEvaluationSummaryAction.php`; retrieve evaluations for period; aggregate scores; calculate trends (improving/declining); return array with summary statistics | | |
| TASK-029 | Create event `VendorEvaluatedEvent` with properties: VendorEvaluation $evaluation, Vendor $vendor, User $evaluator | | |
| TASK-030 | Create listener `UpdatePerformanceOnEvaluationListener.php` listening to VendorEvaluatedEvent; trigger performance recalculation to include new evaluation data | | |

### GOAL-004: Performance Reporting & Analytics

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-PO-008 | Provide vendor performance reports and analytics | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-031 | Create service `VendorReportingService.php` with methods: getVendorScorecard(Vendor $vendor, Carbon $start, Carbon $end): array (comprehensive performance report), getTopPerformingVendors(int $limit = 10, ?string $category = null): Collection, getPoorPerformingVendors(int $limit = 10): Collection, getVendorComparison(array $vendorIds, Carbon $start, Carbon $end): array, getVendorTrend(Vendor $vendor, int $months = 12): array (monthly performance trend), getSpendAnalysisByVendor(Carbon $start, Carbon $end): array | | |
| TASK-032 | Create service `PerformanceMetricsService.php` with methods: calculateKPIs(Carbon $start, Carbon $end): array (overall procurement KPIs), getOnTimeDeliveryKPI(): float (tenant-wide), getQualityPassRateKPI(): float, getAveragePOValue(): float, getVendorConcentrationRisk(): array (spend concentration by vendor) | | |
| TASK-033 | Create action `GenerateVendorScorecardAction.php`; retrieve vendor performance data; aggregate delivery metrics; aggregate quality metrics; aggregate financial data (total spend, average PO value); calculate scores and ratings; format as structured report; return array with scorecard data | | |
| TASK-034 | Create action `GenerateTopPerformersReportAction.php`; retrieve vendors by rating (excellent/good); sort by overall_rating DESC; include key metrics (on-time rate, quality rate, total spend); return Collection | | |
| TASK-035 | Create action `GenerateSpendAnalysisReportAction.php`; aggregate total spend by vendor for period; calculate spend percentage per vendor; identify top 10 vendors by spend; calculate concentration risk (% spent with top vendor); return array with analysis | | |
| TASK-036 | Create action `GeneratePerformanceTrendReportAction.php`; retrieve vendor performance history (monthly); calculate trend indicators (improving/stable/declining); generate chart data (labels, datasets); return array for visualization | | |
| TASK-037 | Create action `ExportVendorPerformanceDataAction.php`; retrieve performance data; format as CSV or Excel; include: vendor name, rating, on-time rate, quality rate, total POs, total spend; return file path or download response | | |

### GOAL-005: API Controllers, Testing & Documentation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| All FRs | Complete test coverage for vendor performance | | |
| EV-PO-004 | Verify event dispatching | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-038 | Create policy `VendorPerformancePolicy.php` requiring 'view-vendor-performance' permission for viewing; 'evaluate-vendors' for manual evaluation; 'manage-vendor-performance' for recalculation; enforce tenant scope | | |
| TASK-039 | Create API controller `VendorPerformanceController.php` with routes: index (GET /purchasing/vendor-performance), show (GET /vendor-performance/{vendorId}), calculate (POST /vendor-performance/{vendorId}/calculate), recalculateAll (POST /vendor-performance/recalculate-all), scorecard (GET /vendor-performance/{vendorId}/scorecard), topPerformers (GET /vendor-performance/top-performers), poorPerformers (GET /vendor-performance/poor-performers), trend (GET /vendor-performance/{vendorId}/trend); authorize actions | | |
| TASK-040 | Create API controller `VendorEvaluationController.php` with routes: index (GET /purchasing/vendor-evaluations), store (POST /vendor-evaluations), show (GET /vendor-evaluations/{id}), history (GET /vendor-evaluations/vendor/{vendorId}), summary (GET /vendor-evaluations/vendor/{vendorId}/summary); authorize actions | | |
| TASK-041 | Create API controller `PerformanceReportController.php` with routes: spendAnalysis (GET /purchasing/reports/spend-analysis), performanceTrend (GET /reports/performance-trend), vendorComparison (POST /reports/vendor-comparison), exportData (GET /reports/export); authorize actions; support date range filters | | |
| TASK-042 | Create form request `EvaluateVendorRequest.php` with validation: vendor_id (required, exists:vendors), evaluation_date (required, date, before_or_equal:today), on_time_delivery_score (required, integer, min:1, max:5), quality_score (required, integer, min:1, max:5), price_competitiveness_score (required, integer, min:1, max:5), communication_score (required, integer, min:1, max:5), comments (nullable, string, max:1000) | | |
| TASK-043 | Create form request `CalculatePerformanceRequest.php` with validation: period_start (required, date), period_end (required, date, after:period_start), recalculate_rating (nullable, boolean) | | |
| TASK-044 | Create form request `VendorComparisonRequest.php` with validation: vendor_ids (required, array, min:2, max:10), vendor_ids.* (exists:vendors), period_start (required, date), period_end (required, date, after:period_start) | | |
| TASK-045 | Create API resource `VendorPerformanceResource.php` with fields: id, vendor (nested VendorResource), evaluation_period_start, evaluation_period_end, total_pos, total_po_value, average_po_value, on_time_delivery_rate, quality_pass_rate, price_competitiveness_score, response_time_avg_days, overall_rating, last_calculated_at | | |
| TASK-046 | Create API resource `VendorEvaluationResource.php` with fields: id, vendor (nested), evaluator (nested UserResource), evaluation_date, on_time_delivery_score, quality_score, price_competitiveness_score, communication_score, overall_score, comments | | |
| TASK-047 | Create API resource `DeliveryPerformanceLogResource.php` with fields: purchaseOrder (nested), grn (nested), vendor (nested), promised_delivery_date, actual_delivery_date, days_late, is_on_time, line_items_count, accepted_items_count, rejected_items_count | | |
| TASK-048 | Create API resource `VendorScorecardResource.php` with fields: vendor (nested), period, delivery_performance (nested object), quality_performance (nested object), financial_summary (nested object), overall_rating, recommendations (array) | | |
| TASK-049 | Write comprehensive unit tests for models: test VendorPerformance rating calculation, test DeliveryPerformanceLog is_on_time logic, test VendorEvaluation overall_score calculation | | |
| TASK-050 | Write comprehensive unit tests for services: test VendorPerformanceService metrics calculation, test DeliveryPerformanceService days_late calculation, test PerformanceAggregationService data aggregation, test PerformanceMetricsService KPI calculations | | |
| TASK-051 | Write comprehensive unit tests for actions: test CalculateVendorPerformanceAction with various data, test EvaluateVendorAction with validation, test report generation actions | | |
| TASK-052 | Write feature tests for performance workflows: test vendor performance calculation (automatic), test manual vendor evaluation, test rating update event dispatched (EV-PO-004), test delivery performance logging on GRN post | | |
| TASK-053 | Write integration tests: test performance calculation uses PO/GRN data correctly, test rating update triggers events, test scheduled performance recalculation job | | |
| TASK-054 | Write acceptance tests: test vendor scorecard generation, test top performers report, test spend analysis report, test performance trend visualization data, test vendor comparison report | | |
| TASK-055 | Set up Pest configuration for performance tests; configure database transactions, vendor/PO/GRN factories | | |
| TASK-056 | Achieve minimum 80% code coverage for performance module; run `./vendor/bin/pest --coverage --min=80` | | |
| TASK-057 | Create API documentation using OpenAPI 3.0: document all performance endpoints, document evaluation API, document reporting endpoints, document event dispatching (EV-PO-004) | | |
| TASK-058 | Create user guide: vendor evaluation procedures, interpreting performance metrics, using scorecards, generating reports, understanding ratings | | |
| TASK-059 | Create technical documentation: performance calculation algorithms, rating methodology, KPI definitions, data aggregation logic, scheduled job configuration | | |
| TASK-060 | Create admin guide: configuring evaluation criteria, setting performance thresholds, scheduling recalculations, managing vendor ratings, troubleshooting calculation issues | | |
| TASK-061 | Update package README with performance features: automated metrics calculation, manual evaluation, performance reports, vendor scorecards, rating system | | |
| TASK-062 | Validate all acceptance criteria: performance calculation functional, rating updates dispatch events (EV-PO-004), evaluations recorded (DR-PO-003), reports generated correctly | | |
| TASK-063 | Conduct code review: verify FR-PO-008 implementation, verify event dispatching (EV-PO-004), verify data recording (DR-PO-003), verify calculation accuracy | | |
| TASK-064 | Run full test suite for performance module; verify all tests pass; verify event listeners work correctly; verify scheduled jobs execute | | |
| TASK-065 | Deploy to staging; test performance calculation with real PO/GRN data; test manual evaluations; test report generation; verify event dispatching; test scheduled recalculation | | |
| TASK-066 | Create seeder `VendorPerformanceSeeder.php` for development with sample performance records across different rating levels, sample evaluations, sample delivery logs | | |
| TASK-067 | Create console command `php artisan purchasing:calculate-vendor-performance` for manual execution of performance calculation; support --vendor-id flag for specific vendor, --period flag for date range | | |
| TASK-068 | Schedule RecalculateAllVendorPerformanceAction to run daily via Laravel Scheduler; configure in app/Console/Kernel.php or use Schedule attribute | | |

## 3. Alternatives

- **ALT-001**: Manual-only vendor rating (no automatic calculation) - rejected; FR-PO-008 requires data-driven performance tracking
- **ALT-002**: Single overall score instead of multiple metrics - rejected; detailed metrics provide actionable insights
- **ALT-003**: Real-time performance calculation on every transaction - rejected; batch calculation more efficient, scheduled daily sufficient
- **ALT-004**: Fixed evaluation period (always quarterly) - rejected; flexible periods allow custom analysis
- **ALT-005**: Store performance metrics in vendor table - rejected; separate VendorPerformance model allows historical tracking
- **ALT-006**: External BI tool for reporting - considered; provide internal API first, BI integration as future enhancement

## 4. Dependencies

### Mandatory Dependencies
- **DEP-001**: SUB01 (Multi-Tenancy) - Tenant model and isolation
- **DEP-002**: SUB02 (Authentication & Authorization) - User model, permission system
- **DEP-003**: SUB03 (Audit Logging) - ActivityLoggerContract for tracking
- **DEP-004**: PLAN01 (Vendor Management) - Vendor model
- **DEP-005**: PLAN02 (Purchase Orders) - PurchaseOrder model for spend analysis
- **DEP-006**: PLAN03 (Goods Receipt) - GoodsReceiptNote model for delivery and quality metrics

### Optional Dependencies
- **DEP-007**: SUB22 (Notifications) - Alerts for poor performing vendors
- **DEP-008**: SUB23 (BI/Reporting Module) - Advanced analytics and dashboards

### Package Dependencies
- **DEP-009**: lorisleiva/laravel-actions ^2.0 - Action and job pattern
- **DEP-010**: Laravel Queue system - Async performance calculation
- **DEP-011**: Laravel Scheduler - Daily performance recalculation
- **DEP-012**: Laravel Cache - Performance metrics caching

## 5. Files

### Models & Enums
- `packages/purchasing/src/Models/VendorPerformance.php` - Performance metrics model
- `packages/purchasing/src/Models/VendorEvaluation.php` - Manual evaluation model
- `packages/purchasing/src/Models/DeliveryPerformanceLog.php` - Delivery tracking model
- `packages/purchasing/src/Enums/VendorOverallRating.php` - Rating enumeration

### Repositories & Contracts
- `packages/purchasing/src/Contracts/VendorPerformanceRepositoryContract.php` - Performance repository interface
- `packages/purchasing/src/Repositories/VendorPerformanceRepository.php` - Performance repository implementation

### Services
- `packages/purchasing/src/Services/VendorPerformanceService.php` - Performance calculation logic
- `packages/purchasing/src/Services/DeliveryPerformanceService.php` - Delivery metrics service
- `packages/purchasing/src/Services/PerformanceAggregationService.php` - Data aggregation
- `packages/purchasing/src/Services/VendorReportingService.php` - Reporting and analytics
- `packages/purchasing/src/Services/PerformanceMetricsService.php` - KPI calculations

### Actions
- `packages/purchasing/src/Actions/CalculateVendorPerformanceAction.php` - Calculate performance
- `packages/purchasing/src/Actions/UpdateVendorRatingAction.php` - Update rating
- `packages/purchasing/src/Actions/LogDeliveryPerformanceAction.php` - Log delivery
- `packages/purchasing/src/Actions/RecalculateAllVendorPerformanceAction.php` - Batch recalculation
- `packages/purchasing/src/Actions/EvaluateVendorAction.php` - Manual evaluation
- `packages/purchasing/src/Actions/GetVendorEvaluationHistoryAction.php` - Evaluation history
- `packages/purchasing/src/Actions/GetVendorEvaluationSummaryAction.php` - Evaluation summary
- `packages/purchasing/src/Actions/GenerateVendorScorecardAction.php` - Scorecard generation
- `packages/purchasing/src/Actions/GenerateTopPerformersReportAction.php` - Top performers
- `packages/purchasing/src/Actions/GenerateSpendAnalysisReportAction.php` - Spend analysis
- `packages/purchasing/src/Actions/GeneratePerformanceTrendReportAction.php` - Trend report
- `packages/purchasing/src/Actions/ExportVendorPerformanceDataAction.php` - Data export

### Controllers & Requests
- `packages/purchasing/src/Http/Controllers/VendorPerformanceController.php` - Performance API controller
- `packages/purchasing/src/Http/Controllers/VendorEvaluationController.php` - Evaluation API controller
- `packages/purchasing/src/Http/Controllers/PerformanceReportController.php` - Reporting API controller
- `packages/purchasing/src/Http/Requests/EvaluateVendorRequest.php` - Evaluation validation
- `packages/purchasing/src/Http/Requests/CalculatePerformanceRequest.php` - Calculation validation
- `packages/purchasing/src/Http/Requests/VendorComparisonRequest.php` - Comparison validation

### Resources
- `packages/purchasing/src/Http/Resources/VendorPerformanceResource.php` - Performance transformation
- `packages/purchasing/src/Http/Resources/VendorEvaluationResource.php` - Evaluation transformation
- `packages/purchasing/src/Http/Resources/DeliveryPerformanceLogResource.php` - Log transformation
- `packages/purchasing/src/Http/Resources/VendorScorecardResource.php` - Scorecard transformation

### Events & Listeners
- `packages/purchasing/src/Events/VendorPerformanceCalculatedEvent.php`
- `packages/purchasing/src/Events/VendorRatingUpdatedEvent.php` (EV-PO-004)
- `packages/purchasing/src/Events/VendorPerformanceBatchUpdatedEvent.php`
- `packages/purchasing/src/Events/VendorEvaluatedEvent.php`
- `packages/purchasing/src/Listeners/UpdateVendorRatingOnPerformanceChangeListener.php`
- `packages/purchasing/src/Listeners/LogDeliveryPerformanceOnGRNPostedListener.php`
- `packages/purchasing/src/Listeners/UpdatePerformanceOnEvaluationListener.php`

### Policies & Commands
- `packages/purchasing/src/Policies/VendorPerformancePolicy.php` - Performance authorization
- `packages/purchasing/src/Console/Commands/CalculateVendorPerformanceCommand.php` - CLI command

### Database
- `packages/purchasing/database/migrations/2025_01_01_000014_create_vendor_performance_table.php`
- `packages/purchasing/database/migrations/2025_01_01_000015_create_vendor_evaluations_table.php`
- `packages/purchasing/database/migrations/2025_01_01_000016_create_delivery_performance_logs_table.php`
- `packages/purchasing/database/factories/VendorPerformanceFactory.php`
- `packages/purchasing/database/factories/VendorEvaluationFactory.php`
- `packages/purchasing/database/factories/DeliveryPerformanceLogFactory.php`
- `packages/purchasing/database/seeders/VendorPerformanceSeeder.php`

### Tests (Total: 68 tasks with testing components)
- `packages/purchasing/tests/Unit/Models/VendorPerformanceTest.php`
- `packages/purchasing/tests/Unit/Models/VendorEvaluationTest.php`
- `packages/purchasing/tests/Unit/Services/VendorPerformanceServiceTest.php`
- `packages/purchasing/tests/Unit/Services/DeliveryPerformanceServiceTest.php`
- `packages/purchasing/tests/Unit/Services/PerformanceAggregationServiceTest.php`
- `packages/purchasing/tests/Unit/Services/PerformanceMetricsServiceTest.php`
- `packages/purchasing/tests/Feature/VendorPerformanceTest.php`
- `packages/purchasing/tests/Feature/VendorEvaluationTest.php`
- `packages/purchasing/tests/Feature/PerformanceReportingTest.php`
- `packages/purchasing/tests/Integration/PerformanceCalculationIntegrationTest.php`

## 6. Testing

### Unit Tests (20 tests)
- **TEST-001**: VendorPerformance rating calculation
- **TEST-002**: VendorPerformance computed properties (total_deliveries, average_po_value)
- **TEST-003**: DeliveryPerformanceLog is_on_time logic
- **TEST-004**: VendorEvaluation overall_score calculation
- **TEST-005**: VendorPerformanceService metrics calculation
- **TEST-006**: DeliveryPerformanceService days_late calculation
- **TEST-007**: PerformanceAggregationService data aggregation
- **TEST-008**: PerformanceMetricsService KPI calculations
- **TEST-009**: VendorReportingService scorecard generation
- **TEST-010**: All action classes with mocked dependencies

### Feature Tests (25 tests)
- **TEST-011**: Calculate vendor performance automatically
- **TEST-012**: Manual vendor evaluation submission
- **TEST-013**: Rating update event dispatched (EV-PO-004)
- **TEST-014**: Delivery performance logged on GRN post
- **TEST-015**: Performance recalculation batch job
- **TEST-016**: Vendor evaluation history retrieval
- **TEST-017**: Top performers report generation
- **TEST-018**: Poor performers identification
- **TEST-019**: Spend analysis report
- **TEST-020**: Performance trend over time
- **TEST-021**: Vendor comparison report
- **TEST-022**: Scorecard generation with all metrics
- **TEST-023**: Export performance data
- **TEST-024**: Performance metrics cached correctly

### Integration Tests (10 tests)
- **TEST-025**: Performance calculation uses PO data
- **TEST-026**: Performance calculation uses GRN data
- **TEST-027**: Rating update triggers VendorRatingUpdatedEvent (EV-PO-004)
- **TEST-028**: Scheduled recalculation job executes
- **TEST-029**: Delivery performance logged automatically on GRN post

### Acceptance Tests (13 tests)
- **TEST-030**: Vendor scorecard displays all metrics correctly
- **TEST-031**: Top performers report shows excellent vendors
- **TEST-032**: Poor performers report identifies vendors needing improvement
- **TEST-033**: Spend analysis shows correct totals
- **TEST-034**: Performance trend shows monthly progression
- **TEST-035**: Vendor comparison compares multiple vendors accurately
- **TEST-036**: Manual evaluation updates performance metrics
- **TEST-037**: Rating changes are audited (DR-PO-003)
- **TEST-038**: Performance data export includes all fields
- **TEST-039**: KPI calculations accurate
- **TEST-040**: VendorRatingUpdatedEvent dispatched when rating changes (EV-PO-004)
- **TEST-041**: Delivery performance affects on-time delivery rate
- **TEST-042**: Quality inspection results affect quality pass rate

**Total Test Coverage:** 68 tests (20 unit + 25 feature + 10 integration + 13 acceptance)

## 7. Risks & Assumptions

### Risks
- **RISK-001**: Performance calculation complexity may cause long execution times - Mitigation: batch processing, caching, optimize queries
- **RISK-002**: Historical data volume may affect report generation speed - Mitigation: pagination, date range filters, data archival strategy
- **RISK-003**: Manual evaluations may be subjective and inconsistent - Mitigation: provide evaluation guidelines, require comments, aggregate with objective metrics
- **RISK-004**: Scheduled job failures may result in stale metrics - Mitigation: job monitoring, retry logic, manual trigger command
- **RISK-005**: Rating changes may not reflect recent improvements - Mitigation: adjustable calculation periods, manual override capability

### Assumptions
- **ASSUMPTION-001**: Performance calculated based on completed transactions (posted GRNs, approved POs)
- **ASSUMPTION-002**: On-time delivery considers promised delivery date from PO (not vendor's promise)
- **ASSUMPTION-003**: Quality pass rate based on GRN inspection results (accepted vs rejected)
- **ASSUMPTION-004**: Price competitiveness compared against average market price or other vendors for same items
- **ASSUMPTION-005**: Overall rating is weighted average (equal weights for delivery, quality, price competitiveness)
- **ASSUMPTION-006**: Performance calculated for rolling periods (last 90 days default)
- **ASSUMPTION-007**: Manual evaluations supplement automated metrics (not replace)

## 8. KIV for Future Implementations

- **KIV-001**: Predictive analytics (forecast vendor performance)
- **KIV-002**: Automated vendor notifications for poor performance
- **KIV-003**: Vendor benchmarking (compare against industry standards)
- **KIV-004**: Vendor improvement plans (action plans for poor performers)
- **KIV-005**: Integration with vendor portals (vendors view own performance)
- **KIV-006**: Advanced BI dashboards with drill-down capabilities
- **KIV-007**: Machine learning for vendor risk scoring
- **KIV-008**: Vendor capacity planning (predict vendor load)
- **KIV-009**: Multi-criteria decision analysis (MCDA) for vendor selection
- **KIV-010**: Vendor segmentation (strategic/tactical/operational)
- **KIV-011**: Cost of poor quality (COPQ) calculation
- **KIV-012**: Vendor collaboration scoring (innovation, responsiveness)

## 9. Related PRD / Further Reading

- **Master PRD**: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD**: [../prd/prd-01/PRD01-SUB16-PURCHASING.md](../prd/prd-01/PRD01-SUB16-PURCHASING.md)
- **Related Plans**:
  - PRD01-SUB16-PLAN01 (Vendor Management & Purchase Requisition) - Vendor foundation
  - PRD01-SUB16-PLAN02 (Purchase Order Management & Approval) - PO data for spend analysis
  - PRD01-SUB16-PLAN03 (Goods Receipt & Three-Way Matching) - GRN data for delivery and quality metrics
- **Integration Documentation**:
  - SUB03 (Audit Logging) - Activity tracking (DR-PO-003)
  - SUB22 (Notifications) - Performance alerts (future)
  - SUB23 (BI/Reporting) - Advanced analytics (future)
- **Architecture Documentation**: [../../architecture/](../../architecture/)
- **Coding Guidelines**: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
