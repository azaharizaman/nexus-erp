# Changelog

All notable changes to `backoffice` will be documented in this file.

## Unreleased

### Added
- **Position Model** - Structured job position management system
  - Position model with company and optional department relationships
  - PositionType enum with 10 hierarchical types (C-Level to Assistant)
  - Department precedence logic (staff department overrides position default)
  - Grade/salary band management through `gred` field
  - Comprehensive query scopes (active, byCompany, byDepartment, byType, management, executive)
  - Position-based staff filtering and reporting
  - Full authorization through PositionPolicy
- **Position Factory** - Comprehensive factory with position type states
  - States for all 10 position types (cLevel, seniorManagement, management, etc.)
  - Support for default department assignment
  - Active/inactive states
- **Staff Model Updates** - Position integration and department precedence
  - Changed staff.position from string to staff.position_id (foreign key)
  - Added position() relationship
  - Added getEffectiveDepartment() and getEffectiveDepartmentId() methods
  - Department precedence: staff's department takes precedence over position's default
- **StaffTransfer Updates** - Position tracking in transfers
  - Added from_position_id and to_position_id fields
  - fromPosition() and toPosition() relationship methods
  - Automatic position tracking in transfer observer
  - StaffTransferFactory updated with withPositionChange() state
- **Position Documentation** - Comprehensive guide in docs/positions.md
  - PositionType enum reference with all 10 types
  - Department precedence logic explanation with examples
  - Usage examples for creating and querying positions
  - Staff assignment examples
  - Authorization guide
  - Migration guide from string-based positions
  - Best practices
- **Model Factories** - Comprehensive factory classes for all models
  - CompanyFactory with states: active, inactive, childOf, root
  - OfficeFactory with states: active, inactive, childOf, root
  - DepartmentFactory with states: active, inactive, childOf, root
  - StaffFactory with 14+ states including resigned, onProbation, manager, ceo, withPosition
  - UnitFactory and UnitGroupFactory with active/inactive states
  - OfficeTypeFactory with active/inactive states
  - All factories use realistic fake data via Faker
  - Support for hierarchical relationships and complex object graphs
- **Factory Documentation** - Comprehensive guide in docs/factories.md
  - Usage examples for all factories
  - Best practices and patterns
  - Seeding examples
  - Testing examples
- **Updated Copilot Instructions** - Added model factory requirements and guidelines

### Changed
- **Breaking: Staff Migration** - staff.position (string) replaced with staff.position_id (foreign key)
  - Requires data migration for existing applications
  - See docs/positions.md for migration guide
- **Breaking: StaffTransfer Migration** - from_position/to_position (strings) replaced with foreign keys
  - from_position_id and to_position_id now reference positions table
- **OrganizationalChart Helper** - Updated to use position relationship
  - Changed all $staff->position to $staff->position?->name
  - Null-safe navigation for optional positions
- **All Tests Updated** - Converted all tests to use model factories instead of manual creation
  - Feature tests: CompanyTest, OfficeTest, DepartmentTest, StaffTest, and others
  - Unit tests: CompanyObserverTest, HasHierarchyTraitTest, EnumsTest
  - Added 23 comprehensive Position tests
  - Simplified test setup code by 30-40% in many cases
  - Improved test readability with expressive factory states
- **Model newFactory() Methods** - Added factory registration to all 9 models (including Position)

### Documentation
- Added docs/positions.md with comprehensive position management documentation
- Added docs/factories.md with comprehensive factory documentation
- Updated docs/README.md to reference Position and factory documentation
- Updated docs/models.md to include Position model
- Updated .github/copilot-instructions.md with factory best practices
- Updated Quick Start guide to demonstrate factory usage

## 1.0.0 - 2025-10-30

- Initial release
- Company hierarchy management
- Office hierarchy management
- Department hierarchy management
- Staff management with office/department assignment
- Unit and unit group management
- Comprehensive model relationships
- Policy-based authorization
- Observer pattern implementation
- Console commands for management
- Full documentation suite

## 1.0.1 - 2025-10-30

- Update test code coverage
- Update StaffFactory to use the new PositionFactory