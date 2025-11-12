---
plan: Implement Integration Connector Framework & Pre-built Connectors
version: 1.0
date_created: 2025-01-15
last_updated: 2025-01-15
owner: Development Team
status: Planned
tags: [feature, integration, connectors, architecture, business-logic]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This plan establishes the foundational connector framework for external system integration, implementing the base connector architecture, credential management, and pre-built connectors for SAP, Salesforce, and QuickBooks. This plan addresses FR-IC-001, FR-IC-002, SR-IC-001, SR-IC-002, and ARCH-IC-001 from PRD01-SUB24.

## 1. Requirements & Constraints

- **FR-IC-001**: Support pre-built connectors for common ERP/CRM systems (SAP, Salesforce, QuickBooks)
- **FR-IC-002**: Provide connector framework for custom integration development
- **SR-IC-001**: Encrypt credentials for external systems at rest
- **SR-IC-002**: Implement OAuth 2.0 for secure third-party authentication
- **ARCH-IC-001**: Store connector configurations in SQL with encrypted credentials
- **SEC-001**: All connector credentials must be encrypted using Laravel's encryption
- **SEC-002**: OAuth 2.0 tokens must be refreshed automatically before expiration
- **CON-001**: Connector configuration changes require approval in production (BR-IC-001)
- **CON-002**: Connectors cannot be deleted with active sync schedules (BR-IC-003)
- **GUD-001**: Follow repository pattern for all data access operations
- **GUD-002**: Use Laravel Actions for all business operations
- **GUD-003**: All models must use strict type declarations and PHPDoc
- **PAT-001**: Use Strategy pattern for different connector types
- **PAT-002**: Use Factory pattern for connector instantiation
- **PAT-003**: Use Observer pattern for connector lifecycle events

## 2. Implementation Steps

### GOAL-001: Create Database Schema for Connectors

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| ARCH-IC-001 | Creates SQL tables with encrypted credentials storage for connector configurations | | |
| SR-IC-001 | Implements encrypted credential storage using JSONB with Laravel encryption | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000001_create_connectors_table.php` with tenant_id (UUID), connector_code, connector_name, connector_type enum, external_system_url, authentication_method enum, encrypted credentials JSONB, configuration JSONB, is_active boolean, last_sync_at timestamp, created_by user reference, timestamps, soft deletes, unique constraint on (tenant_id, connector_code), indexes on tenant_id/type/active, foreign key to tenants with cascade delete | | |
| TASK-002 | Create Connector model in `packages/integration-connectors/src/Models/Connector.php` with BelongsToTenant trait, HasActivityLogging trait, fillable fields, casts (credentials => encrypted:array, configuration => array), relationships (tenant, creator, syncConfigurations), scopes (active, byType), accessor for decrypted credentials | | |
| TASK-003 | Create ConnectorFactory in `packages/integration-connectors/database/factories/ConnectorFactory.php` with state methods for each connector type (salesforce, sap, quickbooks), active/inactive states | | |

### GOAL-002: Implement Base Connector Framework

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-IC-002 | Establishes abstract base connector framework for custom integration development | | |
| SR-IC-002 | Implements OAuth 2.0 authentication flow with token refresh in base connector | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-004 | Create ConnectorServiceContract interface in `packages/integration-connectors/src/Contracts/ConnectorServiceContract.php` with methods: testConnection(Connector $connector): bool, authenticate(Connector $connector): array, refreshToken(Connector $connector): array, validateCredentials(array $credentials): bool | | |
| TASK-005 | Create abstract BaseConnector class in `packages/integration-connectors/src/Connectors/BaseConnector.php` with constructor accepting Connector model, protected methods for HTTP client setup, OAuth 2.0 flow (authorize, getToken, refreshToken), credential validation, error handling with retry logic, abstract methods (connect, disconnect, testConnection, getAuthUrl, handleCallback) | | |
| TASK-006 | Create ConnectorFactory class in `packages/integration-connectors/src/Services/ConnectorFactory.php` using Factory pattern to instantiate connector instances based on connector_type (salesforce => SalesforceConnector, sap => SAPConnector, quickbooks => QuickBooksConnector, custom => CustomConnector) | | |

### GOAL-003: Implement Pre-built Salesforce Connector

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-IC-001 | Implements Salesforce connector with OAuth 2.0 authentication and REST API integration | | |
| SR-IC-002 | Implements OAuth 2.0 authentication flow for Salesforce | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create SalesforceConnector class in `packages/integration-connectors/src/Connectors/SalesforceConnector.php` extending BaseConnector with connect() method implementing OAuth 2.0 flow using Guzzle HTTP client, testConnection() using Salesforce REST API identity endpoint, methods for CRUD operations (create, read, update, delete), query method using SOQL, bulk operation support for batch processing, error handling for Salesforce-specific errors | | |
| TASK-008 | Create SalesforceService in `packages/integration-connectors/src/Services/SalesforceService.php` implementing ConnectorServiceContract with methods wrapping SalesforceConnector operations, credential validation for client_id/client_secret/refresh_token, OAuth callback handling | | |
| TASK-009 | Add Salesforce-specific configuration to `packages/integration-connectors/config/integration-connectors.php` with default API version (v60.0), sandbox/production URLs, OAuth scopes, timeout settings | | |

### GOAL-004: Implement Pre-built SAP Connector

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-IC-001 | Implements SAP connector with certificate authentication and SOAP/REST API integration | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-010 | Create SAPConnector class in `packages/integration-connectors/src/Connectors/SAPConnector.php` extending BaseConnector with connect() method supporting certificate-based authentication, testConnection() using SAP OData service metadata endpoint, methods for RFC calls, BAPI operations, IDoc processing, support for both SOAP and REST APIs, error handling for SAP-specific errors (RFC_ERROR_SYSTEM_FAILURE, etc.) | | |
| TASK-011 | Create SAPService in `packages/integration-connectors/src/Services/SAPService.php` implementing ConnectorServiceContract with methods wrapping SAPConnector operations, credential validation for username/password/client_certificate, connection pooling for performance | | |
| TASK-012 | Add SAP-specific configuration to config with default API endpoints (OData, SOAP), RFC destination settings, timeout/retry settings, certificate path configuration | | |

### GOAL-005: Implement Pre-built QuickBooks Connector

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-IC-001 | Implements QuickBooks connector with OAuth 2.0 authentication and REST API integration | | |
| SR-IC-002 | Implements OAuth 2.0 authentication flow for QuickBooks | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-013 | Create QuickBooksConnector class in `packages/integration-connectors/src/Connectors/QuickBooksConnector.php` extending BaseConnector with connect() method implementing OAuth 2.0 flow, testConnection() using QuickBooks CompanyInfo API, methods for financial operations (invoices, payments, customers, vendors), query method using QuickBooks Query Language, batch operation support, error handling for QuickBooks-specific errors | | |
| TASK-014 | Create QuickBooksService in `packages/integration-connectors/src/Services/QuickBooksService.php` implementing ConnectorServiceContract with methods wrapping QuickBooksConnector operations, credential validation for client_id/client_secret/realm_id, OAuth callback handling with token refresh | | |
| TASK-015 | Add QuickBooks-specific configuration to config with sandbox/production URLs, OAuth scopes (accounting, payments), API version, timeout settings, webhook verification token | | |

## 3. Alternatives

- **ALT-001**: Use third-party integration platform (Zapier, Mulesoft) - Rejected due to cost, vendor lock-in, and limited customization
- **ALT-002**: Implement all connectors as microservices - Rejected due to increased operational complexity and deployment overhead
- **ALT-003**: Use RabbitMQ instead of Redis for event streaming - Kept as optional for future high-volume scenarios (KIV-001)

## 4. Dependencies

- **DEP-001**: Laravel Framework ^12.0 (for encryption, HTTP client, queue)
- **DEP-002**: GuzzleHTTP ^7.0 (for HTTP requests to external APIs)
- **DEP-003**: Symfony HTTP Client ^7.0 (alternative HTTP client with async support)
- **DEP-004**: azaharizaman/erp-core ^1.0 (for multi-tenancy, authentication)
- **DEP-005**: lorisleiva/laravel-actions ^2.0 (for action pattern)
- **DEP-006**: SUB01 Multi-Tenancy (for tenant isolation)
- **DEP-007**: SUB02 Authentication & Authorization (for connector access control)
- **DEP-008**: SUB03 Audit Logging (for tracking connector operations)

## 5. Files

**Migrations:**
- `packages/integration-connectors/database/migrations/2025_01_01_000001_create_connectors_table.php`: Connectors table schema

**Models:**
- `packages/integration-connectors/src/Models/Connector.php`: Connector model with encrypted credentials

**Contracts:**
- `packages/integration-connectors/src/Contracts/ConnectorServiceContract.php`: Connector service interface

**Connectors:**
- `packages/integration-connectors/src/Connectors/BaseConnector.php`: Abstract base connector
- `packages/integration-connectors/src/Connectors/SalesforceConnector.php`: Salesforce integration
- `packages/integration-connectors/src/Connectors/SAPConnector.php`: SAP integration
- `packages/integration-connectors/src/Connectors/QuickBooksConnector.php`: QuickBooks integration

**Services:**
- `packages/integration-connectors/src/Services/ConnectorFactory.php`: Factory for connector instantiation
- `packages/integration-connectors/src/Services/SalesforceService.php`: Salesforce service layer
- `packages/integration-connectors/src/Services/SAPService.php`: SAP service layer
- `packages/integration-connectors/src/Services/QuickBooksService.php`: QuickBooks service layer

**Factories:**
- `packages/integration-connectors/database/factories/ConnectorFactory.php`: Test data factory

**Configuration:**
- `packages/integration-connectors/config/integration-connectors.php`: Package configuration

## 6. Testing

- **TEST-001**: Unit test for Connector model encryption/decryption of credentials
- **TEST-002**: Unit test for ConnectorFactory instantiating correct connector types
- **TEST-003**: Unit test for BaseConnector OAuth 2.0 flow with mock HTTP responses
- **TEST-004**: Feature test for SalesforceConnector authentication and testConnection
- **TEST-005**: Feature test for SAPConnector certificate authentication
- **TEST-006**: Feature test for QuickBooksConnector OAuth flow and API calls
- **TEST-007**: Integration test for connector CRUD operations via API
- **TEST-008**: Security test verifying credentials are encrypted at rest
- **TEST-009**: Security test verifying OAuth tokens are refreshed before expiration
- **TEST-010**: Test that connectors cannot be deleted with active sync schedules (BR-IC-003)

## 7. Risks & Assumptions

- **RISK-001**: External system API changes may break connectors - Mitigation: Version connectors, implement adapter pattern for easy updates
- **RISK-002**: OAuth token refresh failures may cause sync interruptions - Mitigation: Implement retry with exponential backoff, alert administrators
- **RISK-003**: Encrypted credentials may be vulnerable if encryption key is compromised - Mitigation: Use Laravel's key rotation, store keys in secure vault (AWS KMS, HashiCorp Vault)
- **ASSUMPTION-001**: External systems provide stable APIs with backward compatibility
- **ASSUMPTION-002**: Authentication credentials are available and valid for external systems
- **ASSUMPTION-003**: Network connectivity is stable between ERP system and external APIs
- **ASSUMPTION-004**: External systems support OAuth 2.0 or certificate-based authentication

## 8. KIV for future implementations

- **KIV-001**: Implement Kafka for high-volume real-time event streaming instead of Redis (ARCH-IC-002)
- **KIV-002**: Add support for GraphQL connectors for modern APIs (IR-IC-002)
- **KIV-003**: Implement connector marketplace for third-party connector plugins
- **KIV-004**: Add AI-powered field mapping suggestions based on entity similarity
- **KIV-005**: Implement connector health monitoring with predictive failure detection
- **KIV-006**: Add support for file-based integrations (CSV, XML, EDI) for legacy systems

## 9. Related PRD / Further Reading

- [PRD01-SUB24: Integration Connectors](../prd/prd-01/PRD01-SUB24-INTEGRATION-CONNECTORS.md)
- [Master PRD](../prd/PRD01-MVP.md)
- [CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- [PACKAGE-DECOUPLING-STRATEGY.md](../architecture/PACKAGE-DECOUPLING-STRATEGY.md)
- [Salesforce REST API Documentation](https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/)
- [SAP OData API Documentation](https://www.sap.com/developer/topics/odata.html)
- [QuickBooks API Documentation](https://developer.intuit.com/app/developer/qbo/docs/get-started)
