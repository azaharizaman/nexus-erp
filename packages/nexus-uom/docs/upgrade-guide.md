# Upgrade Guide

Use this guide when upgrading the package between releases or integrating new major features.

## General Strategy

1. Review the changelog for the target version once releases are tagged.
2. Back up configuration overrides (`config/uom.php`) and published migrations before updating.
3. Run `composer update azaharizaman/laravel-uom-management` to pull the latest release.
4. Rerun `php artisan vendor:publish` with the `--force` flag **only** when you need to refresh published assets.
5. Execute `vendor/bin/phpunit` to ensure the package still operates as expected inside your application.

## Schema Changes

- After updating, run pending migrations (`php artisan migrate`).
- If new tables or columns are introduced, review them against your published migration copy and sync manually as required.

## Configuration Changes

- Compare your published `config/uom.php` with the version in this repository.
- Adopt new options (such as additional conversion controls or logging toggles) to stay aligned with defaults.

## Command Additions

- New artisan commands land through the package service provider. Run `php artisan list uom` to see newly registered tools after upgrading.
- Commands remain backwards compatible wherever possible. Breaking changes will be noted in the changelog and in this guide.

## Deprecations

- Deprecated APIs ship with clear docblocks and will emit warnings in future releases. Plan to update your consuming code before the next major bump.

## Post-Upgrade Checklist

- [ ] Migrations applied
- [ ] Config compared and updated
- [ ] Tests executed successfully
- [ ] Documentation reviewed for new usage patterns

Staying on top of these steps keeps upgrades predictable and reduces regression risk.
