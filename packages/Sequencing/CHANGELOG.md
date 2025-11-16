# Changelog

All notable changes to the Nexus Sequencing package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2025-11-16

### Changed - BREAKING
- **Framework-Agnostic Refactoring:** Package is now completely framework-agnostic with zero Laravel dependencies
- **Architecture Overhaul:** Moved all persistence logic to application layer following Nexus Monorepo Architecture
- **Contract-Driven Design:** Introduced 6 comprehensive interfaces for complete decoupling
  - `SequenceInterface` - Sequence data structure contract
  - `SerialNumberLogInterface` - Log entry data structure contract
  - `SequenceRepositoryInterface` - Sequence persistence contract
  - `SerialNumberLogRepositoryInterface` - Log persistence contract
  - `PatternParserServiceInterface` - Pattern parsing service contract
  - `GenerationServiceInterface` - Generation orchestration contract

### Added
- New comprehensive exception hierarchy:
  - `GenerationException` - For generation failures
  - `SequenceConfigurationException` - For configuration errors
- MIT License file
- `.gitignore` file for package
- Complete architectural documentation in README.md
- CHANGELOG.md for version tracking

### Removed - BREAKING
- Laravel framework dependency from `composer.json`
- `lorisleiva/laravel-actions` dependency
- All Laravel-specific code from package:
  - Models (moved to apps/Atomy)
  - Migrations (moved to apps/Atomy)
  - HTTP layer (controllers, requests, resources)
  - Laravel Actions
  - Service Provider
- Old contracts that referenced Laravel-specific types

### Migrated to Application Layer (apps/Atomy)
- Database migrations:
  - `2025_11_12_000001_create_serial_number_sequences_table.php`
  - `2025_11_12_000002_create_serial_number_logs_table.php`
  - `2025_11_14_000001_add_step_size_reset_limit_to_sequences.php`
- Eloquent Models:
  - `App\Models\Sequence` - Implements `SequenceInterface`
  - `App\Models\SerialNumberLog` - Implements `SerialNumberLogInterface`
- Repository Implementations:
  - `App\Repositories\Sequencing\SequenceRepository`
  - `App\Repositories\Sequencing\SerialNumberLogRepository`
- IoC Bindings in `AtomyServiceProvider`

### Technical Details
- Core services remain framework-agnostic with zero Laravel dependencies
- All business logic preserved in `src/Core/` directory
- Pattern parser, generation service, and validation service unchanged
- Atomic counter management with database-level locking preserved
- Transaction safety and rollback capabilities maintained

### Migration Guide

#### For Existing Implementations

If you were using version 1.x of this package, follow these steps to migrate:

1. **Update composer.json:**
   ```bash
   composer require nexus/sequencing:^2.0
   ```

2. **Implement the new contracts in your application:**
   - Create models implementing `SequenceInterface` and `SerialNumberLogInterface`
   - Create repositories implementing the repository interfaces
   - Add IoC bindings in your service provider

3. **Run migrations in your application:**
   - Copy migration files from the package examples
   - Update table names and columns as needed for your schema

4. **Update code references:**
   - Replace `GenerateSerialNumberAction::run()` with service injection
   - Use `GenerationServiceInterface` instead of actions
   - Inject repositories via constructor dependency injection

See the README.md for complete implementation examples.

## [1.0.0] - 2025-11-14

### Initial Release
- Atomic serial number generation with database-level locking
- Configurable pattern templates with variable substitution
- Multi-tenant sequence isolation
- Reset periods (never, daily, monthly, yearly)
- Step size and reset limit support
- Preview mode without counter consumption
- Comprehensive audit trail
- Laravel Actions integration
- REST API endpoints
- Event system integration

[2.0.0]: https://github.com/azaharizaman/nexus/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/azaharizaman/nexus/releases/tag/v1.0.0
