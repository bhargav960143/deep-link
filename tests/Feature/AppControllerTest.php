<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\Plan;
use App\Models\User;
use App\Services\SsrfValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SetupTenantTest;

class AppControllerTest extends TestCase
{
    use RefreshDatabase, SetupTenantTest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();

        // Mock SSRF validator to avoid DNS lookups in tests
        $this->mock(SsrfValidator::class, function ($mock) {
            $mock->shouldReceive('isSafe')->andReturn(true);
        });
    }

    public function test_index_shows_only_owned_apps(): void
    {
        $app1 = $this->createApp(['name' => 'My First App']);

        $other = $this->createOtherTenant();
        $otherApp = App::factory()->create([
            'tenant_id' => $other['otherTenant']->id,
            'name' => 'Foreign App'
        ]);

        $response = $this->get(route('apps.index'));

        $response->assertStatus(200);
        $response->assertSee('My First App');
        $response->assertDontSee('Foreign App');
    }

    public function test_create_view(): void
    {
        $response = $this->get(route('apps.create'));
        $response->assertStatus(200);
    }

    public function test_store_creates_app_successfully(): void
    {
        $payload = [
            'name' => 'New Android App',
            'platform' => 'android',
            'android_package_name' => 'com.example.android',
            'android_sha256_fingerprints' => [
                '14:26:17:F1:C9:82:1E:83:B3:26:14:26:17:F1:C9:82:1E:83:B3:26:14:26:17:F1:C9:82:1E:83:B3:26:14:26'
            ],
            'android_store_url' => 'https://play.google.com/store/apps/details?id=com.example',
            'web_fallback_url' => 'https://example.com',
        ];

        $response = $this->post(route('apps.store'), $payload);

        $response->assertRedirect(route('apps.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('apps', [
            'tenant_id' => $this->tenant->id,
            'name' => 'New Android App',
            'platform' => 'android',
        ]);
    }

    public function test_store_fails_when_plan_limits_exceeded(): void
    {
        // Set limits to 1 app
        $plan = Plan::where('slug', 'free')->first();
        $plan->update(['apps_limit' => 1]);

        // Create the 1 allowed app
        $this->createApp();

        $payload = [
            'name' => 'New iOS App',
            'platform' => 'ios',
            'ios_bundle_id' => 'com.example.ios',
            'ios_team_id' => 'ABC123XYZ4',
            'ios_store_url' => 'https://apps.apple.com/app/id123',
        ];

        // Should return 403 forbidden
        $response = $this->post(route('apps.store'), $payload);
        $response->assertStatus(403);
    }

    public function test_edit_view_for_owned_app(): void
    {
        $app = $this->createApp();

        $response = $this->get(route('apps.edit', $app));
        $response->assertStatus(200);
        $response->assertSee($app->name);
    }

    public function test_edit_view_fails_for_foreign_app(): void
    {
        $other = $this->createOtherTenant();
        $otherApp = App::factory()->create(['tenant_id' => $other['otherTenant']->id]);

        $response = $this->get(route('apps.edit', $otherApp));
        $response->assertStatus(404);
    }

    public function test_update_app_successfully(): void
    {
        $app = $this->createApp(['name' => 'Old Name']);

        $payload = [
            'name' => 'Updated Name',
            'platform' => 'ios',
            'ios_bundle_id' => 'com.example.ios',
            'ios_team_id' => 'ABC123XYZ4',
            'ios_store_url' => 'https://apps.apple.com/app/id123',
        ];

        $response = $this->put(route('apps.update', $app), $payload);

        $response->assertRedirect(route('apps.index'));
        $response->assertSessionHas('success');

        $this->assertEquals('Updated Name', $app->fresh()->name);
    }

    public function test_update_app_fails_for_foreign_app(): void
    {
        $other = $this->createOtherTenant();
        $otherApp = App::factory()->create(['tenant_id' => $other['otherTenant']->id]);

        $payload = [
            'name' => 'Hijacked App',
            'platform' => 'ios',
            'ios_bundle_id' => 'com.example.ios',
            'ios_team_id' => 'ABC123XYZ4',
            'ios_store_url' => 'https://apps.apple.com/app/id123',
        ];

        $response = $this->put(route('apps.update', $otherApp), $payload);
        $response->assertStatus(404);
    }

    public function test_destroy_app_successfully(): void
    {
        $app = $this->createApp();

        $response = $this->delete(route('apps.destroy', $app));

        $response->assertRedirect(route('apps.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted($app);
    }

    public function test_destroy_app_fails_for_foreign_app(): void
    {
        $other = $this->createOtherTenant();
        $otherApp = App::factory()->create(['tenant_id' => $other['otherTenant']->id]);

        $response = $this->delete(route('apps.destroy', $otherApp));
        $response->assertStatus(404);
    }
}
