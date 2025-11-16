<?php

declare(strict_types=1);

use Nexus\Crm\Core\WebhookIntegration;
use Nexus\Crm\Models\CrmEntity;
use Nexus\Crm\Models\CrmDefinition;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;

it('delivers webhook successfully and fires delivered event', function () {
    Http::fake([
        'https://example.com/*' => Http::response(['ok' => true], 200),
    ]);

    Event::fake();

    $definition = CrmDefinition::create([
        'name' => 'webhook-test',
        'type' => 'lead',
        'schema' => [],
        'is_active' => true,
    ]);

    $entity = CrmEntity::create([
        'entity_type' => 'lead',
        'definition_id' => $definition->id,
        'owner_id' => 'user-1',
        'data' => ['email' => 'foo@example.com'],
        'status' => 'active',
    ]);

    $integration = new WebhookIntegration();

    $integration->execute($entity, [
        'url' => 'https://example.com/hook',
        'method' => 'POST',
        'secret' => 'shhh',
    ]);

    // Assert that a request was sent
    Http::assertSent(function ($request) {
        return $request->url() === 'https://example.com/hook' && $request->method() === 'POST';
    });

    // Assert event fired
    Event::assertDispatched(Nexus\Crm\Events\WebhookDeliveredEvent::class);
});

it('fires failed event on non-success response', function () {
    Http::fake([
        'https://example.net/*' => Http::response(['error' => true], 500),
    ]);

    Event::fake();

    $definition = CrmDefinition::create([
        'name' => 'webhook-test-2',
        'type' => 'lead',
        'schema' => [],
        'is_active' => true,
    ]);

    $entity = CrmEntity::create([
        'entity_type' => 'lead',
        'definition_id' => $definition->id,
        'owner_id' => 'user-1',
        'data' => ['email' => 'foo@example.net'],
        'status' => 'active',
    ]);

    $integration = new WebhookIntegration();

    try {
        $integration->execute($entity, [
            'url' => 'https://example.net/hook',
            'method' => 'POST',
        ]);
    } catch (\Illuminate\Http\Client\RequestException $e) {
        // Expected exception thrown by Laravel HTTP client; we still expect failed event to be dispatched
    }

    Event::assertDispatched(Nexus\Crm\Events\WebhookFailedEvent::class);
});
