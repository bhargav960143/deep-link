<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\Link;
use App\Models\Plan;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;
use Tests\Traits\SetupTenantTest;

class TenantPoliciesTest extends TestCase
{
    use RefreshDatabase, SetupTenantTest;

    public function test_view_any_is_authorized(): void
    {
        $this->setUpTenant();
        $this->assertTrue($this->user->can('viewAny', App::class));
        $this->assertTrue($this->user->can('viewAny', Link::class));
    }

    public function test_create_limits_under_plan(): void
    {
        // 1. Plan with 1 app and 1 link limit
        $this->setUpTenant();
        $plan = Plan::where('slug', 'free')->first();
        $plan->update([
            'apps_limit' => 1,
            'links_limit' => 1,
        ]);

        // First app / link creation allowed
        $this->assertTrue($this->user->can('create', App::class));
        $this->assertTrue($this->user->can('create', Link::class));

        // Create 1 App and 1 Link
        $app = $this->createApp();
        $link = $this->createLink(['app_id' => $app->id]);

        // Second creation should NOT be allowed (limit reached)
        $this->assertFalse($this->user->can('create', App::class));
        $this->assertFalse($this->user->can('create', Link::class));
    }

    public function test_create_limits_unlimited_plan(): void
    {
        // 2. Plan with unlimited (-1) apps and links
        $this->setUpTenant();
        $plan = Plan::where('slug', 'free')->first();
        $plan->update([
            'apps_limit' => -1,
            'links_limit' => -1,
        ]);

        // Create some apps and links
        $app = $this->createApp();
        $link = $this->createLink(['app_id' => $app->id]);

        // Should still be allowed to create more
        $this->assertTrue($this->user->can('create', App::class));
        $this->assertTrue($this->user->can('create', Link::class));
    }

    public function test_view_and_update_enforces_tenant_ownership(): void
    {
        $this->setUpTenant();
        $app = $this->createApp();
        $link = $this->createLink(['app_id' => $app->id]);

        // Authorized for owned entities
        $this->assertTrue($this->user->can('view', $app));
        $this->assertTrue($this->user->can('update', $app));
        $this->assertTrue($this->user->can('view', $link));
        $this->assertTrue($this->user->can('update', $link));

        // Create another tenant's context
        $other = $this->createOtherTenant();
        $otherApp = App::factory()->create(['tenant_id' => $other['otherTenant']->id]);
        $otherLink = Link::factory()->create([
            'tenant_id' => $other['otherTenant']->id,
            'app_id' => $otherApp->id,
            'domain_id' => $other['otherDomain']->id,
        ]);

        // Unauthorized for other tenant's entities
        $this->assertFalse($this->user->can('view', $otherApp));
        $this->assertFalse($this->user->can('update', $otherApp));
        $this->assertFalse($this->user->can('view', $otherLink));
        $this->assertFalse($this->user->can('update', $otherLink));
    }

    public function test_delete_enforces_roles(): void
    {
        // 1. Owner can delete
        $this->setUpTenant('owner');
        $app = $this->createApp();
        $link = $this->createLink(['app_id' => $app->id]);

        $this->assertTrue($this->user->can('delete', $app));
        $this->assertTrue($this->user->can('delete', $link));

        // 2. Admin can delete
        $adminUser = User::factory()->create();
        TenantUser::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $adminUser->id,
            'role' => 'admin',
            'accepted_at' => now(),
        ]);
        $this->assertTrue($adminUser->can('delete', $app));
        $this->assertTrue($adminUser->can('delete', $link));

        // 3. Regular member CANNOT delete
        $memberUser = User::factory()->create();
        TenantUser::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $memberUser->id,
            'role' => 'member',
            'accepted_at' => now(),
        ]);
        // Emulate member context (session + auth)
        $this->actingAs($memberUser);
        session(['current_tenant_id' => $this->tenant->id]);

        $this->assertFalse($memberUser->can('delete', $app));
        $this->assertFalse($memberUser->can('delete', $link));
    }
}
