<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\Domain;
use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SetupTenantTest;

class TenantScopingTest extends TestCase
{
    use RefreshDatabase, SetupTenantTest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
    }

    public function test_queries_are_scoped_to_current_tenant(): void
    {
        // Create apps for current tenant
        $app1 = $this->createApp(['name' => 'Current Tenant App']);

        // Create other tenant context and an app for them
        $other = $this->createOtherTenant();
        // Temporarily change session to other tenant to create their app via factory
        session(['current_tenant_id' => $other['otherTenant']->id]);
        $app2 = App::factory()->create([
            'tenant_id' => $other['otherTenant']->id,
            'name' => 'Other Tenant App'
        ]);

        // Restore session to current tenant
        session(['current_tenant_id' => $this->tenant->id]);

        // Fetch all apps
        $apps = App::all();

        // Check that only current tenant app is visible
        $this->assertCount(1, $apps);
        $this->assertEquals($app1->id, $apps->first()->id);
        $this->assertEquals('Current Tenant App', $apps->first()->name);

        // Verify with find
        $this->assertNotNull(App::find($app1->id));
        $this->assertNull(App::find($app2->id));
    }

    public function test_tenant_id_is_automatically_set_on_creation(): void
    {
        // When creating an app, tenant_id should be auto-set from session
        $app = App::create([
            'name' => 'Auto Tenant App',
            'platform' => 'ios',
            'bundle_id' => 'com.example.auto',
            'is_active' => true,
        ]);

        $this->assertEquals($this->tenant->id, $app->tenant_id);

        // When creating a link, tenant_id should be auto-set from session
        $link = Link::create([
            'domain_id' => $this->domain->id,
            'app_id' => $app->id,
            'short_code' => 'abcde',
            'destination_path' => 'https://example.com',
            'is_active' => true,
        ]);

        $this->assertEquals($this->tenant->id, $link->tenant_id);
    }

    public function test_scopes_apply_to_domain_model(): void
    {
        $domain1 = $this->domain;

        $other = $this->createOtherTenant();
        $domain2 = $other['otherDomain'];

        // Only current tenant's domain should be visible
        $domains = Domain::all();
        $this->assertCount(1, $domains);
        $this->assertEquals($domain1->id, $domains->first()->id);
    }
}
