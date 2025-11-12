---
plan: Implement Language & Translation Management Foundation
version: 1.0
date_created: 2025-01-15
last_updated: 2025-01-15
owner: Development Team
status: Planned
tags: [feature, localization, i18n, translation, language, rtl, business-logic]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This plan establishes the foundation for multi-language support with runtime language switching, translation management system with fallback logic, and right-to-left (RTL) language support. This plan addresses FR-LOC-001, FR-LOC-007, FR-LOC-006, BR-LOC-001, BR-LOC-003, DR-LOC-001, DR-LOC-003, ARCH-LOC-002, ARCH-LOC-003, and EV-LOC-001, EV-LOC-003.

## 1. Requirements & Constraints

- **FR-LOC-001**: Support multi-language user interfaces with runtime language switching
- **FR-LOC-006**: Support right-to-left (RTL) languages (Arabic, Hebrew)
- **FR-LOC-007**: Provide translation management with fallback to default language
- **BR-LOC-001**: Users can switch language anytime without re-authentication
- **BR-LOC-003**: Missing translations fallback to default language (English)
- **DR-LOC-001**: Store translation files in JSON format per language
- **DR-LOC-003**: Store user language preferences at user and tenant levels
- **ARCH-LOC-001**: Use Redis caching for translations and exchange rates
- **ARCH-LOC-002**: Use Laravel localization framework for translation management
- **ARCH-LOC-003**: Store translations in JSON format for easy editing
- **PR-LOC-001**: Language switching must complete within 200ms
- **SEC-001**: Translation data must respect tenant isolation
- **SEC-002**: RTL language detection must be automatic based on language code
- **CON-001**: Default language is English (en) with fallback for all languages
- **CON-002**: Translations stored both in database (for management) and JSON files (for runtime)
- **CON-003**: Support ISO 639-1 language codes (2-letter codes)
- **GUD-001**: Follow repository pattern for all data access operations
- **GUD-002**: Use Laravel Actions for all business operations
- **GUD-003**: All models must use strict type declarations and PHPDoc
- **PAT-001**: Use Strategy pattern for translation source (database vs JSON)
- **PAT-002**: Use Observer pattern for translation cache invalidation
- **PAT-003**: Use Singleton pattern for translation service instance

## 2. Implementation Steps

### GOAL-001: Create Database Schema for Languages & Translations

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-LOC-001/DR-LOC-003 | Creates SQL tables for language definitions and user/tenant preferences | | |
| FR-LOC-006 | Stores RTL flag for language direction support | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000001_create_languages_table.php` with language_code varchar(10) unique (ISO 639-1), language_name varchar(100), native_name varchar(100), is_rtl boolean default false, is_active boolean, timestamps, indexes on code/active | | |
| TASK-002 | Create migration `2025_01_01_000004_create_tenant_localization_settings_table.php` with tenant_id UUID FK unique, default_language_code varchar(10) FK to languages, timezone varchar(50), date_format/time_format varchar(50), first_day_of_week int, number_format JSONB, timestamps, FK to tenants with cascade delete | | |
| TASK-003 | Create migration `2025_01_01_000005_create_user_localization_preferences_table.php` with user_id bigint FK unique, language_code varchar(10) FK to languages, timezone/date_format/time_format nullable, timestamps, FK to users with cascade delete | | |
| TASK-004 | Create migration `2025_01_01_000006_create_translation_keys_table.php` with translation_key varchar(255) unique, module varchar(100), context text, timestamps, index on module | | |
| TASK-005 | Create migration `2025_01_01_000007_create_translations_table.php` with translation_key_id bigint FK, language_code varchar(10) FK, translated_text text, is_approved boolean, translated_by/approved_by bigint FK to users nullable, timestamps, unique constraint on (key_id, language_code), indexes on key_id/language_code/approved, FK cascade delete | | |

### GOAL-002: Implement Language Models & Seeders

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-LOC-001 | Creates Eloquent models for language management with RTL support | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-006 | Create Language model in `packages/localization/src/Models/Language.php` with HasActivityLogging trait, fillable fields, casts (is_rtl => boolean, is_active => boolean), scopes (active, rtl), methods (isRTL, activate, deactivate) | | |
| TASK-007 | Create TenantLocalizationSettings model in `packages/localization/src/Models/TenantLocalizationSettings.php` with BelongsToTenant trait, relationships (tenant, language), casts (number_format => array), accessors for format preferences | | |
| TASK-008 | Create UserLocalizationPreferences model in `packages/localization/src/Models/UserLocalizationPreferences.php` with relationships (user, language), methods for applying preferences, fallback to tenant settings | | |
| TASK-009 | Create TranslationKey model in `packages/localization/src/Models/TranslationKey.php` with HasActivityLogging trait, relationships (translations), scopes (byModule), methods for key management | | |
| TASK-010 | Create Translation model in `packages/localization/src/Models/Translation.php` with relationships (key, language, translator, approver), scopes (approved, byLanguage, pending), methods (approve, reject) | | |
| TASK-011 | Create LanguageSeeder in `packages/localization/database/seeders/LanguageSeeder.php` seeding 50+ languages with ISO 639-1 codes: English (en), Spanish (es), French (fr), German (de), Italian (it), Portuguese (pt), Dutch (nl), Russian (ru), Chinese (zh), Japanese (ja), Korean (ko), Arabic (ar, RTL), Hebrew (he, RTL), Hindi (hi), Bengali (bn), and 35+ more languages, marking English as default active | | |

### GOAL-003: Implement Translation Service with Fallback Logic

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-LOC-007/BR-LOC-003 | Implements translation retrieval with automatic fallback to English | | |
| ARCH-LOC-002/ARCH-LOC-003 | Integrates Laravel localization framework with JSON storage | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-012 | Create LocalizationServiceContract interface in `packages/localization/src/Contracts/LocalizationServiceContract.php` with methods: translate(string $key, string $locale, array $replace = []): string, hasTranslation(string $key, string $locale): bool, getAllTranslations(string $locale): array, setLocale(string $locale): void, getLocale(): string | | |
| TASK-013 | Create TranslationService class in `packages/localization/src/Services/TranslationService.php` implementing LocalizationServiceContract with translate() using Laravel __() helper, fallback logic (try requested locale -> try English -> return key), caching translations in Redis with 24h TTL, placeholder replacement support, methods for loading translations from database and JSON files | | |
| TASK-014 | Create TranslationRepository in `packages/localization/src/Repositories/TranslationRepository.php` with methods: findByKey(string $key, string $locale), findByModule(string $module, string $locale), exportToJson(string $locale): array, importFromJson(string $locale, array $translations): int, getPendingTranslations(string $locale): Collection | | |
| TASK-015 | Implement translation caching in TranslationService using Redis with cache keys formatted as `translations:{locale}:{module}`, cache warming on app boot, cache invalidation on translation create/update/delete via Observer pattern | | |

### GOAL-004: Implement Language Switching Actions

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-LOC-001/BR-LOC-001/PR-LOC-001 | Implements runtime language switching completing within 200ms | | |
| EV-LOC-001 | Dispatches LanguageSwitchedEvent on language change | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-016 | Create SwitchLanguageAction in `packages/localization/src/Actions/SwitchLanguageAction.php` using AsAction trait, handle() accepting User and language_code, validating language exists and is active, updating/creating UserLocalizationPreferences record, setting app locale via App::setLocale(), dispatching LanguageSwitchedEvent, returning success with new locale | | |
| TASK-017 | Create GetUserLanguageAction in `packages/localization/src/Actions/GetUserLanguageAction.php` with logic: check UserLocalizationPreferences -> fallback to TenantLocalizationSettings -> fallback to 'en', caching result in request lifecycle to avoid repeated queries | | |
| TASK-018 | Create ApplyUserLanguageMiddleware in `packages/localization/src/Http/Middleware/ApplyUserLanguageMiddleware.php` using GetUserLanguageAction to determine language, calling App::setLocale() at request start, adding Accept-Language header support for API clients | | |
| TASK-019 | Create LanguageSwitchedEvent in `packages/localization/src/Events/LanguageSwitchedEvent.php` with properties: User $user, string $previousLanguage, string $newLanguage, timestamp | | |
| TASK-020 | Create ApplyUserLanguagePreferenceListener in `packages/localization/src/Listeners/ApplyUserLanguagePreferenceListener.php` listening to UserLoggedInEvent (SUB02), calling GetUserLanguageAction, applying language via App::setLocale() | | |

### GOAL-005: Implement Translation Management API & RTL Support

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-LOC-006 | Implements RTL language detection and support | | |
| FR-LOC-007 | Provides API for translation CRUD operations | | |
| EV-LOC-003 | Dispatches TranslationMissingEvent for missing translations | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-021 | Create AddTranslationAction in `packages/localization/src/Actions/AddTranslationAction.php` with validation for translation_key, language_code, translated_text, creating TranslationKey if not exists, creating Translation record, invalidating cache, dispatching TranslationAddedEvent | | |
| TASK-022 | Create ApproveTranslationAction in `packages/localization/src/Actions/ApproveTranslationAction.php` with authorization check (translator role), marking is_approved = true, setting approved_by and approved_at, invalidating cache | | |
| TASK-023 | Create ExportTranslationsAction in `packages/localization/src/Actions/ExportTranslationsAction.php` using TranslationRepository to export translations for specific language to JSON format, saving to `lang/{locale}/messages.json`, returning file path | | |
| TASK-024 | Create ImportTranslationsAction in `packages/localization/src/Actions/ImportTranslationsAction.php` reading JSON from `lang/{locale}/messages.json`, creating/updating Translation records, marking as auto-imported, returning count of imported translations | | |
| TASK-025 | Create DetectRTLMiddleware in `packages/localization/src/Http/Middleware/DetectRTLMiddleware.php` checking Language model is_rtl flag, adding HTML dir="rtl" attribute via view composer, adding CSS class 'rtl-mode' to body, providing helper function isRTL(): bool | | |
| TASK-026 | Create TranslationMissingEvent in `packages/localization/src/Events/TranslationMissingEvent.php` with properties: string $translationKey, string $languageCode, string $module, dispatched when translation not found, listener creates TranslationKey record for tracking | | |

## 3. Alternatives

- **ALT-001**: Use third-party translation service (POEditor, Lokalise) - Deferred as in-house management provides better control and data privacy
- **ALT-002**: Store translations only in JSON files - Rejected as database storage enables translation management UI and approval workflow
- **ALT-003**: Use Laravel's native lang files only - Rejected as JSON format is more flexible and easier for non-developers to edit
- **ALT-004**: Implement machine translation - Deferred to future enhancement (KIV-001) due to quality concerns

## 4. Dependencies

- **DEP-001**: Laravel Framework ^12.0 (for localization, cache, events)
- **DEP-002**: nesbot/carbon ^3.0 (for date/time handling)
- **DEP-003**: lorisleiva/laravel-actions ^2.0 (for action pattern)
- **DEP-004**: SUB01 Multi-Tenancy (for tenant isolation)
- **DEP-005**: SUB02 Authentication & Authorization (for user preferences)
- **DEP-006**: Redis 6+ (for translation caching)

## 5. Files

**Migrations:**
- `packages/localization/database/migrations/2025_01_01_000001_create_languages_table.php`: Languages schema
- `packages/localization/database/migrations/2025_01_01_000004_create_tenant_localization_settings_table.php`: Tenant settings
- `packages/localization/database/migrations/2025_01_01_000005_create_user_localization_preferences_table.php`: User preferences
- `packages/localization/database/migrations/2025_01_01_000006_create_translation_keys_table.php`: Translation keys
- `packages/localization/database/migrations/2025_01_01_000007_create_translations_table.php`: Translations

**Models:**
- `packages/localization/src/Models/Language.php`: Language model with RTL support
- `packages/localization/src/Models/TenantLocalizationSettings.php`: Tenant settings model
- `packages/localization/src/Models/UserLocalizationPreferences.php`: User preferences model
- `packages/localization/src/Models/TranslationKey.php`: Translation key model
- `packages/localization/src/Models/Translation.php`: Translation model

**Contracts:**
- `packages/localization/src/Contracts/LocalizationServiceContract.php`: Localization service interface

**Services:**
- `packages/localization/src/Services/TranslationService.php`: Core translation logic with fallback

**Repositories:**
- `packages/localization/src/Repositories/TranslationRepository.php`: Translation data access

**Actions:**
- `packages/localization/src/Actions/SwitchLanguageAction.php`: Language switching
- `packages/localization/src/Actions/GetUserLanguageAction.php`: Get user language
- `packages/localization/src/Actions/AddTranslationAction.php`: Add translation
- `packages/localization/src/Actions/ApproveTranslationAction.php`: Approve translation
- `packages/localization/src/Actions/ExportTranslationsAction.php`: Export to JSON
- `packages/localization/src/Actions/ImportTranslationsAction.php`: Import from JSON

**Events:**
- `packages/localization/src/Events/LanguageSwitchedEvent.php`: Language change event
- `packages/localization/src/Events/TranslationMissingEvent.php`: Missing translation event

**Listeners:**
- `packages/localization/src/Listeners/ApplyUserLanguagePreferenceListener.php`: Apply language on login

**Middleware:**
- `packages/localization/src/Http/Middleware/ApplyUserLanguageMiddleware.php`: Set locale per request
- `packages/localization/src/Http/Middleware/DetectRTLMiddleware.php`: RTL detection and HTML attributes

**Seeders:**
- `packages/localization/database/seeders/LanguageSeeder.php`: 50+ languages with RTL flags

**Language Files:**
- `packages/localization/lang/en/messages.json`: English translations
- `packages/localization/lang/es/messages.json`: Spanish translations
- `packages/localization/lang/ar/messages.json`: Arabic translations (RTL)

**Configuration:**
- `packages/localization/config/localization.php`: Package configuration

## 6. Testing

- **TEST-001**: Unit test for translation fallback logic (requested -> English -> key)
- **TEST-002**: Unit test for RTL language detection based on language code
- **TEST-003**: Feature test for language switching completing within 200ms
- **TEST-004**: Feature test for user language preference persistence
- **TEST-005**: Feature test for translation CRUD operations
- **TEST-006**: Feature test for translation approval workflow
- **TEST-007**: Feature test for JSON export/import
- **TEST-008**: Integration test for middleware applying language on request
- **TEST-009**: Performance test for translation caching effectiveness
- **TEST-010**: Test for TranslationMissingEvent dispatch and tracking

## 7. Risks & Assumptions

- **RISK-001**: Translation cache may become stale after updates - Mitigation: Implement Observer for automatic cache invalidation
- **RISK-002**: Missing translations may confuse users - Mitigation: Track missing translations, log TranslationMissingEvent, provide fallback
- **RISK-003**: RTL layout may break custom CSS - Mitigation: Provide RTL-specific CSS helper classes, test thoroughly
- **RISK-004**: Language switching performance may degrade with many translations - Mitigation: Redis caching, lazy loading, cache warming
- **ASSUMPTION-001**: English is acceptable fallback language for all users
- **ASSUMPTION-002**: Translation quality verified by native speakers before approval
- **ASSUMPTION-003**: RTL languages require only text direction change, not layout redesign
- **ASSUMPTION-004**: Redis is available for caching

## 8. KIV for future implementations

- **KIV-001**: Integration with machine translation APIs (Google Translate, DeepL)
- **KIV-002**: Translation memory for suggesting similar translations
- **KIV-003**: Translation review workflow with comments and revisions
- **KIV-004**: Crowdsourced translation platform for community contributions
- **KIV-005**: Translation versioning and rollback capability
- **KIV-006**: Context-aware translations based on user role or module
- **KIV-007**: Visual translation editor with in-context preview

## 9. Related PRD / Further Reading

- [PRD01-SUB25: Localization](../prd/prd-01/PRD01-SUB25-LOCALIZATION.md)
- [Master PRD](../prd/PRD01-MVP.md)
- [CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- [Laravel Localization Documentation](https://laravel.com/docs/12.x/localization)
- [ISO 639-1 Language Codes](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes)
- [RTL Best Practices](https://rtlstyling.com/)
