# Nexus Adapter (Nexus\Erp) Integration Guide

This document describes how to implement the `Nexus`-specific adapter for the `nexus-crm` package. The `nexus-crm` package is atomic and framework-agnostic. All Nexus-specific orchestration code must live inside the Nexus ERP package (`nexus-erp`) or a separate adapter package under the `Nexus` organization.

## Principles

- Keep `nexus-crm` framework-agnostic (`src/Core/` should never depend on Nexus packages).
- Implement concrete bindings and tenant/audit/resourcing integrations inside `nexus-erp`.
- Use contracts defined by `nexus-crm` for cross-package integration where appropriate.
- Guard integration tests and only run them from the orchestrator CI step.

## Adapter responsibilities

- Tenant-wiring (tenant defaults, scope injection)
- Cross-package orchestration (sequencing, audit logging, persistent settings)
- CLI commands for orchestration (sync definitions, process tenant timers)
- Tests exercising cross-package behavior

## Example adapter provider (pattern)

1. Create an adapter contract in `nexus-erp` (e.g., `Nexus\Erp\Crm\Contracts\NexusCrmAdapterInterface`).
2. Implement `NexusCrmAdapter` and bind it in a provider `Nexus\Erp\Providers\CrmServiceProvider`.
3. Keep calls to `nexus-crm` core safe (use `method_exists()` or `interface_exists()` checks when calling optional methods).

This keeps `nexus-crm` independent and testable while allowing `nexus-erp` to provide orchestrated behavior.

## Example CI pattern for orchestrator tests

In your CI pipeline add a dedicated job for orchestration tests that runs only when `nexus-erp` is included:

```bash
# Run package tests
composer test

# Run orchestrator tests (guarded) that exercise cross-package features
CRM_ORCHESTRATOR_TESTS=1 composer test -- --group orchestrator
```

## Example test guide

- Use `Orchestra\Testbench\TestCase` for tests that need to boot Laravel and service providers.
- Register `CrmServiceProvider` in `getPackageProviders()` to make the adapter binding available.
- Use `Mockery` to inject a fake `CrmEngine` to the adapter to test calls like `processTimersForTenant()`.

## Notes

- The adapter may call optional APIs (Tenancy, Sequencing, Activity logging), but the atomic package should not require or rely on them.
- Keep documentation and examples up to date in `packages/nexus-crm/docs/` and a `NEXUS_ADAPTER.md` for sample implementation in Nexus.
