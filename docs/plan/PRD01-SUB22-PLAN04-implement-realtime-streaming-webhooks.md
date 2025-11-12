---
plan: PRD01-SUB22-PLAN04 - Real-Time Streaming & Webhooks
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Laravel ERP Development Team
status: Planned
tags: [feature, notification, real-time, webhook, websocket, streaming, event-broadcasting]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan covers **Real-Time Streaming & Webhooks** for the Notifications & Events module (PRD01-SUB22). It implements WebSocket server with Laravel Reverb/Pusher integration, real-time event broadcasting, webhook management with signature verification, notification scheduling service, and performance optimization for low-latency real-time updates.

## 1. Requirements & Constraints

**Requirements Addressed:**
- **FR-NE-004**: Implement notification scheduling with delivery time optimization
- **FR-NE-008**: Support real-time event streaming via WebSockets for live updates
- **IR-NE-003**: Provide webhook endpoints for third-party system notifications
- **SR-NE-002**: Implement webhook signature verification for security
- **PR-NE-003**: Real-time broadcasting must have < 100ms latency
- **PR-NE-004**: WebSocket server must support 1000+ concurrent connections per server

**Security Constraints:**
- **SEC-001**: All webhook payloads must include HMAC-SHA256 signatures for verification
- **SEC-002**: WebSocket connections must authenticate using Laravel Sanctum tokens
- **SEC-003**: Rate limit WebSocket connections to prevent DoS attacks (100 connections/IP/minute)

**Performance Constraints:**
- **CON-001**: Real-time broadcast latency must be < 100ms (99th percentile)
- **CON-002**: WebSocket server must handle 1000+ concurrent connections per instance
- **CON-003**: Webhook delivery must retry with exponential backoff (max 5 attempts)
- **CON-004**: Notification scheduling processor must handle 10,000+ scheduled notifications/minute

**Guidelines:**
- **GUD-001**: Use Laravel Reverb or Pusher for WebSocket server implementation
- **GUD-002**: Implement channel-based pub/sub pattern for real-time events
- **GUD-003**: Use Laravel Queue with Redis driver for scheduling processing
- **GUD-004**: Store webhook delivery history for 90 days with auto-cleanup

**Patterns:**
- **PAT-001**: Use Observer pattern for event broadcasting to WebSocket channels
- **PAT-002**: Implement Strategy pattern for webhook delivery (HTTP POST, custom protocols)
- **PAT-003**: Use Command pattern for scheduled notification processing
- **PAT-004**: Apply Circuit Breaker pattern for webhook endpoint failures

## 2. Implementation Steps

### GOAL-001: Implement WebSocket Server Infrastructure

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-NE-008 | WebSocket server with Laravel Reverb/Pusher for real-time event streaming | | |
| PR-NE-003 | Broadcast latency < 100ms optimization | | |
| PR-NE-004 | Support 1000+ concurrent connections per server instance | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Install Laravel Reverb package (`composer require laravel/reverb`) or configure Pusher credentials in `.env` | | |
| TASK-002 | Publish Reverb configuration with `php artisan vendor:publish --tag=reverb-config` to `config/reverb.php` | | |
| TASK-003 | Create WebSocket authentication middleware in `app/Domains/Notifications/Http/Middleware/WebSocketAuthMiddleware.php` that validates Sanctum tokens from connection query parameters | | |
| TASK-004 | Configure WebSocket broadcasting driver in `config/broadcasting.php` with `'default' => env('BROADCAST_DRIVER', 'reverb')` and add Reverb connection configuration | | |
| TASK-005 | Create private channel authorization in `routes/channels.php` for `notification.{userId}` channel that checks authenticated user ID matches channel parameter | | |
| TASK-006 | Create Artisan command `php artisan erp:websocket:serve` in `app/Console/Commands/ServeWebSocketCommand.php` that starts Reverb server with `Artisan::call('reverb:start')` | | |
| TASK-007 | Add WebSocket server health check endpoint in `routes/api.php` at `/api/v1/websocket/health` that returns server status and connection count | | |

### GOAL-002: Implement Real-Time Event Broadcasting

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-NE-008 | Channel-based pub/sub for real-time event broadcasting | | |
| PR-NE-003 | Optimize broadcast performance for < 100ms latency | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-008 | Create broadcast event base class in `app/Domains/Notifications/Events/BroadcastableNotificationEvent.php` that implements `ShouldBroadcast` interface with channel routing logic | | |
| TASK-009 | Create notification broadcast event in `app/Domains/Notifications/Events/NotificationBroadcastEvent.php` extending base class with payload including notification ID, type, title, body, timestamp | | |
| TASK-010 | Update `NotificationService` to dispatch `NotificationBroadcastEvent` after successful notification creation for in-app channel | | |
| TASK-011 | Create private broadcast channel `notification.{userId}` in `routes/channels.php` with authorization callback checking user ID matches authenticated user | | |
| TASK-012 | Create public broadcast channel `tenant.{tenantId}.notifications` in `routes/channels.php` for tenant-wide notifications with tenant membership verification | | |
| TASK-013 | Add broadcast configuration in event classes using `broadcastOn()` method returning `PrivateChannel` or `PresenceChannel` instances | | |
| TASK-014 | Create broadcast service in `app/Domains/Notifications/Services/BroadcastService.php` with method `broadcastToUser(int $userId, array $payload)` that dispatches event to user channel | | |
| TASK-015 | Implement broadcast queue job in `app/Domains/Notifications/Jobs/BroadcastNotificationJob.php` for async broadcasting with retry logic (3 attempts, exponential backoff) | | |

### GOAL-003: Implement Webhook Management System

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| IR-NE-003 | Webhook endpoints for third-party system notifications | | |
| SEC-002 | Webhook signature verification with HMAC-SHA256 | | |
| CON-003 | Retry logic with exponential backoff (max 5 attempts) | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-016 | Create webhook migration in `database/migrations/xxxx_create_webhooks_table.php` with columns: id, tenant_id, name, url, secret, events (JSON), is_active, max_retries, retry_delay, last_triggered_at, timestamps | | |
| TASK-017 | Create webhook model in `app/Domains/Notifications/Models/Webhook.php` with relationships to Tenant, casts for events (array), retry_delay (integer) | | |
| TASK-018 | Create webhook delivery log migration in `database/migrations/xxxx_create_webhook_delivery_logs_table.php` with columns: id, webhook_id, event_type, payload (JSON), response_status, response_body, attempt_number, delivered_at, timestamps | | |
| TASK-019 | Create webhook delivery log model in `app/Domains/Notifications/Models/WebhookDeliveryLog.php` with relationship to Webhook | | |
| TASK-020 | Create webhook repository contract in `app/Domains/Notifications/Contracts/WebhookRepositoryContract.php` with methods: findById, findActiveByEvent, create, update, delete | | |
| TASK-021 | Implement webhook repository in `app/Domains/Notifications/Repositories/WebhookRepository.php` with query optimization using indexes on tenant_id, is_active, events | | |
| TASK-022 | Create webhook delivery service in `app/Domains/Notifications/Services/WebhookDeliveryService.php` with method `deliver(Webhook $webhook, string $event, array $payload): bool` that signs payload with HMAC-SHA256 and sends HTTP POST request | | |
| TASK-023 | Implement signature generation in webhook delivery service using `hash_hmac('sha256', json_encode($payload), $webhook->secret)` and include in `X-Webhook-Signature` header | | |
| TASK-024 | Create webhook retry job in `app/Domains/Notifications/Jobs/RetryWebhookDeliveryJob.php` with exponential backoff: delays = [60s, 300s, 900s, 3600s, 7200s] for 5 attempts | | |
| TASK-025 | Create webhook event listener in `app/Domains/Notifications/Listeners/WebhookEventListener.php` that triggers webhook delivery for subscribed events | | |

### GOAL-004: Implement Notification Scheduling Service

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-NE-004 | Notification scheduling with delivery time optimization | | |
| CON-004 | Handle 10,000+ scheduled notifications per minute | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-026 | Create scheduled notification migration in `database/migrations/xxxx_create_scheduled_notifications_table.php` with columns: id, tenant_id, notification_template_id, scheduled_at, delivered_at, status (pending/delivered/failed), recipient_id, recipient_type, payload (JSON), timestamps | | |
| TASK-027 | Create scheduled notification model in `app/Domains/Notifications/Models/ScheduledNotification.php` with relationships to Tenant, NotificationTemplate, morphTo recipient | | |
| TASK-028 | Create scheduled notification repository contract in `app/Domains/Notifications/Contracts/ScheduledNotificationRepositoryContract.php` with methods: findPendingForDelivery, create, markAsDelivered, markAsFailed | | |
| TASK-029 | Implement scheduled notification repository in `app/Domains/Notifications/Repositories/ScheduledNotificationRepository.php` with query optimization for pending status and scheduled_at <= now() | | |
| TASK-030 | Create notification scheduler service in `app/Domains/Notifications/Services/NotificationSchedulerService.php` with method `scheduleNotification(array $data): ScheduledNotification` validating scheduled_at is in future | | |
| TASK-031 | Create scheduled notification processor command in `app/Console/Commands/ProcessScheduledNotificationsCommand.php` that fetches pending notifications and dispatches delivery jobs | | |
| TASK-032 | Implement batch processing in command using `chunk(1000)` to handle high volume efficiently without memory issues | | |
| TASK-033 | Add command to Laravel scheduler in `app/Console/Kernel.php` with `$schedule->command('erp:notifications:process-scheduled')->everyMinute()` | | |
| TASK-034 | Create scheduled notification delivery job in `app/Domains/Notifications/Jobs/DeliverScheduledNotificationJob.php` that calls NotificationService::send() and updates scheduled notification status | | |
| TASK-035 | Implement delivery time optimization logic in scheduler service using quiet hours check: if scheduled during 22:00-08:00, defer to 08:00 next day | | |

### GOAL-005: Performance Testing and Monitoring

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PR-NE-003 | Validate broadcast latency < 100ms | | |
| PR-NE-004 | Validate 1000+ concurrent WebSocket connections | | |
| CON-004 | Validate 10,000+ scheduled notifications/minute throughput | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-036 | Create WebSocket performance test in `tests/Performance/WebSocketPerformanceTest.php` using `symfony/websocket-client` to establish 1000 concurrent connections and measure latency | | |
| TASK-037 | Create broadcast latency test measuring time between event dispatch and client reception, asserting 99th percentile < 100ms using 1000 sample broadcasts | | |
| TASK-038 | Create webhook delivery performance test in `tests/Performance/WebhookDeliveryPerformanceTest.php` simulating 100 webhook deliveries with mock HTTP responses and measuring throughput | | |
| TASK-039 | Create scheduled notification throughput test in `tests/Performance/ScheduledNotificationPerformanceTest.php` creating 10,000 scheduled notifications and measuring processing time < 60 seconds | | |
| TASK-040 | Add monitoring metrics to WebSocket server tracking connection count, message rate, error rate using Laravel Pulse custom recorders | | |
| TASK-041 | Create webhook monitoring dashboard query in `app/Domains/Notifications/Services/WebhookMonitoringService.php` with methods: getDeliverySuccessRate(), getAverageResponseTime(), getFailedWebhooks() | | |
| TASK-042 | Implement circuit breaker pattern in webhook delivery service: after 5 consecutive failures, pause webhook for 1 hour before retrying | | |

## 3. Alternatives

- **ALT-001**: Use Pusher instead of Laravel Reverb for WebSocket server - Rejected due to cost for high-volume scenarios and vendor lock-in concerns
- **ALT-002**: Use Server-Sent Events (SSE) instead of WebSockets - Rejected due to lack of bidirectional communication and higher bandwidth usage for frequent updates
- **ALT-003**: Use polling instead of WebSockets for real-time updates - Rejected due to higher server load and latency (1-5 seconds vs < 100ms)
- **ALT-004**: Store webhook delivery history indefinitely - Rejected due to storage costs; 90-day retention provides sufficient audit trail

## 4. Dependencies

- **DEP-001**: Laravel Reverb package (`laravel/reverb`) or Pusher account for WebSocket server
- **DEP-002**: Redis server for Laravel Queue driver and pub/sub messaging
- **DEP-003**: Laravel Horizon for queue monitoring and management
- **DEP-004**: Guzzle HTTP client (`guzzlehttp/guzzle`) for webhook HTTP requests
- **DEP-005**: Notification infrastructure from PLAN01 (templates, in-app notifications)
- **DEP-006**: Laravel Sanctum for WebSocket authentication
- **DEP-007**: Laravel Broadcasting configuration and routes

## 5. Files

- **database/migrations/xxxx_create_webhooks_table.php**: Webhook management table schema
- **database/migrations/xxxx_create_webhook_delivery_logs_table.php**: Webhook delivery history table
- **database/migrations/xxxx_create_scheduled_notifications_table.php**: Scheduled notification queue table
- **app/Domains/Notifications/Models/Webhook.php**: Webhook Eloquent model
- **app/Domains/Notifications/Models/WebhookDeliveryLog.php**: Webhook delivery log model
- **app/Domains/Notifications/Models/ScheduledNotification.php**: Scheduled notification model
- **app/Domains/Notifications/Contracts/WebhookRepositoryContract.php**: Webhook repository interface
- **app/Domains/Notifications/Contracts/ScheduledNotificationRepositoryContract.php**: Scheduled notification repository interface
- **app/Domains/Notifications/Repositories/WebhookRepository.php**: Webhook repository implementation
- **app/Domains/Notifications/Repositories/ScheduledNotificationRepository.php**: Scheduled notification repository
- **app/Domains/Notifications/Services/BroadcastService.php**: Real-time broadcasting service
- **app/Domains/Notifications/Services/WebhookDeliveryService.php**: Webhook delivery and signature service
- **app/Domains/Notifications/Services/NotificationSchedulerService.php**: Notification scheduling service
- **app/Domains/Notifications/Services/WebhookMonitoringService.php**: Webhook monitoring and circuit breaker
- **app/Domains/Notifications/Events/BroadcastableNotificationEvent.php**: Base broadcast event class
- **app/Domains/Notifications/Events/NotificationBroadcastEvent.php**: Notification broadcast event
- **app/Domains/Notifications/Listeners/WebhookEventListener.php**: Webhook event listener
- **app/Domains/Notifications/Jobs/BroadcastNotificationJob.php**: Async broadcast job
- **app/Domains/Notifications/Jobs/RetryWebhookDeliveryJob.php**: Webhook retry with exponential backoff
- **app/Domains/Notifications/Jobs/DeliverScheduledNotificationJob.php**: Scheduled notification delivery job
- **app/Domains/Notifications/Http/Middleware/WebSocketAuthMiddleware.php**: WebSocket authentication middleware
- **app/Console/Commands/ServeWebSocketCommand.php**: WebSocket server start command
- **app/Console/Commands/ProcessScheduledNotificationsCommand.php**: Scheduled notification processor
- **routes/channels.php**: WebSocket channel definitions and authorization
- **config/broadcasting.php**: Laravel broadcasting configuration
- **config/reverb.php**: Laravel Reverb configuration
- **tests/Performance/WebSocketPerformanceTest.php**: WebSocket connection and latency tests
- **tests/Performance/WebhookDeliveryPerformanceTest.php**: Webhook delivery throughput tests
- **tests/Performance/ScheduledNotificationPerformanceTest.php**: Scheduled notification processing tests

## 6. Testing

- **TEST-001**: Feature test for WebSocket authentication middleware validating Sanctum token in connection query parameters
- **TEST-002**: Feature test for private channel authorization ensuring user can only subscribe to own notification channel
- **TEST-003**: Feature test for real-time notification broadcasting verifying event dispatch and payload structure
- **TEST-004**: Feature test for webhook creation with HMAC-SHA256 secret generation
- **TEST-005**: Feature test for webhook delivery with signature verification
- **TEST-006**: Feature test for webhook retry logic with exponential backoff delays (60s, 300s, 900s, 3600s, 7200s)
- **TEST-007**: Feature test for webhook circuit breaker pausing after 5 consecutive failures
- **TEST-008**: Feature test for scheduled notification creation validating scheduled_at is in future
- **TEST-009**: Feature test for scheduled notification processing command fetching pending notifications
- **TEST-010**: Feature test for quiet hours optimization deferring 22:00-08:00 schedules to 08:00 next day
- **TEST-011**: Unit test for webhook signature generation matching HMAC-SHA256 algorithm
- **TEST-012**: Unit test for broadcast service channel routing to correct user/tenant channels
- **TEST-013**: Performance test for WebSocket server handling 1000+ concurrent connections
- **TEST-014**: Performance test for broadcast latency measuring 99th percentile < 100ms
- **TEST-015**: Performance test for scheduled notification throughput processing 10,000+ per minute

## 7. Risks & Assumptions

- **RISK-001**: WebSocket connections may drop during network interruptions - Mitigated by implementing auto-reconnect logic on client side
- **RISK-002**: Webhook endpoints may be unavailable causing delivery failures - Mitigated by retry logic with exponential backoff and circuit breaker pattern
- **RISK-003**: High volume of scheduled notifications may overwhelm queue system - Mitigated by batch processing with chunk(1000) and horizontal scaling of queue workers
- **RISK-004**: WebSocket server may become bottleneck under load - Mitigated by horizontal scaling with load balancer and Redis pub/sub for cross-server broadcasting

- **ASSUMPTION-001**: Laravel Reverb or Pusher is available and configured for WebSocket server
- **ASSUMPTION-002**: Redis server is available for queue driver and pub/sub
- **ASSUMPTION-003**: External webhook endpoints respond within 30 seconds (default timeout)
- **ASSUMPTION-004**: Clients implement auto-reconnect logic for WebSocket connections

## 8. KIV for Future Implementations

- **KIV-001**: Implement WebSocket presence channels for user online/offline status tracking
- **KIV-002**: Add WebSocket message compression (gzip) to reduce bandwidth usage for high-frequency updates
- **KIV-003**: Implement webhook payload encryption for sensitive data protection
- **KIV-004**: Add webhook rate limiting per endpoint to prevent abuse
- **KIV-005**: Implement notification delivery time prediction based on historical success rates
- **KIV-006**: Add support for webhook custom headers and authentication schemes (OAuth, API keys)
- **KIV-007**: Implement WebSocket message queueing for offline clients with automatic replay on reconnection

## 9. Related PRD / Further Reading

- **[PRD01-SUB22: Notifications & Events](../prd/prd-01/PRD01-SUB22-NOTIFICATIONS-EVENTS.md)** - Master Sub-PRD for this module
- **[PRD01-SUB22-PLAN01](PRD01-SUB22-PLAN01-implement-notification-infrastructure-templates.md)** - Notification infrastructure foundation
- **[PRD01-SUB22-PLAN02](PRD01-SUB22-PLAN02-implement-multichannel-delivery-retry.md)** - Multi-channel delivery implementation
- **[PRD01-SUB22-PLAN03](PRD01-SUB22-PLAN03-implement-event-subscriptions-preferences.md)** - Event subscriptions and preferences
- **[Laravel Broadcasting Documentation](https://laravel.com/docs/broadcasting)** - Laravel real-time broadcasting guide
- **[Laravel Reverb Documentation](https://laravel.com/docs/reverb)** - Laravel WebSocket server
- **[Pusher Documentation](https://pusher.com/docs)** - Alternative WebSocket service
- **[Webhook Security Best Practices](https://webhooks.fyi/security/hmac)** - HMAC signature verification
