<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Plan;
use App\Services\SsrfValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SetupTenantTest;

class LinkControllerTest extends TestCase
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

        // Ensure domain is active so validation doesn't fail
        $this->domain->update(['status' => 'active']);
    }

    public function test_index_shows_only_owned_links(): void
    {
        $app = $this->createApp();
        $link1 = $this->createLink([
            'app_id' => $app->id,
            'title' => 'My Owned Link',
            'short_code' => 'owned1',
        ]);

        $other = $this->createOtherTenant();
        $otherApp = App::factory()->create(['tenant_id' => $other['otherTenant']->id]);
        $otherLink = Link::factory()->create([
            'tenant_id' => $other['otherTenant']->id,
            'app_id' => $otherApp->id,
            'domain_id' => $other['otherDomain']->id,
            'title' => 'Foreign Link',
            'short_code' => 'foreign1',
        ]);

        $response = $this->get(route('links.index'));

        $response->assertStatus(200);
        $response->assertSee('My Owned Link');
        $response->assertDontSee('Foreign Link');
    }

    public function test_create_view(): void
    {
        $response = $this->get(route('links.create'));
        $response->assertStatus(200);
    }

    public function test_store_creates_link_successfully(): void
    {
        $app = $this->createApp();

        $payload = [
            'app_id' => $app->id,
            'domain_id' => $this->domain->id,
            'destination_path' => 'https://example.com/some/path',
            'title' => 'My Link Title',
            'link_type' => 'universal',
            'short_code' => 'customcode',
            'web_fallback_url' => 'https://example.com/fallback',
        ];

        $response = $this->post(route('links.store'), $payload);

        $response->assertRedirect(route('links.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('links', [
            'tenant_id' => $this->tenant->id,
            'app_id' => $app->id,
            'short_code' => 'customcode',
            'destination_path' => 'https://example.com/some/path',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_store_auto_generates_short_code_if_omitted(): void
    {
        $app = $this->createApp();

        $payload = [
            'app_id' => $app->id,
            'domain_id' => $this->domain->id,
            'destination_path' => 'https://example.com/another/path',
            'link_type' => 'universal',
            'short_code' => '',
        ];

        $response = $this->post(route('links.store'), $payload);

        $response->assertRedirect(route('links.index'));
        $response->assertSessionHas('success');

        $link = Link::where('destination_path', 'https://example.com/another/path')->first();
        $this->assertNotNull($link);
        $this->assertNotEmpty($link->short_code);
        $this->assertEquals(6, strlen($link->short_code));
    }

    public function test_store_fails_when_plan_limits_exceeded(): void
    {
        // Set limits to 1 link
        $plan = Plan::where('slug', 'free')->first();
        $plan->update(['links_limit' => 1]);

        $app = $this->createApp();

        // Create the 1 allowed link
        $this->createLink(['app_id' => $app->id]);

        $payload = [
            'app_id' => $app->id,
            'domain_id' => $this->domain->id,
            'destination_path' => 'https://example.com/overflow',
            'link_type' => 'universal',
            'short_code' => 'overflow',
        ];

        $response = $this->post(route('links.store'), $payload);
        $response->assertStatus(403);
    }

    public function test_edit_view_for_owned_link(): void
    {
        $app = $this->createApp();
        $link = $this->createLink(['app_id' => $app->id]);

        $response = $this->get(route('links.edit', $link));
        $response->assertStatus(200);
        $response->assertSee($link->short_code);
    }

    public function test_edit_view_fails_for_foreign_link(): void
    {
        $other = $this->createOtherTenant();
        $otherApp = App::factory()->create(['tenant_id' => $other['otherTenant']->id]);
        $otherLink = Link::factory()->create([
            'tenant_id' => $other['otherTenant']->id,
            'app_id' => $otherApp->id,
            'domain_id' => $other['otherDomain']->id,
        ]);

        $response = $this->get(route('links.edit', $otherLink));
        $response->assertStatus(404);
    }

    public function test_update_link_successfully(): void
    {
        $app = $this->createApp();
        $link = $this->createLink(['app_id' => $app->id, 'destination_path' => 'https://old.com']);

        $payload = [
            'app_id' => $app->id,
            'domain_id' => $this->domain->id,
            'destination_path' => 'https://new.com',
            'link_type' => 'universal',
        ];

        $response = $this->put(route('links.update', $link), $payload);

        $response->assertRedirect(route('links.index'));
        $response->assertSessionHas('success');

        $this->assertEquals('https://new.com', $link->fresh()->destination_path);
    }

    public function test_update_link_fails_for_foreign_link(): void
    {
        $other = $this->createOtherTenant();
        $otherApp = App::factory()->create(['tenant_id' => $other['otherTenant']->id]);
        $otherLink = Link::factory()->create([
            'tenant_id' => $other['otherTenant']->id,
            'app_id' => $otherApp->id,
            'domain_id' => $other['otherDomain']->id,
        ]);

        $payload = [
            'app_id' => $otherApp->id,
            'domain_id' => $other['otherDomain']->id,
            'destination_path' => 'https://hacked.com',
            'link_type' => 'universal',
        ];

        $response = $this->put(route('links.update', $otherLink), $payload);
        $response->assertStatus(404);
    }

    public function test_destroy_link_successfully(): void
    {
        $app = $this->createApp();
        $link = $this->createLink(['app_id' => $app->id]);

        $response = $this->delete(route('links.destroy', $link));

        $response->assertRedirect(route('links.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted($link);
    }

    public function test_destroy_link_fails_for_foreign_link(): void
    {
        $other = $this->createOtherTenant();
        $otherApp = App::factory()->create(['tenant_id' => $other['otherTenant']->id]);
        $otherLink = Link::factory()->create([
            'tenant_id' => $other['otherTenant']->id,
            'app_id' => $otherApp->id,
            'domain_id' => $other['otherDomain']->id,
        ]);

        $response = $this->delete(route('links.destroy', $otherLink));
        $response->assertStatus(404);
    }
}
