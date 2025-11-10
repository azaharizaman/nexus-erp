<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domains\Core\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SanctumTokenAbilitiesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /**
     * Test core domain abilities.
     */
    public function test_token_with_tenant_read_ability(): void
    {
        Sanctum::actingAs($this->user, ['tenant:read']);

        $this->assertTrue(auth()->user()->tokenCan('tenant:read'));
        $this->assertFalse(auth()->user()->tokenCan('tenant:write'));
        $this->assertFalse(auth()->user()->tokenCan('tenant:delete'));
    }

    /**
     * Test multiple abilities on same token.
     */
    public function test_token_with_multiple_abilities(): void
    {
        Sanctum::actingAs($this->user, ['tenant:read', 'tenant:write', 'user:read']);

        $this->assertTrue(auth()->user()->tokenCan('tenant:read'));
        $this->assertTrue(auth()->user()->tokenCan('tenant:write'));
        $this->assertTrue(auth()->user()->tokenCan('user:read'));
        $this->assertFalse(auth()->user()->tokenCan('tenant:delete'));
    }

    /**
     * Test inventory domain abilities.
     */
    public function test_token_with_inventory_abilities(): void
    {
        Sanctum::actingAs($this->user, ['inventory:read', 'inventory:write']);

        $this->assertTrue(auth()->user()->tokenCan('inventory:read'));
        $this->assertTrue(auth()->user()->tokenCan('inventory:write'));
        $this->assertFalse(auth()->user()->tokenCan('warehouse:read'));
    }

    /**
     * Test sales domain abilities.
     */
    public function test_token_with_sales_abilities(): void
    {
        Sanctum::actingAs($this->user, ['customer:read', 'order:read', 'quotation:write']);

        $this->assertTrue(auth()->user()->tokenCan('customer:read'));
        $this->assertTrue(auth()->user()->tokenCan('order:read'));
        $this->assertTrue(auth()->user()->tokenCan('quotation:write'));
        $this->assertFalse(auth()->user()->tokenCan('order:write'));
    }

    /**
     * Test purchasing domain abilities.
     */
    public function test_token_with_purchasing_abilities(): void
    {
        Sanctum::actingAs($this->user, ['vendor:read', 'purchase:write']);

        $this->assertTrue(auth()->user()->tokenCan('vendor:read'));
        $this->assertTrue(auth()->user()->tokenCan('purchase:write'));
        $this->assertFalse(auth()->user()->tokenCan('goods-receipt:write'));
    }

    /**
     * Test accounting domain abilities.
     */
    public function test_token_with_accounting_abilities(): void
    {
        Sanctum::actingAs($this->user, ['accounting:read', 'reports:read']);

        $this->assertTrue(auth()->user()->tokenCan('accounting:read'));
        $this->assertTrue(auth()->user()->tokenCan('reports:read'));
        $this->assertFalse(auth()->user()->tokenCan('accounting:write'));
    }

    /**
     * Test backoffice domain abilities.
     */
    public function test_token_with_backoffice_abilities(): void
    {
        Sanctum::actingAs($this->user, ['company:read', 'office:read', 'department:write']);

        $this->assertTrue(auth()->user()->tokenCan('company:read'));
        $this->assertTrue(auth()->user()->tokenCan('office:read'));
        $this->assertTrue(auth()->user()->tokenCan('department:write'));
        $this->assertFalse(auth()->user()->tokenCan('staff:write'));
    }

    /**
     * Test read-only API ability.
     */
    public function test_token_with_read_only_api_ability(): void
    {
        Sanctum::actingAs($this->user, ['api:read-only']);

        $this->assertTrue(auth()->user()->tokenCan('api:read-only'));
        $this->assertFalse(auth()->user()->tokenCan('tenant:write'));
        $this->assertFalse(auth()->user()->tokenCan('api:full-access'));
    }

    /**
     * Test full access API ability.
     */
    public function test_token_with_full_access_api_ability(): void
    {
        Sanctum::actingAs($this->user, ['api:full-access']);

        $this->assertTrue(auth()->user()->tokenCan('api:full-access'));
        // Note: 'api:full-access' is just one ability, not wildcard
        $this->assertFalse(auth()->user()->tokenCan('tenant:read'));
    }

    /**
     * Test that abilities follow domain:action pattern.
     */
    public function test_abilities_follow_consistent_pattern(): void
    {
        $abilities = [
            'tenant:read',
            'tenant:write',
            'user:read',
            'inventory:read',
            'customer:write',
            'vendor:delete',
        ];

        Sanctum::actingAs($this->user, $abilities);

        foreach ($abilities as $ability) {
            $this->assertTrue(auth()->user()->tokenCan($ability));
            // Verify pattern: domain:action
            $this->assertStringContainsString(':', $ability);
        }
    }

    /**
     * Test that token without specific ability is denied.
     */
    public function test_token_without_ability_is_denied(): void
    {
        Sanctum::actingAs($this->user, ['tenant:read']);

        $this->assertFalse(auth()->user()->tokenCan('tenant:write'));
        $this->assertFalse(auth()->user()->tokenCan('user:read'));
        $this->assertFalse(auth()->user()->tokenCan('inventory:write'));
    }

    /**
     * Test that empty abilities array means no access (except wildcard).
     */
    public function test_token_with_empty_abilities(): void
    {
        Sanctum::actingAs($this->user, []);

        $this->assertFalse(auth()->user()->tokenCan('tenant:read'));
        $this->assertFalse(auth()->user()->tokenCan('tenant:write'));
    }

    /**
     * Test settings domain abilities.
     */
    public function test_token_with_settings_abilities(): void
    {
        Sanctum::actingAs($this->user, ['settings:read']);

        $this->assertTrue(auth()->user()->tokenCan('settings:read'));
        $this->assertFalse(auth()->user()->tokenCan('settings:write'));
    }

    /**
     * Test warehouse and stock abilities.
     */
    public function test_token_with_warehouse_and_stock_abilities(): void
    {
        Sanctum::actingAs($this->user, ['warehouse:read', 'stock:read', 'stock:write']);

        $this->assertTrue(auth()->user()->tokenCan('warehouse:read'));
        $this->assertTrue(auth()->user()->tokenCan('stock:read'));
        $this->assertTrue(auth()->user()->tokenCan('stock:write'));
        $this->assertFalse(auth()->user()->tokenCan('warehouse:write'));
    }

    /**
     * Test that ability checks are case-sensitive.
     */
    public function test_ability_checks_are_case_sensitive(): void
    {
        Sanctum::actingAs($this->user, ['tenant:read']);

        $this->assertTrue(auth()->user()->tokenCan('tenant:read'));
        $this->assertFalse(auth()->user()->tokenCan('Tenant:Read'));
        $this->assertFalse(auth()->user()->tokenCan('TENANT:READ'));
    }

    /**
     * Test creating token with documented abilities.
     */
    public function test_creating_tokens_with_various_ability_combinations(): void
    {
        // Read-only token
        $readToken = $this->user->createToken('read-only', [
            'tenant:read',
            'user:read',
            'inventory:read',
        ]);

        $this->assertCount(3, $readToken->accessToken->abilities);

        // Write token
        $writeToken = $this->user->createToken('write-access', [
            'tenant:write',
            'inventory:write',
            'customer:write',
        ]);

        $this->assertCount(3, $writeToken->accessToken->abilities);

        // Admin token
        $adminToken = $this->user->createToken('admin-access', ['*']);

        $this->assertEquals(['*'], $adminToken->accessToken->abilities);
    }
}
