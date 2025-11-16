# Nexus SCM Package Requirements

> **⚠️ REDIRECT NOTICE**  
> This document has been restructured and moved to [README.md](README.md) for better alignment with Nexus ERP architecture.
>
> **For legacy/historical requirements document, see [REQUIREMENTS.legacy.md](REQUIREMENTS.legacy.md)**

---

## Package Information

- **Package Name**: `nexus/scm` (formerly `nexus-supply-chain`)
- **Namespace**: `Nexus\SCM` (formerly `Nexus\SupplyChain`)
- **Version**: 1.0.0
- **Status**: Requirements defined, Phase 1 planned
- **Architecture**: Atomic package following Maximum Atomicity principles

## Quick Links

- **[README.md](README.md)** - Main documentation with architecture-aligned structure
- **[REQUIREMENTS.legacy.md](REQUIREMENTS.legacy.md)** - Original detailed requirements document

## Summary

Nexus SCM is a progressive supply chain management engine that scales from basic inventory tracking to enterprise SCM optimization through three progressive levels:

1. **Level 1**: Basic SCM with trait-based inventory (no database tables)
2. **Level 2**: Chain automation with database-driven suppliers and orders
3. **Level 3**: Enterprise features with AI forecasting and optimization

### Key Architectural Principles

This package adheres to:
- **Maximum Atomicity**: Independent, testable, zero cross-package coupling
- **Contract-Driven**: Communication via Interfaces and Events (through `Nexus\Erp`)
- **Headless Backend**: API-only, no UI/Blade logic
- **Framework Agnostic Core**: Pure PHP in `src/Core/`, Laravel adapter in `src/Adapters/Laravel/`

### Progressive Disclosure Model

The three-level approach aligns with atomic principles:

- **Level 1 (HasSupplyChain trait)** → Laravel Adapter (`src/Adapters/Laravel/Traits/`)
- **Level 2 (DB-driven SCM)** → Core business logic (`src/Core/`)
- **Level 3 (Enterprise features)** → Advanced core services with contracts
- **Cross-package orchestration** → Handled by `Nexus\Erp` orchestration layer

## Documentation Structure

The new README.md provides:
- Progressive journey guide (Level 1 → 2 → 3)
- Atomic package architecture compliance
- Clear separation of concerns (Core vs Adapter vs Orchestration)
- Installation instructions
- Package structure explanation
- Functional and non-functional requirements
- Development phases and timeline
- Testing requirements
- Integration patterns with Nexus ERP

**All detailed specifications remain available in [REQUIREMENTS.legacy.md](REQUIREMENTS.legacy.md)**
