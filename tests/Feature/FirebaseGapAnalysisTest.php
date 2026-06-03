<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FirebaseGapAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Domain $domain;
    private App $tenantApp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Acme Corp',
            'plan' => 'pro',
            'is_active' => true,
        ]);

        $this->domain = Domain::create([
            'tenant_id' => $this->tenant->id,
            'domain' => 'acme.localhost',
            'status' => 'active',
        ]);

        $this->tenantApp = App::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Acme App',
            'platform' => 'both',
            'web_fallback_url' => 'https://acme.com',
            'is_active' => true,
        ]);
    }

    public function test_granular_platform_fallbacks()
    {
        $link = Link::create([
            'tenant_id' => $this->tenant->id,
            'app_id' => $this->tenantApp->id,
            'domain_id' => $this->domain->id,
            'short_code' => 'fallbacks',
            'destination_path' => '/home',
            'web_fallback_url' => 'https://example.com/web',
            'ios_fallback_url' => 'https://example.com/ios',
            'android_fallback_url' => 'https://example.com/android',
            'link_type' => 'both',
        ]);

        // Test iOS Fallback
        $responseIos = $this->withServerVariables(['HTTP_HOST' => $this->domain->domain])
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15'])
            ->get("http://{$this->domain->domain}/l/fallbacks");
        $responseIos->assertSee('https://example.com/ios');
        $responseIos->assertDontSee('https://example.com/web');
        $responseIos->assertDontSee('https://example.com/android');

        // Test Android Fallback
        $responseAndroid = $this->withServerVariables(['HTTP_HOST' => $this->domain->domain])
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36'])
            ->get("http://{$this->domain->domain}/l/fallbacks");
        $responseAndroid->assertSee('https://example.com/android');
        $responseAndroid->assertDontSee('https://example.com/web');
        $responseAndroid->assertDontSee('https://example.com/ios');

        // Test Desktop Fallback
        $responseDesktop = $this->withServerVariables(['HTTP_HOST' => $this->domain->domain])
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'])
            ->get("http://{$this->domain->domain}/l/fallbacks");
        $responseDesktop->assertSee('https://example.com/web');
        $responseDesktop->assertDontSee('https://example.com/ios');
        $responseDesktop->assertDontSee('https://example.com/android');
    }

    public function test_interstitial_page_disables_auto_redirect()
    {
        $link = Link::create([
            'tenant_id' => $this->tenant->id,
            'app_id' => $this->tenantApp->id,
            'domain_id' => $this->domain->id,
            'short_code' => 'interstitial',
            'destination_path' => '/home',
            'show_interstitial' => true,
            'link_type' => 'both',
        ]);

        $response = $this->withServerVariables(['HTTP_HOST' => $this->domain->domain])
            ->get("http://{$this->domain->domain}/l/interstitial");

        $response->assertOk();
        $response->assertSee('showInterstitial: true');
    }

    public function test_deferred_deep_link_retrieval()
    {
        $link = Link::create([
            'tenant_id' => $this->tenant->id,
            'app_id' => $this->tenantApp->id,
            'domain_id' => $this->domain->id,
            'short_code' => 'deferred',
            'destination_path' => '/promo',
            'link_type' => 'both',
        ]);

        // Safari browser UA for the initial click
        $clickUa = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15';
        $ip = '123.123.123.123';

        // Simulate click
        $this->withServerVariables(['HTTP_HOST' => $this->domain->domain, 'REMOTE_ADDR' => $ip])
            ->withHeaders(['User-Agent' => $clickUa])
            ->get("http://{$this->domain->domain}/l/deferred");

        $this->assertDatabaseHas('link_clicks', [
            'link_id' => $link->id,
            'platform' => 'ios'
        ]);

        // Mobile SDK UA for the deferred lookup (completely different string, but still iOS)
        $sdkUa = 'MyApp/1.0.0 (iPhone; iOS 16.0; Scale/3.00)';

        // Query the API
        $response = $this->withServerVariables(['HTTP_HOST' => $this->domain->domain, 'REMOTE_ADDR' => $ip])
            ->withHeaders(['User-Agent' => $sdkUa])
            ->get("http://{$this->domain->domain}/api/v1/deferred-link");

        $response->assertOk();
        $response->assertJsonStructure([
            'found',
            'click_token',
            'link' => ['destination_path', 'short_code']
        ]);
        
        $this->assertTrue($response->json('found'));
        $this->assertEquals('/promo', $response->json('link.destination_path'));
    }

    public function test_post_install_event_ingestion()
    {
        $link = Link::create([
            'tenant_id' => $this->tenant->id,
            'app_id' => $this->tenantApp->id,
            'domain_id' => $this->domain->id,
            'short_code' => 'events',
            'destination_path' => '/home',
        ]);

        $click = \App\Models\LinkClick::create([
            'link_id' => $link->id,
            'platform' => 'ios',
            'ip_hash' => hash_hmac('sha256', '1.1.1.1' . 'ios', config('app.key')),
        ]);

        $token = \Illuminate\Support\Facades\Crypt::encryptString((string)$click->id);

        $response = $this->withServerVariables(['HTTP_HOST' => $this->domain->domain])
            ->postJson("http://{$this->domain->domain}/api/v1/events", [
                'click_token' => $token,
                'event_name' => 'app_installed',
                'properties' => ['version' => '1.0.0']
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('link_events', [
            'link_id' => $link->id,
            'event_name' => 'app_installed',
        ]);
    }
}
