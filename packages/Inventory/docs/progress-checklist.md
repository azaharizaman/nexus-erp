# Laravel Inventory Management: Progress Checklist

**Author**: @azaharizaman
**Date**: 2025-11-02

Keep this checklist updated as work lands in `main` so we can track the overall progress of the package at a glance.

## 1. Repository Foundations
- [x] Establish Composer package metadata and dependencies (`composer.json`)
- [ ] Configure local `path` repositories for `uom-management` and `serial-numbering`
- [x] Add base `README.md` with package overview, philosophy, and installation steps
- [ ] Set up CI workflow (PHPUnit + static analysis) in GitHub Actions

## 2. Contracts & Pluggable Architecture
- [x] Define `Item` and `Location` contracts (`src/Contracts/*`)
- [x] Implement `IsItem` and `IsLocation` helper traits (`src/Concerns/*`)
- [x] Ensure all internal services use contracts for dependency injection

## 3. Data Layer & Default Models
- [x] Create migrations for `items`, `locations`, `stocks`, and `stock_movements` tables
- [x] Scaffold default Eloquent models for all entities (`src/Models/*`)
- [x] Provide model factories for all default models (`database/factories/*`)
- [x] Ensure migrations and models use table names from the configuration file

## 4. Service Provider & Configuration
- [x] Create publishable `inventory-management.php` config file with model and table name mappings
- [x] Implement `InventoryManagementServiceProvider`
- [x] Bind core services (e.g., `InventoryService`) into the container
- [x] Register the `Inventory` facade

## 5. Core Domain Services & Features
- [x] Implement the core `InventoryService`
- [x] Implement `moveStock()` as the primary transaction engine
- [ ] Integrate `laravel-serial-numbering` to assign a unique serial number to every `StockMovement`
- [x] Implement convenience methods: `addStock()`, `removeStock()`, `adjustStock()`
- [x] Implement `transferStock()` for moving items between locations atomically
- [x] Implement `getStockLevel()` for querying quantity on hand
- [ ] Ensure `laravel-uom-management` contracts are respected in all operations

## 6. Events & Observers
- [ ] Implement core events: `StockMoving`, `StockMoved`, `StockAdjusted`
- [ ] Implement `LowStockThresholdReached` event
- [ ] Create a `StockObserver` to monitor quantity changes and fire the low stock event

## 7. Testing Strategy
- [ ] Establish PHPUnit/Testbench testing harness
- [ ] Add unit tests for migrations, models, and relationships
- [ ] Add feature tests for `InventoryService` logic (add, remove, transfer)
- [ ] Test the polymorphic relationship with a sample user-land `Product` model
- [ ] Test that all `StockMovement` records are created correctly with unique serial numbers
- [ ] Test that all relevant events are fired during stock operations
- [ ] Track test coverage for critical paths (stock movements, adjustments)

## 8. Documentation
- [ ] Document the `Item` and `Location` contracts and how to implement them
- [ ] Provide clear usage examples for a fresh install (using default models)
- [ ] Provide a detailed guide on integrating with existing user models (the "pluggable" approach)
- [ ] Document all available `Inventory` facade methods and `InventoryService` features
- [ ] Document all events and how to listen for them
- [ ] Add contribution and upgrade guidelines

## 9. Release Preparation
- [ ] Tag initial release (v0.1.0) once core features are complete and tested
- [ ] Draft a `CHANGELOG.md` file
- [ ] Prepare for Packagist submission