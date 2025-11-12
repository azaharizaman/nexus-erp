---
plan: Implement Multi-Currency & Exchange Rate Management System
version: 1.0
date_created: 2025-01-15
last_updated: 2025-01-15
owner: Development Team
status: Planned
tags: [feature, localization, currency, exchange-rate, financial, business-logic]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This plan implements multi-currency support with real-time exchange rate management, currency conversion logic, and automated synchronization from external exchange rate APIs. This plan addresses FR-LOC-002, FR-LOC-003, BR-LOC-002, DR-LOC-002, ARCH-LOC-001, PR-LOC-002, and EV-LOC-002.

## 1. Requirements & Constraints

- **FR-LOC-002**: Support multi-currency operations with configurable tenant and user defaults
- **FR-LOC-003**: Provide exchange rate management with multiple sources
- **BR-LOC-002**: Exchange rates updated daily from external source with manual override
- **DR-LOC-002**: Store exchange rates in database with effective date tracking
- **ARCH-LOC-001**: Use Redis caching for translations and exchange rates
- **PR-LOC-002**: Currency conversion must complete within 10ms (target: 5ms)
- **SCR-LOC-001**: Support 150+ currencies (ISO 4217 codes)
- **SEC-003**: Currency conversion must use appropriate precision (4 decimal places)
- **SEC-004**: Exchange rate changes must be audit-logged
- **CON-004**: Base currency is USD unless overridden at tenant level
- **CON-005**: Support ISO 4217 currency codes (3-letter codes)
- **CON-006**: Exchange rates cached for 6 hours with auto-refresh
- **GUD-001**: Follow repository pattern for all data access operations
- **GUD-002**: Use Laravel Actions for all business operations
- **GUD-003**: All models must use strict type declarations and PHPDoc
- **PAT-004**: Use Strategy pattern for exchange rate provider selection
- **PAT-005**: Use Circuit Breaker pattern for external API calls
- **PAT-006**: Use Command pattern for scheduled rate synchronization

## 2. Implementation Steps

### GOAL-001: Create Database Schema for Currencies & Exchange Rates

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-LOC-002/FR-LOC-002 | Creates SQL tables for currency definitions and exchange rate tracking | | |
| SCR-LOC-001 | Supports 150+ currencies with ISO 4217 codes | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000002_create_currencies_table.php` with currency_code varchar(10) unique (ISO 4217), currency_name varchar(100), symbol varchar(10), decimal_places int default 2, is_crypto boolean default false, is_active boolean, timestamps, indexes on code/active | | |
| TASK-002 | Create migration `2025_01_01_000003_create_exchange_rates_table.php` with from_currency_code varchar(10) FK, to_currency_code varchar(10) FK, rate decimal(20,10), inverse_rate decimal(20,10), source varchar(50), effective_date date, effective_until date nullable, is_manual_override boolean default false, created_by bigint FK to users nullable, timestamps, unique constraint on (from, to, effective_date), indexes on from/to/effective_date/source, FK to currencies with restrict delete | | |
| TASK-003 | Update TenantLocalizationSettings migration to add default_currency_code varchar(10) FK to currencies, accounting_currency_code varchar(10) FK for reporting currency | | |
| TASK-004 | Update UserLocalizationPreferences migration to add currency_code varchar(10) FK to currencies nullable for display preference | | |

### GOAL-002: Implement Currency Models & Seeders

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-LOC-002 | Creates Eloquent models for currency management | | |
| SCR-LOC-001 | Seeds 150+ currencies with ISO 4217 codes | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-005 | Create Currency model in `packages/localization/src/Models/Currency.php` with HasActivityLogging trait, fillable fields, casts (is_crypto => boolean, is_active => boolean, decimal_places => integer), scopes (active, fiat, crypto), methods (isCrypto, format(amount), activate, deactivate) | | |
| TASK-006 | Create ExchangeRate model in `packages/localization/src/Models/ExchangeRate.php` with HasActivityLogging trait, relationships (fromCurrency, toCurrency, creator), casts (rate/inverse_rate => decimal:10, effective_date/effective_until => date, is_manual_override => boolean), scopes (active, bySource, current), methods (isExpired, isManualOverride, calculateInverse) | | |
| TASK-007 | Add currency relationship to TenantLocalizationSettings model (defaultCurrency, accountingCurrency) with default USD fallback | | |
| TASK-008 | Add currency relationship to UserLocalizationPreferences model (currency) with fallback to tenant currency | | |
| TASK-009 | Create CurrencySeeder in `packages/localization/database/seeders/CurrencySeeder.php` seeding 150+ currencies: Major fiat (USD, EUR, GBP, JPY, CHF, CAD, AUD, CNY, INR, etc.), Cryptocurrencies (BTC, ETH with is_crypto=true, decimal_places=8), marking USD as default active base currency | | |

### GOAL-003: Implement Exchange Rate Service with Multiple Providers

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-LOC-003/BR-LOC-002 | Implements exchange rate fetching from multiple external sources | | |
| PAT-004/PAT-005 | Uses Strategy pattern and Circuit Breaker for reliability | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-010 | Create ExchangeRateProviderContract interface in `packages/localization/src/Contracts/ExchangeRateProviderContract.php` with methods: getRate(string $from, string $to): ?float, getRates(string $baseCurrency, array $targetCurrencies): array, isAvailable(): bool, getProviderName(): string | | |
| TASK-011 | Create FixerIoExchangeRateProvider in `packages/localization/src/Services/ExchangeRateProviders/FixerIoExchangeRateProvider.php` implementing contract, using fixer.io API with Guzzle HTTP client, parsing JSON response, handling rate limits, implementing Circuit Breaker (3 failures = 5min cooldown) | | |
| TASK-012 | Create CurrencyLayerProvider in `packages/localization/src/Services/ExchangeRateProviders/CurrencyLayerProvider.php` as alternative provider, supporting fallback if Fixer.io unavailable | | |
| TASK-013 | Create ExchangeRateProviderFactory in `packages/localization/src/Services/ExchangeRateProviderFactory.php` with getPrimaryProvider(), getFallbackProvider(), implementing Strategy pattern for provider selection based on config and availability | | |
| TASK-014 | Create ExchangeRateServiceContract interface in `packages/localization/src/Contracts/ExchangeRateServiceContract.php` with methods: getRate(string $from, string $to, ?Carbon $date = null): float, syncRates(string $baseCurrency = 'USD'): int, getHistoricalRate(string $from, string $to, Carbon $date): ?float, createManualRate(string $from, string $to, float $rate, ?Carbon $effectiveDate = null): ExchangeRate | | |
| TASK-015 | Create ExchangeRateService in `packages/localization/src/Services/ExchangeRateService.php` implementing contract, using ExchangeRateRepository for data access, caching rates in Redis with 6h TTL (key: `exchange_rate:{from}:{to}:{date}`), handling automatic fallback between providers, logging rate sync activities | | |

### GOAL-004: Implement Currency Conversion Logic

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-LOC-002/PR-LOC-002 | Implements high-performance currency conversion (10ms target) | | |
| ARCH-LOC-001 | Uses Redis caching for optimal performance | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-016 | Create ExchangeRateRepository in `packages/localization/src/Repositories/ExchangeRateRepository.php` with methods: getCurrentRate(string $from, string $to), getRateByDate(string $from, string $to, Carbon $date), storeRate(string $from, string $to, float $rate, string $source), getHistoricalRates(string $from, string $to, Carbon $startDate, Carbon $endDate): Collection, with indexes optimized for date-range queries | | |
| TASK-017 | Create ConvertCurrencyAction in `packages/localization/src/Actions/ConvertCurrencyAction.php` using AsAction trait, handle() accepting amount (float), fromCurrency (string), toCurrency (string), optional date (Carbon), validating currencies exist and active, fetching rate via ExchangeRateService, performing calculation with proper precision (4 decimal places), caching result for 6h, returning ConversionResult DTO | | |
| TASK-018 | Create ConversionResult DTO in `packages/localization/src/DataTransferObjects/ConversionResult.php` with properties: float $originalAmount, string $fromCurrency, float $convertedAmount, string $toCurrency, float $exchangeRate, Carbon $rateDate, string $source, readonly class with getters | | |
| TASK-019 | Implement decimal precision handling in ConvertCurrencyAction using brick/math library for accurate decimal calculations, rounding to currency-specific decimal places (from Currency model), avoiding floating-point precision errors | | |
| TASK-020 | Add cross-currency conversion in ConvertCurrencyAction (e.g., EUR to JPY via USD) when direct rate unavailable: get EUR->USD rate, get USD->JPY rate, multiply rates with proper precision, cache composite conversion | | |

### GOAL-005: Implement Exchange Rate Synchronization & API

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| BR-LOC-002/EV-LOC-002 | Implements automated daily rate sync with manual override capability | | |
| SEC-004 | Audit logs all exchange rate changes | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-021 | Create SyncExchangeRatesCommand in `packages/localization/src/Console/Commands/SyncExchangeRatesCommand.php` with signature `localization:sync-exchange-rates`, accepting optional --base-currency flag, using ExchangeRateService to fetch rates, storing new rates, invalidating cache, dispatching ExchangeRateUpdatedEvent, scheduled daily at 2 AM UTC in AppServiceProvider | | |
| TASK-022 | Create UpdateExchangeRateAction in `packages/localization/src/Actions/UpdateExchangeRateAction.php` for manual rate override, requiring admin permission, accepting from/to currencies, rate, optional effective_date, creating ExchangeRate record with is_manual_override=true, invalidating cache, logging activity, dispatching event | | |
| TASK-023 | Create ExchangeRateUpdatedEvent in `packages/localization/src/Events/ExchangeRateUpdatedEvent.php` with properties: ExchangeRate $rate, bool $isManualOverride, string $source, dispatched after rate creation/update | | |
| TASK-024 | Create InvalidateExchangeRateCacheListener in `packages/localization/src/Listeners/InvalidateExchangeRateCacheListener.php` listening to ExchangeRateUpdatedEvent, invalidating relevant Redis cache keys (direct rate, inverse rate, related cross-currency conversions) | | |
| TASK-025 | Add observer ExchangeRateObserver in `packages/localization/src/Observers/ExchangeRateObserver.php` with created() and updated() methods logging activities with old/new rate values, user who made change, timestamp | | |
| TASK-026 | Create GetExchangeRateHistoryAction in `packages/localization/src/Actions/GetExchangeRateHistoryAction.php` using ExchangeRateRepository to fetch historical rates for charting/reporting, supporting date range filtering, returning time series data | | |

## 3. Alternatives

- **ALT-001**: Use single exchange rate provider - Rejected in favor of multiple providers for redundancy and reliability
- **ALT-002**: Store exchange rates with 2 decimal places - Rejected as 10 decimal places needed for cryptocurrency and cross-rate accuracy
- **ALT-003**: Real-time rate fetching without caching - Rejected due to latency and API rate limit concerns (PR-LOC-002 requires 10ms performance)
- **ALT-004**: Store only current exchange rate (no history) - Rejected as historical rates needed for reporting and backdated transactions

## 4. Dependencies

- **DEP-001**: Laravel Framework ^12.0 (for Eloquent, cache, events, console)
- **DEP-002**: guzzlehttp/guzzle ^7.0 (for external API calls)
- **DEP-003**: brick/math ^0.12 (for precise decimal calculations)
- **DEP-004**: nesbot/carbon ^3.0 (for date handling)
- **DEP-005**: lorisleiva/laravel-actions ^2.0 (for action pattern)
- **DEP-006**: SUB01 Multi-Tenancy (for tenant-specific currency settings)
- **DEP-007**: SUB02 Authentication & Authorization (for manual rate override permissions)
- **DEP-008**: SUB12 Accounting (will consume currency conversion for financial transactions)
- **DEP-009**: Redis 6+ (for exchange rate caching)
- **DEP-010**: External API: Fixer.io or CurrencyLayer API key (for rate synchronization)

## 5. Files

**Migrations:**
- `packages/localization/database/migrations/2025_01_01_000002_create_currencies_table.php`: Currencies schema
- `packages/localization/database/migrations/2025_01_01_000003_create_exchange_rates_table.php`: Exchange rates schema
- `packages/localization/database/migrations/2025_01_01_000004_update_tenant_settings_add_currency.php`: Add currency fields
- `packages/localization/database/migrations/2025_01_01_000005_update_user_preferences_add_currency.php`: Add currency field

**Models:**
- `packages/localization/src/Models/Currency.php`: Currency model with formatting methods
- `packages/localization/src/Models/ExchangeRate.php`: Exchange rate model with relationships
- `packages/localization/src/Models/TenantLocalizationSettings.php`: Updated with currency relationships
- `packages/localization/src/Models/UserLocalizationPreferences.php`: Updated with currency relationship

**Contracts:**
- `packages/localization/src/Contracts/ExchangeRateProviderContract.php`: Exchange rate provider interface
- `packages/localization/src/Contracts/ExchangeRateServiceContract.php`: Exchange rate service interface

**Services:**
- `packages/localization/src/Services/ExchangeRateService.php`: Core exchange rate logic
- `packages/localization/src/Services/ExchangeRateProviderFactory.php`: Provider strategy factory
- `packages/localization/src/Services/ExchangeRateProviders/FixerIoExchangeRateProvider.php`: Fixer.io provider
- `packages/localization/src/Services/ExchangeRateProviders/CurrencyLayerProvider.php`: CurrencyLayer provider

**Repositories:**
- `packages/localization/src/Repositories/ExchangeRateRepository.php`: Exchange rate data access

**Actions:**
- `packages/localization/src/Actions/ConvertCurrencyAction.php`: Currency conversion logic
- `packages/localization/src/Actions/UpdateExchangeRateAction.php`: Manual rate override
- `packages/localization/src/Actions/GetExchangeRateHistoryAction.php`: Historical rate retrieval

**DTOs:**
- `packages/localization/src/DataTransferObjects/ConversionResult.php`: Conversion result DTO

**Events:**
- `packages/localization/src/Events/ExchangeRateUpdatedEvent.php`: Rate update event

**Listeners:**
- `packages/localization/src/Listeners/InvalidateExchangeRateCacheListener.php`: Cache invalidation

**Observers:**
- `packages/localization/src/Observers/ExchangeRateObserver.php`: Audit logging for rate changes

**Console:**
- `packages/localization/src/Console/Commands/SyncExchangeRatesCommand.php`: Daily rate sync command

**Seeders:**
- `packages/localization/database/seeders/CurrencySeeder.php`: 150+ currencies

**Configuration:**
- `packages/localization/config/localization.php`: Add exchange rate provider config, API keys, cache TTL

## 6. Testing

- **TEST-001**: Unit test for currency conversion accuracy with various decimal places
- **TEST-002**: Unit test for cross-currency conversion (EUR->JPY via USD)
- **TEST-003**: Unit test for exchange rate caching and cache invalidation
- **TEST-004**: Feature test for ConvertCurrencyAction completing within 10ms
- **TEST-005**: Feature test for manual exchange rate override with admin permissions
- **TEST-006**: Feature test for daily exchange rate synchronization command
- **TEST-007**: Integration test for ExchangeRateProvider with mocked API responses
- **TEST-008**: Integration test for Circuit Breaker pattern during API failures
- **TEST-009**: Performance test for conversion under load (1000 conversions/sec)
- **TEST-010**: Test for ExchangeRateUpdatedEvent dispatch and cache invalidation
- **TEST-011**: Test for historical rate retrieval and date-range queries
- **TEST-012**: Test for decimal precision using brick/math (no floating-point errors)

## 7. Risks & Assumptions

- **RISK-001**: External exchange rate API unavailable - Mitigation: Multiple providers with automatic fallback, Circuit Breaker pattern
- **RISK-002**: Exchange rate API rate limits exceeded - Mitigation: Caching with 6h TTL, batch rate fetching, daily scheduled sync
- **RISK-003**: Floating-point precision errors in currency calculations - Mitigation: Use brick/math library for decimal arithmetic
- **RISK-004**: Cache serving stale rates during market volatility - Mitigation: 6h cache TTL (configurable), manual refresh capability
- **RISK-005**: Historical rate gaps preventing backdated transactions - Mitigation: Store daily snapshots, interpolation logic for missing dates
- **ASSUMPTION-001**: Daily rate synchronization is sufficient (not real-time trading system)
- **ASSUMPTION-002**: Fixer.io or CurrencyLayer API available with paid subscription
- **ASSUMPTION-003**: USD as base currency is acceptable for most conversions
- **ASSUMPTION-004**: 10 decimal places sufficient for all currency pairs including crypto

## 8. KIV for future implementations

- **KIV-001**: Real-time exchange rate streaming for high-frequency trading
- **KIV-002**: Multiple exchange rate sources with weighted average
- **KIV-003**: Currency conversion API rate limiting per tenant
- **KIV-004**: Exchange rate forecasting and trend analysis
- **KIV-005**: Cryptocurrency exchange integration (Coinbase, Binance)
- **KIV-006**: Custom exchange rate markup per tenant (for FX fees)
- **KIV-007**: Historical exchange rate charting and visualization

## 9. Related PRD / Further Reading

- [PRD01-SUB25: Localization](../prd/prd-01/PRD01-SUB25-LOCALIZATION.md)
- [Master PRD](../prd/PRD01-MVP.md)
- [CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- [ISO 4217 Currency Codes](https://en.wikipedia.org/wiki/ISO_4217)
- [Fixer.io API Documentation](https://fixer.io/documentation)
- [CurrencyLayer API Documentation](https://currencylayer.com/documentation)
- [Brick Math Documentation](https://github.com/brick/math)
