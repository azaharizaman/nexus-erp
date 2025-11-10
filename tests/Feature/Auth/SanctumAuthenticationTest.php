<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domains\Core\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SanctumAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $adminUser;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->adminUser = User::factory()->admin()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /**
     * Test that authenticated users can access protected routes.
     */
    public function test_authenticated_user_can_access_protected_routes(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/tenants');

        $response->assertSuccessful();
    }

    /**
     * Test that unauthenticated users cannot access protected routes.
     */
    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/v1/tenants');

        $response->assertUnauthorized();
    }

    /**
     * Test that tokens can be created with abilities.
     */
    public function test_user_can_create_token_with_abilities(): void
    {
        $token = $this->user->createToken('test-token', ['tenant:read', 'tenant:write']);

        $this->assertNotNull($token->plainTextToken);
        $this->assertEquals(['tenant:read', 'tenant:write'], $token->accessToken->abilities);
    }

    /**
     * Test that tokens can be created without abilities (all access).
     */
    public function test_user_can_create_token_without_abilities(): void
    {
        $token = $this->user->createToken('test-token');

        $this->assertNotNull($token->plainTextToken);
        $this->assertEquals(['*'], $token->accessToken->abilities);
    }

    /**
     * Test that token abilities are properly checked.
     */
    public function test_token_abilities_are_properly_checked(): void
    {
        $token = $this->user->createToken('test-token', ['tenant:read']);

        Sanctum::actingAs($this->user, ['tenant:read']);

        $this->assertTrue(auth()->user()->tokenCan('tenant:read'));
        $this->assertFalse(auth()->user()->tokenCan('tenant:write'));
    }

    /**
     * Test that wildcard ability grants all access.
     */
    public function test_wildcard_ability_grants_all_access(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $this->assertTrue(auth()->user()->tokenCan('tenant:read'));
        $this->assertTrue(auth()->user()->tokenCan('tenant:write'));
        $this->assertTrue(auth()->user()->tokenCan('any:ability'));
    }

    /**
     * Test that token expiration is properly configured.
     */
    public function test_token_expiration_is_configured(): void
    {
        $expirationMinutes = config('sanctum.expiration');

        $this->assertNotNull($expirationMinutes);
        $this->assertEquals(480, $expirationMinutes); // 8 hours
    }

    /**
     * Test that user can have multiple tokens.
     */
    public function test_user_can_have_multiple_tokens(): void
    {
        $token1 = $this->user->createToken('token-1', ['tenant:read']);
        $token2 = $this->user->createToken('token-2', ['tenant:write']);

        $this->assertCount(2, $this->user->tokens);
        $this->assertNotEquals($token1->plainTextToken, $token2->plainTextToken);
    }

    /**
     * Test that tokens can be revoked.
     */
    public function test_user_can_revoke_token(): void
    {
        $token = $this->user->createToken('test-token');

        $this->assertCount(1, $this->user->tokens);

        $this->user->tokens()->delete();

        $this->assertCount(0, $this->user->fresh()->tokens);
    }

    /**
     * Test that specific token can be revoked by id.
     */
    public function test_user_can_revoke_specific_token(): void
    {
        $token1 = $this->user->createToken('token-1');
        $token2 = $this->user->createToken('token-2');

        $this->assertCount(2, $this->user->tokens);

        $token1->accessToken->delete();

        $this->assertCount(1, $this->user->fresh()->tokens);
        // After deleting token-1, only token-2 remains (latest)
        $latestToken = $this->user->tokens()->latest()->first();
        $this->assertEquals('token-2', $latestToken->name);
    }

    /**
     * Test that tokens can have descriptive names.
     */
    public function test_tokens_can_have_descriptive_names(): void
    {
        $token = $this->user->createToken('mobile-app-token');

        $this->assertEquals('mobile-app-token', $token->accessToken->name);
    }

    /**
     * Test that different users have isolated tokens.
     */
    public function test_different_users_have_isolated_tokens(): void
    {
        $user2 = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->user->createToken('user1-token');
        $user2->createToken('user2-token');

        $this->assertCount(1, $this->user->tokens);
        $this->assertCount(1, $user2->tokens);
        $this->assertNotEquals($this->user->tokens->first()->id, $user2->tokens->first()->id);
    }

    /**
     * Test that token can be used for API authentication.
     */
    public function test_token_can_be_used_for_api_authentication(): void
    {
        $token = $this->adminUser->createToken('api-token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson('/api/v1/tenants');

        $response->assertSuccessful();
    }

    /**
     * Test that invalid token is rejected.
     */
    public function test_invalid_token_is_rejected(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token-string',
        ])->getJson('/api/v1/tenants');

        $response->assertUnauthorized();
    }

    /**
     * Test sanctum stateful domains configuration.
     */
    public function test_sanctum_stateful_domains_is_configured(): void
    {
        $statefulDomains = config('sanctum.stateful');

        $this->assertIsArray($statefulDomains);
        $this->assertContains('localhost', $statefulDomains);
        $this->assertContains('127.0.0.1', $statefulDomains);
    }

    /**
     * Test sanctum guard configuration.
     */
    public function test_sanctum_guard_is_configured(): void
    {
        $guard = config('sanctum.guard');

        $this->assertIsArray($guard);
        $this->assertContains('web', $guard);
    }

    /**
     * Test that middleware configuration exists.
     */
    public function test_sanctum_middleware_configuration_exists(): void
    {
        $middleware = config('sanctum.middleware');

        $this->assertIsArray($middleware);
        $this->assertArrayHasKey('authenticate_session', $middleware);
        $this->assertArrayHasKey('encrypt_cookies', $middleware);
        $this->assertArrayHasKey('validate_csrf_token', $middleware);
    }
}
