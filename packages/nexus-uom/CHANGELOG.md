# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Fixed
- Compatibility with brick/math 0.14+ where RoundingMode constants are now enums instead of integers

## [v0.1.0] - 2025-11-02

### Added
- Initial public release of the Laravel UOM Management package.
- Core database schema, Eloquent models, and factories for unit types, units, conversions, packaging, and compound units.
- Conversion services covering standard, compound, custom, and packaging flows using `brick/math` precision types.
- Alias resolver and helper utilities for streamlined lookups.
- Artisan commands for listing units, performing conversions, and seeding starter datasets.
- Package configuration file with publishable defaults and Testbench-backed test suite achieving >90% class coverage.

[v0.1.0]: https://github.com/azaharizaman/laravel-uom-management/releases/tag/v0.1.0
