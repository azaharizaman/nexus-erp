# Architecture Decision Record (ADR-001)
## Core/Adapter Separation in nexus-sequencing Package

**Date:** 2024-12-19  
**Status:** Accepted ✅  
**Decision Maker:** Development Team  
**Context:** Phase 2.1 Core Separation Implementation

---

## Decision

We have decided to implement a **hexagonal architecture** (ports and adapters) pattern in the nexus-sequencing package to achieve **maximum atomicity** as defined in the Nexus ERP Architecture Document.

---

## Context

The nexus-sequencing package contained business logic tightly coupled to Laravel framework components, violating the **Maximum Atomicity** principle. This coupling made:

1. **Unit testing difficult** - Required full Laravel application bootstrap
2. **Code reuse impossible** - Business logic locked to Laravel framework
3. **Migration risky** - Future framework changes would break business logic
4. **Performance suboptimal** - Framework overhead for pure computational tasks

---

## Solution Architecture

### Core/ Directory (Framework-Agnostic)

**Purpose:** Contains pure PHP business logic with zero external dependencies

**Structure:**
```
Core/
├── ValueObjects/     # Immutable data containers (6 classes)
├── Contracts/        # Interface definitions (3 contracts)  
├── Services/         # Business orchestration (2 services)
└── Engine/          # Computational logic (1 evaluator)
```

**Constraints:**
- ✅ Must pass PHPStan Level 8 analysis
- ✅ Zero Laravel framework dependencies
- ✅ Immutable Value Objects only
- ✅ Interface-driven design (dependency inversion)

### Adapters/ Directory (Framework Bridge)

**Purpose:** Bridges Core business logic to specific framework implementations

**Implementation:**
- `EloquentCounterRepository`: Translates Core contracts to Laravel Eloquent
- Atomic database operations with SELECT FOR UPDATE
- Transaction safety with proper rollback handling
- Eloquent model integration while maintaining Core interface contracts

### Actions/ Directory (Presentation Layer)

**Purpose:** Laravel-specific presentation logic delegating to Core services

**Responsibilities:**
- HTTP request/response handling
- Laravel event dispatching
- Framework-specific logging and auditing
- Dependency injection of Core services

---

## Benefits Realized

### 1. Framework Independence
- **Core business logic** runs in any PHP environment (Symfony, standalone, etc.)
- **Testing isolation** - Unit tests require no database or framework bootstrap
- **Migration safety** - Framework changes cannot break business rules

### 2. Type Safety & Quality
- **PHPStan Level 8** compliance (strictest static analysis)
- **Immutable Value Objects** prevent accidental state mutations
- **Interface contracts** enable dependency injection and mocking

### 3. Performance & Scalability
- **Pure PHP execution** for computational logic (pattern evaluation, reset strategies)
- **Selective framework usage** - Only when persistence or HTTP handling required
- **Caching potential** - Pure functions can be memoized safely

### 4. Developer Experience
- **Clear boundaries** between business logic and framework concerns
- **Easy testing** with lightweight mocks instead of database fixtures
- **Reusable components** can be extracted to other packages or applications

---

## Implementation Evidence

### Static Analysis Results
```bash
vendor/bin/phpstan analyze src/Core/ --level=8
Result: ✅ 0 errors (12/12 files analyzed)
```

### Unit Testing Results
```bash
./vendor/bin/pest packages/nexus-sequencing/tests/Unit/Core/
Result: ✅ 27 tests passed, 85 assertions
```

### Framework Independence Verification
```bash
php scripts/verify-core.php
Result: ✅ All Core services instantiate without Laravel
```

---

## Trade-offs

### Accepted Trade-offs

1. **Initial Complexity**: More files and directory structure
   - **Mitigation**: Clear naming conventions and comprehensive documentation

2. **Learning Curve**: Developers must understand hexagonal architecture
   - **Mitigation**: Detailed ADR and examples provided

3. **Service Provider Setup**: Additional DI container configuration
   - **Mitigation**: Auto-discovery through package service providers

### Rejected Alternatives

1. **Monolithic Service Classes**: Keep all logic in Laravel-specific services
   - **Rejected**: Violates Maximum Atomicity principle

2. **Repository Pattern Only**: Just extract database access
   - **Rejected**: Business logic would still be coupled to Laravel

3. **Full Microservice**: Separate HTTP service for sequencing
   - **Rejected**: Over-engineering for current scale requirements

---

## Compliance

This implementation satisfies the following Nexus ERP Architecture Document requirements:

✅ **Maximum Atomicity**: Core business logic is framework-independent  
✅ **SOLID Principles**: Clear separation of concerns and dependency inversion  
✅ **Contracts First**: All cross-layer communication via interfaces  
✅ **Immutability**: Value Objects prevent accidental mutations  
✅ **Type Safety**: PHPStan Level 8 compliance maintained  

---

## Future Considerations

### Next Steps
1. **Phase 2.2**: Apply same pattern to other atomic packages
2. **Performance Optimization**: Add caching layer for pattern evaluation
3. **Enhanced Testing**: Implement property-based testing for Value Objects

### Extension Points
- **Custom Pattern Evaluators**: Implement `PatternEvaluatorInterface` for complex patterns
- **Alternative Repositories**: Implement `CounterRepositoryInterface` for Redis, MongoDB, etc.
- **Custom Reset Strategies**: Implement `ResetStrategyInterface` for business-specific reset logic

---

## Conclusion

The hexagonal architecture implementation successfully achieves the Maximum Atomicity principle while maintaining backward compatibility with existing Laravel integrations. The separation enables true code reusability, enhanced testability, and future framework migration safety.

**Decision Status: Accepted and Implemented ✅**

---

*This ADR documents the successful implementation of Phase 2.1 Core Separation for the nexus-sequencing package as part of the broader Nexus ERP modular architecture initiative.*