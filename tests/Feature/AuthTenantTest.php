<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;
use Tests\Traits\SetupTenantTest;

class AuthTenantTest extends TestCase
{
    use RefreshDatabase, SetupTenantTest;

    public function test_registration_creates_tenant_and_sets_session(): void
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
            'company_name' => 'Acme Inc',
            'slug' => 'acme',
        ];

        // Seed the plans table first since registration assigns a plan (Free)
        \App\Models\Plan::factory()->create(['slug' => 'free']);

        $response = $this->post(route('register'), $payload);

        // Asserts redirect to verification notice
        $response->assertRedirect(route('verification.notice'));

        // Asserts user logged in
        $this->assertTrue(Auth::check());
        $user = Auth::user();
        $this->assertEquals('john@example.com', $user->email);

        // Asserts tenant created
        $tenant = Tenant::where('plan_slug', 'free')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('Acme Inc', $tenant->name);

        // Asserts session has tenant ID
        $this->assertEquals($tenant->id, session('current_tenant_id'));

        // Asserts pivot created
        $this->assertDatabaseHas('tenant_users', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
    }

    public function test_login_sets_tenant_session(): void
    {
        $password = 'secret123';
        $user = User::factory()->create([
            'password' => Hash::make($password),
        ]);

        $tenant = Tenant::factory()->create();
        TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'accepted_at' => now(),
        ]);

        $payload = [
            'email' => $user->email,
            'password' => $password,
        ];

        $response = $this->post(route('login'), $payload);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
        $this->assertEquals($tenant->id, session('current_tenant_id'));
    }

    public function test_login_with_2fa_enabled_redirects_to_challenge(): void
    {
        $password = 'secret123';
        $user = User::factory()->create([
            'password' => Hash::make($password),
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'), // valid 16-char secret
            'two_factor_confirmed_at' => now(),
        ]);

        $tenant = Tenant::factory()->create();
        TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'accepted_at' => now(),
        ]);

        $payload = [
            'email' => $user->email,
            'password' => $password,
        ];

        $response = $this->post(route('login'), $payload);

        // Should redirect to two factor challenge
        $response->assertRedirect(route('two-factor.create'));

        // Should NOT be logged in yet
        $this->assertFalse(Auth::check());

        // Session should have login.id set
        $this->assertEquals($user->id, session('login.id'));
        $this->assertNull(session('current_tenant_id'));

        // Mock Google2FA to pass the challenge
        $this->mock(Google2FA::class, function ($mock) {
            $mock->shouldReceive('verifyKey')
                ->once()
                ->with('JBSWY3DPEHPK3PXP', '123456')
                ->andReturn(true);
        });

        // Submit the challenge code
        $challengeResponse = $this->post(route('two-factor.store'), [
            'code' => '123456',
        ]);

        // Should redirect to dashboard, authenticate, and set session
        $challengeResponse->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
        $this->assertEquals($tenant->id, session('current_tenant_id'));
    }
}
