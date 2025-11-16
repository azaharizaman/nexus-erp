# Laravel Inventory Management

A headless, contract-driven Laravel package for managing inventory. It provides a robust backend for tracking stock levels, movements, and valuation without imposing any UI constraints.

## Core Principles

*   **Non-Constricting**: The package will not force users into a rigid data structure. It will adapt to the user's existing models.
*   **Contract-Driven**: All core logic will depend on interfaces (Contracts), not concrete model implementations.
*   **Pluggable & Extensible**: Functionality can be extended through Events, Observers, and custom model implementations.
*   **Integrated Ecosystem**: The package will leverage `laravel-uom-management` and `laravel-serial-numbering` as first-class dependencies.
*   **Data Integrity**: Every stock movement is an immutable event, uniquely identified by a serial number, creating an auditable, double-entry-like ledger.

## Installation

You can install the package via composer:

```bash
composer require azaharizaman/laravel-inventory-management
```

Publish and run the migrations:

```bash
php artisan vendor:publish --provider="Azaharizaman\\LaravelInventoryManagement\\InventoryManagementServiceProvider" --tag="inventory-management-migrations"
php artisan migrate
```

Optionally, you can publish the config file:

```bash
php artisan vendor:publish --provider="Azaharizaman\\LaravelInventoryManagement\\InventoryManagementServiceProvider" --tag="inventory-management-config"
```
