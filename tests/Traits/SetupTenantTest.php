<?php

namespace Tests\Traits;

use App\Models\App;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;

/**
 * Shared helper for setting up tenant test scaffolding.
 *
 * Creates a verified user + tenant + domain + tenant_user pivot,
 * seeds the free plan, and sets the session so BelongsToTenant scope works.
 */
trait SetupTenantTest
{
    protected User $user;
    protected Tenant $tenant;
    protected Domain $domain;

    /**
     * Bootstrap a complete tenant context: user, tenant, domain, pivot, plan, session.
     */
    protected function setUpTenant(string $role = 'owner'): void
    {
        // Ensure the free plan exists
        Plan::factory()->create();

        $this->user = User::factory()->create();

        $this->tenant = Tenant::factory()->create();

        $this->domain = Domain::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        TenantUser::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'role' => $role,
            'accepted_at' => now(),
        ]);

        // Simulate logged-in user with tenant session
        $this->actingAs($this->user);
        session(['current_tenant_id' => $this->tenant->id]);
    }

    /**
     * Create a second tenant context — for cross-tenant isolation tests.
     */
    protected function createOtherTenant(): array
    {
        $otherUser = User::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $otherDomain = Domain::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        TenantUser::create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $otherUser->id,
            'role' => 'owner',
            'accepted_at' => now(),
        ]);

        return compact('otherUser', 'otherTenant', 'otherDomain');
    }

    /**
     * Create an App for the current tenant.
     */
    protected function createApp(array $overrides = []): App
    {
        return App::factory()->create(array_merge([
            'tenant_id' => $this->tenant->id,
        ], $overrides));
    }

    /**
     * Create a Link for the current tenant.
     */
    protected function createLink(array $overrides = []): Link
    {
        $app = $overrides['app_id'] ?? $this->createApp()->id;

        return Link::factory()->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'app_id' => $app,
            'domain_id' => $this->domain->id,
        ], $overrides));
    }
}
