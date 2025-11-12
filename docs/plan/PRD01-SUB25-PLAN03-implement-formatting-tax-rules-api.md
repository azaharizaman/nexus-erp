---
plan: Implement Localization Settings, Formatting & Country Tax Rules
version: 1.0
date_created: 2025-01-15
last_updated: 2025-01-15
owner: Development Team
status: Planned
tags: [feature, localization, formatting, tax-rules, api, settings, business-logic]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This plan implements tenant and user localization settings management, number/date/time formatting services, country-specific tax rules for compliance, and comprehensive REST API for all localization features. This plan addresses FR-LOC-004, FR-LOC-005, FR-LOC-008, DR-LOC-003, IR-LOC-001, IR-LOC-002, SEC-001, and SEC-002.

## 1. Requirements & Constraints

- **FR-LOC-004**: Provide configurable number, date, and time formatting per locale
- **FR-LOC-005**: Support timezone conversion for date/time display
- **FR-LOC-008**: Support country-specific tax rules and compliance formats
- **DR-LOC-003**: Store user language preferences at user and tenant levels (completed in PLAN01)
- **IR-LOC-001**: Must integrate with SUB12 (Accounting) for tax calculation
- **IR-LOC-002**: Must provide REST API for language/currency/settings management
- **SEC-001**: Localization data must be tenant-isolated
- **SEC-002**: Settings API must require appropriate permissions
- **CON-007**: Default formatting follows en_US locale (thousands separator: comma, decimal: period)
- **CON-008**: Support user timezone override with fallback to tenant timezone
- **CON-009**: Tax rules retrieved by country ISO code (ISO 3166-1 alpha-2)
- **GUD-001**: Follow repository pattern for all data access operations
- **GUD-002**: Use Laravel Actions for all business operations
- **GUD-003**: All models must use strict type declarations and PHPDoc
- **PAT-007**: Use Factory pattern for formatter instantiation
- **PAT-008**: Use Adapter pattern for Carbon timezone conversion

## 2. Implementation Steps

### GOAL-001: Create Database Schema for Country Tax Rules

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-LOC-008/IR-LOC-001 | Creates SQL table for country-specific tax and compliance rules | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000008_create_country_tax_rules_table.php` with country_code varchar(10) unique (ISO 3166-1 alpha-2), country_name varchar(100), default_tax_rate decimal(5,2), tax_identifier_label varchar(100) for tax ID name (e.g., "VAT", "GST", "Tax ID"), tax_identifier_format varchar(255) for regex pattern, invoice_number_format varchar(255) for required format, timestamps, index on country_code | | |
| TASK-002 | Seed CountryTaxRulesSeeder with common countries: US (Tax ID, EIN format), UK (VAT, GB format), DE (USt-IdNr., DE format), FR (TVA, FR format), AU (ABN), CA (GST/HST), SG (GST), IN (GSTIN), JP (Corporate Number), CN (Taxpayer ID), and 40+ more countries with their respective tax formats and rates | | |

### GOAL-002: Implement Formatting Services for Numbers, Dates, Times

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-LOC-004/FR-LOC-005 | Implements locale-aware formatting with timezone support | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-003 | Create FormatterContract interface in `packages/localization/src/Contracts/FormatterContract.php` with methods: formatNumber(float $number, int $decimals = 2, ?string $locale = null): string, formatDate(Carbon $date, ?string $format = null, ?string $locale = null): string, formatTime(Carbon $time, ?string $format = null, ?string $locale = null): string, formatDateTime(Carbon $datetime, ?string $format = null, ?string $locale = null): string, parseNumber(string $formatted, ?string $locale = null): float | | |
| TASK-004 | Create NumberFormatterService in `packages/localization/src/Services/NumberFormatterService.php` using PHP NumberFormatter (Intl extension), supporting thousands separator, decimal separator, currency formatting, percentage formatting, respecting locale-specific rules (e.g., en_US: "1,234.56", de_DE: "1.234,56", fr_FR: "1 234,56"), caching formatter instances per locale | | |
| TASK-005 | Create DateTimeFormatterService in `packages/localization/src/Services/DateTimeFormatterService.php` using Carbon localization, IntlDateFormatter for locale-specific formats, supporting user timezone override, tenant default timezone fallback, common presets (short, medium, long, full), custom format strings | | |
| TASK-006 | Create FormatNumberAction in `packages/localization/src/Actions/FormatNumberAction.php` with handle() accepting number, decimals, locale, using NumberFormatterService, caching formatted results for 1h, returning formatted string | | |
| TASK-007 | Create FormatDateAction in `packages/localization/src/Actions/FormatDateAction.php` with handle() accepting Carbon date, format preset or custom, locale, timezone, using DateTimeFormatterService, applying user/tenant timezone, returning formatted string | | |
| TASK-008 | Add global helper functions in `packages/localization/src/helpers.php`: formatNumber($number, $decimals = 2), formatDate($date, $format = null), formatTime($time, $format = null), formatDateTime($datetime, $format = null), getCurrentLocale(): string, getCurrentTimezone(): string | | |

### GOAL-003: Implement Localization Settings Management

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-LOC-003/SEC-001 | Implements tenant and user settings management with isolation | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-009 | Create LocalizationSettingsRepository in `packages/localization/src/Repositories/LocalizationSettingsRepository.php` with methods: getTenantSettings(Tenant $tenant): TenantLocalizationSettings, getUserPreferences(User $user): UserLocalizationPreferences, updateTenantSettings(Tenant $tenant, array $data): TenantLocalizationSettings, updateUserPreferences(User $user, array $data): UserLocalizationPreferences, ensuring tenant isolation with BelongsToTenant trait | | |
| TASK-010 | Create UpdateTenantLocalizationSettingsAction in `packages/localization/src/Actions/UpdateTenantLocalizationSettingsAction.php` requiring 'manage-localization-settings' permission, validating language/currency exist, validating timezone from timezone_identifiers_list(), updating settings, invalidating cache, dispatching LocalizationSettingsUpdatedEvent | | |
| TASK-011 | Create UpdateUserLocalizationPreferencesAction in `packages/localization/src/Actions/UpdateUserLocalizationPreferencesAction.php` allowing user to update own preferences (or admin to update any), validating language/currency/timezone, creating or updating UserLocalizationPreferences, invalidating user-specific cache, dispatching UserPreferencesUpdatedEvent | | |
| TASK-012 | Create GetEffectiveLocalizationSettingsAction in `packages/localization/src/Actions/GetEffectiveLocalizationSettingsAction.php` with logic: get user preferences -> merge with tenant settings -> apply defaults (en, USD, UTC), returning EffectiveLocalizationSettings DTO, caching per user for request lifecycle | | |
| TASK-013 | Create EffectiveLocalizationSettings DTO in `packages/localization/src/DataTransferObjects/EffectiveLocalizationSettings.php` with properties: string $language, string $currency, string $timezone, string $dateFormat, string $timeFormat, string $numberFormat, int $firstDayOfWeek, readonly class with getters and toArray() | | |

### GOAL-004: Implement Country Tax Rules & Compliance API

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-LOC-008/IR-LOC-001 | Implements tax rule retrieval for country-specific compliance | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-014 | Create CountryTaxRules model in `packages/localization/src/Models/CountryTaxRules.php` with fillable fields, casts (default_tax_rate => decimal:2), methods for format validation: validateTaxIdentifier(string $identifier): bool, validateInvoiceNumber(string $number): bool, formatTaxIdentifier(string $identifier): string | | |
| TASK-015 | Create CountryTaxRulesRepository in `packages/localization/src/Repositories/CountryTaxRulesRepository.php` with methods: getByCountryCode(string $code): ?CountryTaxRules, getAllActiveCountries(): Collection, caching rules for 24h (rarely change) | | |
| TASK-016 | Create GetCountryTaxRulesAction in `packages/localization/src/Actions/GetCountryTaxRulesAction.php` using repository to fetch rules, caching per country, returning CountryTaxRules model or null if country not found | | |
| TASK-017 | Create ValidateTaxIdentifierAction in `packages/localization/src/Actions/ValidateTaxIdentifierAction.php` accepting country_code and tax_identifier, fetching tax rules, applying regex validation from tax_identifier_format field, returning boolean with validation message | | |
| TASK-018 | Create CountryTaxRulesSeeder in `packages/localization/database/seeders/CountryTaxRulesSeeder.php` with data for 50+ countries including: tax rates (VAT/GST/Sales Tax), tax identifier labels (VAT Number, EIN, ABN, GST Number, etc.), identifier format regex patterns, invoice number format requirements | | |

### GOAL-005: Implement Comprehensive REST API for Localization

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| IR-LOC-002/SEC-002 | Implements full CRUD API with proper authorization | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-019 | Create LanguageController in `packages/localization/src/Http/Controllers/LanguageController.php` with routes: GET /api/v1/languages (list), GET /api/v1/languages/active (active only), POST /api/v1/languages/{code}/switch (user switch language), requiring 'view-localization' permission for list, authenticated user for switch | | |
| TASK-020 | Create CurrencyController in `packages/localization/src/Http/Controllers/CurrencyController.php` with routes: GET /api/v1/currencies (list), GET /api/v1/currencies/active (active only), requiring 'view-localization' permission | | |
| TASK-021 | Create ExchangeRateController in `packages/localization/src/Http/Controllers/ExchangeRateController.php` with routes: GET /api/v1/exchange-rates (list with filters), POST /api/v1/exchange-rates (manual override, requires 'manage-exchange-rates'), GET /api/v1/exchange-rates/history (historical rates), POST /api/v1/exchange-rates/sync (trigger sync, requires 'manage-exchange-rates') | | |
| TASK-022 | Create CurrencyConversionController in `packages/localization/src/Http/Controllers/CurrencyConversionController.php` with route: POST /api/v1/conversions (convert currency), accepting amount/from/to/date, using ConvertCurrencyAction, returning ConversionResult, requiring authentication | | |
| TASK-023 | Create TenantLocalizationSettingsController in `packages/localization/src/Http/Controllers/TenantLocalizationSettingsController.php` with routes: GET /api/v1/localization/settings (get tenant settings), PUT /api/v1/localization/settings (update, requires 'manage-localization-settings'), using actions with proper authorization | | |
| TASK-024 | Create UserLocalizationPreferencesController in `packages/localization/src/Http/Controllers/UserLocalizationPreferencesController.php` with routes: GET /api/v1/localization/preferences (get own preferences), PUT /api/v1/localization/preferences (update own), authenticated users only | | |
| TASK-025 | Create TranslationController in `packages/localization/src/Http/Controllers/TranslationController.php` with routes: GET /api/v1/translations (list by module/language), POST /api/v1/translations (add translation, requires 'manage-translations'), PUT /api/v1/translations/{id} (update), POST /api/v1/translations/{id}/approve (approve, requires 'approve-translations'), POST /api/v1/translations/export (export to JSON), POST /api/v1/translations/import (import from JSON) | | |
| TASK-026 | Create CountryTaxRulesController in `packages/localization/src/Http/Controllers/CountryTaxRulesController.php` with routes: GET /api/v1/tax-rules (list all countries), GET /api/v1/tax-rules/{country_code} (get specific), POST /api/v1/tax-rules/{country_code}/validate-tax-id (validate tax identifier), requiring 'view-localization' permission | | |
| TASK-027 | Create FormRequest classes in `packages/localization/src/Http/Requests/` for: SwitchLanguageRequest, UpdateExchangeRateRequest, ConvertCurrencyRequest, UpdateTenantLocalizationSettingsRequest, UpdateUserLocalizationPreferencesRequest, AddTranslationRequest, ValidateTaxIdentifierRequest with complete validation rules | | |
| TASK-028 | Create API Resources in `packages/localization/src/Http/Resources/` for: LanguageResource, CurrencyResource, ExchangeRateResource, ConversionResultResource, TenantLocalizationSettingsResource, UserLocalizationPreferencesResource, TranslationResource, CountryTaxRulesResource with proper data transformation and optional fields based on permissions | | |
| TASK-029 | Create LocalizationPolicy in `packages/localization/src/Policies/LocalizationPolicy.php` with methods: viewLocalization, manageSettings, manageExchangeRates, manageTranslations, approveTranslations, defining authorization rules (admin for management, authenticated for view) | | |
| TASK-030 | Register all routes in `packages/localization/routes/api.php` with versioning (v1), appropriate middleware (auth:sanctum, tenant), rate limiting, route names for easy reference | | |

## 3. Alternatives

- **ALT-001**: Use JavaScript formatting libraries (Intl) - Rejected in favor of server-side for consistency and security
- **ALT-002**: Store formatting patterns in config files - Rejected as database storage allows runtime customization per tenant
- **ALT-003**: Hardcode tax rules in code - Rejected as database storage enables non-developer updates and tenant customization
- **ALT-004**: Separate package for tax compliance - Deferred to KIV as localization package sufficient for MVP

## 4. Dependencies

- **DEP-001**: Laravel Framework ^12.0 (for Eloquent, API routes, validation)
- **DEP-002**: PHP Intl extension (for NumberFormatter, IntlDateFormatter)
- **DEP-003**: nesbot/carbon ^3.0 (for timezone handling)
- **DEP-004**: lorisleiva/laravel-actions ^2.0 (for action pattern)
- **DEP-005**: SUB01 Multi-Tenancy (for tenant isolation)
- **DEP-006**: SUB02 Authentication & Authorization (for API security)
- **DEP-007**: SUB12 Accounting (consumer of tax rules and currency conversion)
- **DEP-008**: PLAN01 Language & Translation Management (foundation)
- **DEP-009**: PLAN02 Currency & Exchange Rates (foundation)

## 5. Files

**Migrations:**
- `packages/localization/database/migrations/2025_01_01_000008_create_country_tax_rules_table.php`: Tax rules schema

**Models:**
- `packages/localization/src/Models/CountryTaxRules.php`: Country tax rules model
- `packages/localization/src/Models/TenantLocalizationSettings.php`: Updated in PLAN01
- `packages/localization/src/Models/UserLocalizationPreferences.php`: Updated in PLAN01

**Contracts:**
- `packages/localization/src/Contracts/FormatterContract.php`: Formatting interface

**Services:**
- `packages/localization/src/Services/NumberFormatterService.php`: Number formatting logic
- `packages/localization/src/Services/DateTimeFormatterService.php`: Date/time formatting logic

**Repositories:**
- `packages/localization/src/Repositories/LocalizationSettingsRepository.php`: Settings data access
- `packages/localization/src/Repositories/CountryTaxRulesRepository.php`: Tax rules data access

**Actions:**
- `packages/localization/src/Actions/FormatNumberAction.php`: Format numbers
- `packages/localization/src/Actions/FormatDateAction.php`: Format dates
- `packages/localization/src/Actions/UpdateTenantLocalizationSettingsAction.php`: Update tenant settings
- `packages/localization/src/Actions/UpdateUserLocalizationPreferencesAction.php`: Update user preferences
- `packages/localization/src/Actions/GetEffectiveLocalizationSettingsAction.php`: Get merged settings
- `packages/localization/src/Actions/GetCountryTaxRulesAction.php`: Get tax rules
- `packages/localization/src/Actions/ValidateTaxIdentifierAction.php`: Validate tax IDs

**DTOs:**
- `packages/localization/src/DataTransferObjects/EffectiveLocalizationSettings.php`: Settings DTO

**Controllers:**
- `packages/localization/src/Http/Controllers/LanguageController.php`: Language API
- `packages/localization/src/Http/Controllers/CurrencyController.php`: Currency API
- `packages/localization/src/Http/Controllers/ExchangeRateController.php`: Exchange rate API
- `packages/localization/src/Http/Controllers/CurrencyConversionController.php`: Conversion API
- `packages/localization/src/Http/Controllers/TenantLocalizationSettingsController.php`: Tenant settings API
- `packages/localization/src/Http/Controllers/UserLocalizationPreferencesController.php`: User preferences API
- `packages/localization/src/Http/Controllers/TranslationController.php`: Translation management API
- `packages/localization/src/Http/Controllers/CountryTaxRulesController.php`: Tax rules API

**Requests:**
- `packages/localization/src/Http/Requests/SwitchLanguageRequest.php`: Language switch validation
- `packages/localization/src/Http/Requests/UpdateExchangeRateRequest.php`: Rate update validation
- `packages/localization/src/Http/Requests/ConvertCurrencyRequest.php`: Conversion validation
- `packages/localization/src/Http/Requests/UpdateTenantLocalizationSettingsRequest.php`: Settings validation
- `packages/localization/src/Http/Requests/UpdateUserLocalizationPreferencesRequest.php`: Preferences validation
- `packages/localization/src/Http/Requests/AddTranslationRequest.php`: Translation validation
- `packages/localization/src/Http/Requests/ValidateTaxIdentifierRequest.php`: Tax ID validation

**Resources:**
- `packages/localization/src/Http/Resources/LanguageResource.php`: Language API response
- `packages/localization/src/Http/Resources/CurrencyResource.php`: Currency API response
- `packages/localization/src/Http/Resources/ExchangeRateResource.php`: Exchange rate API response
- `packages/localization/src/Http/Resources/ConversionResultResource.php`: Conversion result response
- `packages/localization/src/Http/Resources/TenantLocalizationSettingsResource.php`: Settings response
- `packages/localization/src/Http/Resources/UserLocalizationPreferencesResource.php`: Preferences response
- `packages/localization/src/Http/Resources/TranslationResource.php`: Translation response
- `packages/localization/src/Http/Resources/CountryTaxRulesResource.php`: Tax rules response

**Policies:**
- `packages/localization/src/Policies/LocalizationPolicy.php`: Authorization rules

**Seeders:**
- `packages/localization/database/seeders/CountryTaxRulesSeeder.php`: 50+ countries with tax rules

**Routes:**
- `packages/localization/routes/api.php`: All API routes

**Helpers:**
- `packages/localization/src/helpers.php`: Global formatting functions

## 6. Testing

- **TEST-001**: Unit test for number formatting in various locales (en_US, de_DE, fr_FR)
- **TEST-002**: Unit test for date/time formatting with timezone conversion
- **TEST-003**: Unit test for tax identifier validation with various country formats
- **TEST-004**: Feature test for tenant settings update with permission check
- **TEST-005**: Feature test for user preferences update and fallback logic
- **TEST-006**: Feature test for effective settings retrieval with user/tenant merge
- **TEST-007**: API test for all language endpoints (list, switch)
- **TEST-008**: API test for all currency endpoints (list, convert)
- **TEST-009**: API test for exchange rate management (list, create, sync)
- **TEST-010**: API test for translation CRUD operations with authorization
- **TEST-011**: API test for country tax rules retrieval and validation
- **TEST-012**: Integration test for formatting services with IntlDateFormatter
- **TEST-013**: Test for global helper functions (formatNumber, formatDate)

## 7. Risks & Assumptions

- **RISK-001**: PHP Intl extension not available on server - Mitigation: Add to deployment requirements, provide fallback basic formatting
- **RISK-002**: Tax rules become outdated due to regulation changes - Mitigation: Provide admin UI for updates, schedule quarterly reviews
- **RISK-003**: Timezone conversion errors with DST transitions - Mitigation: Use Carbon's robust timezone handling, test edge cases
- **RISK-004**: API rate limiting too strict for high-traffic tenants - Mitigation: Implement per-tenant rate limits, upgrade tiers
- **ASSUMPTION-001**: PHP Intl extension available on all production servers
- **ASSUMPTION-002**: Tax rules updated manually by administrators as regulations change
- **ASSUMPTION-003**: Formatting preferences stored at tenant level sufficient (not per-user for dates/numbers)
- **ASSUMPTION-004**: 50 countries coverage sufficient for MVP (expand based on demand)

## 8. KIV for future implementations

- **KIV-001**: Automatic tax rule updates from external compliance API
- **KIV-002**: Visual formatting preview in settings UI
- **KIV-003**: Custom formatting patterns per tenant (advanced mode)
- **KIV-004**: Fiscal year configuration per country
- **KIV-005**: Localized address formatting per country postal standards
- **KIV-006**: Phone number formatting with country-specific patterns
- **KIV-007**: Integration with tax calculation services (Avalara, TaxJar)

## 9. Related PRD / Further Reading

- [PRD01-SUB25: Localization](../prd/prd-01/PRD01-SUB25-LOCALIZATION.md)
- [Master PRD](../prd/PRD01-MVP.md)
- [CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- [PHP Intl Extension Documentation](https://www.php.net/manual/en/book.intl.php)
- [ISO 3166-1 Country Codes](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2)
- [Laravel API Resources](https://laravel.com/docs/12.x/eloquent-resources)
- [Carbon Timezone Handling](https://carbon.nesbot.com/docs/#api-timezone)
