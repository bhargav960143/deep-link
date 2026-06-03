<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SetupTenantTest;

class TenantAccessMiddlewareTest extends TestCase
{
    use RefreshDatabase, SetupTenantTest;

    public function test_access_granted_with_valid_session(): void
    {
        $this->setUpTenant();

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
    }

    public function test_auto_recovers_when_no_tenant_in_session(): void
    {
        // Setup tenant but manually clear session
        $this->setUpTenant();
        session()->forget('current_tenant_id');

        $this->assertNull(session('current_tenant_id'));

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $this->assertEquals($this->tenant->id, session('current_tenant_id'));
    }

    public function test_aborts_when_no_workspace_at_all(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // No tenant / no pivot
        $response = $this->get(route('dashboard'));

        $response->assertStatus(403);
        $response->assertSee('You are not a member of any workspace.');
    }

    public function test_redirects_to_first_workspace_when_session_tampered(): void
    {
        $this->setUpTenant(); // User belongs to $this->tenant

        // Create a different tenant that this user does NOT belong to
        $otherTenant = Tenant::factory()->create();

        // Tamper with the session to point to the other tenant
        session(['current_tenant_id' => $otherTenant->id]);

        $response = $this->get(route('dashboard'));

        // Should forget otherTenant, fallback to $this->tenant, redirect to dashboard
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('warning', 'You were redirected to your default workspace.');
        $this->assertEquals($this->tenant->id, session('current_tenant_id'));
    }

    public function test_aborts_when_session_tampered_and_user_has_no_workspaces(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $otherTenant = Tenant::factory()->create();
        session(['current_tenant_id' => $otherTenant->id]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(403);
        $response->assertSee('You do not have access to this workspace.');
    }
}
