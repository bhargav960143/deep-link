<?php

namespace Tests\Feature;

use App\Jobs\LogLinkClick;
use App\Models\App;
use App\Models\Domain;
use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\SetupTenantTest;

class LinkRedirectTest extends TestCase
{
    use RefreshDatabase, SetupTenantTest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
        $this->domain->update(['status' => 'active']);
    }

    public function test_redirects_to_landing_page_and_dispatches_log_click_job(): void
    {
        Bus::fake();

        $app = $this->createApp(['web_fallback_url' => 'https://example.com/app-fallback']);
        $link = $this->createLink([
            'app_id' => $app->id,
            'short_code' => 'route1',
            'destination_path' => 'my-path',
            'web_fallback_url' => 'https://example.com/link-fallback',
        ]);

        $domain = $this->domain->domain;

        $response = $this->get("http://{$domain}/l/{$link->short_code}", [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('redirect.landing');
        $response->assertViewHas('link');
        $response->assertViewHas('platform', 'desktop');

        // Asserts job dispatched
        Bus::assertDispatched(LogLinkClick::class, function ($job) use ($link) {
            // Retrieve private/protected properties using reflection or standard array inspection if available
            // Let's use reflection to inspect private properties of the job
            $refJob = new \ReflectionClass($job);
            $linkIdProperty = $refJob->getProperty('linkId');
            $linkIdProperty->setAccessible(true);
            $outcomeProperty = $refJob->getProperty('outcome');
            $outcomeProperty->setAccessible(true);

            return $linkIdProperty->getValue($job) === $link->id &&
                   $outcomeProperty->getValue($job) === 'web_fallback';
        });
    }

    public function test_bot_detection_serves_landing_page_with_bot_outcome(): void
    {
        Bus::fake();

        $app = $this->createApp();
        $link = $this->createLink([
            'app_id' => $app->id,
            'short_code' => 'botcode',
        ]);

        $domain = $this->domain->domain;

        $response = $this->get("http://{$domain}/l/{$link->short_code}", [
            'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('redirect.landing');
        $response->assertViewHas('platform', 'bot');

        Bus::assertDispatched(LogLinkClick::class, function ($job) use ($link) {
            $refJob = new \ReflectionClass($job);
            $outcomeProperty = $refJob->getProperty('outcome');
            $outcomeProperty->setAccessible(true);
            return $outcomeProperty->getValue($job) === 'bot_filtered';
        });
    }

    public function test_inactive_link_returns_error_view(): void
    {
        Bus::fake();

        $app = $this->createApp();
        $link = $this->createLink([
            'app_id' => $app->id,
            'short_code' => 'inactivecode',
            'is_active' => false,
        ]);

        $domain = $this->domain->domain;

        $response = $this->get("http://{$domain}/l/{$link->short_code}");

        $response->assertStatus(200); // Controller renders error view with 200 by default (custom landing page)
        $response->assertViewIs('redirect.error');
        $response->assertViewHas('type', 'inactive');

        Bus::assertDispatched(LogLinkClick::class, function ($job) {
            $refJob = new \ReflectionClass($job);
            $outcomeProperty = $refJob->getProperty('outcome');
            $outcomeProperty->setAccessible(true);
            return $outcomeProperty->getValue($job) === 'link_inactive';
        });
    }

    public function test_expired_link_returns_error_view(): void
    {
        Bus::fake();

        $app = $this->createApp();
        $link = $this->createLink([
            'app_id' => $app->id,
            'short_code' => 'expiredcode',
            'expires_at' => now()->subDay(),
        ]);

        $domain = $this->domain->domain;

        $response = $this->get("http://{$domain}/l/{$link->short_code}");

        $response->assertStatus(410);
        $response->assertViewIs('redirect.error');
        $response->assertViewHas('type', 'expired');
    }

    public function test_max_clicks_link_returns_error_view(): void
    {
        Bus::fake();

        $app = $this->createApp();
        $link = $this->createLink([
            'app_id' => $app->id,
            'short_code' => 'maxclicks',
            'max_clicks' => 10,
            'click_count' => 10,
        ]);

        $domain = $this->domain->domain;

        $response = $this->get("http://{$domain}/l/{$link->short_code}");

        $response->assertStatus(200);
        $response->assertViewIs('redirect.error');
        $response->assertViewHas('type', 'max_clicks');
    }

    public function test_device_routing_for_ios(): void
    {
        Bus::fake();

        $app = $this->createApp([
            'ios_store_url' => 'https://apps.apple.com/app/id123',
        ]);
        $link = $this->createLink([
            'app_id' => $app->id,
            'short_code' => 'ioscode',
        ]);

        $domain = $this->domain->domain;

        $response = $this->get("http://{$domain}/l/{$link->short_code}", [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('redirect.landing');
        $response->assertViewHas('platform', 'ios');

        Bus::assertDispatched(LogLinkClick::class, function ($job) {
            $refJob = new \ReflectionClass($job);
            $outcomeProperty = $refJob->getProperty('outcome');
            $outcomeProperty->setAccessible(true);
            return $outcomeProperty->getValue($job) === 'store_redirect_ios';
        });
    }

    public function test_device_routing_for_android(): void
    {
        Bus::fake();

        $app = $this->createApp([
            'android_store_url' => 'https://play.google.com/store/apps/details?id=com.example',
        ]);
        $link = $this->createLink([
            'app_id' => $app->id,
            'short_code' => 'androidcode',
        ]);

        $domain = $this->domain->domain;

        $response = $this->get("http://{$domain}/l/{$link->short_code}", [
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('redirect.landing');
        $response->assertViewHas('platform', 'android');

        Bus::assertDispatched(LogLinkClick::class, function ($job) {
            $refJob = new \ReflectionClass($job);
            $outcomeProperty = $refJob->getProperty('outcome');
            $outcomeProperty->setAccessible(true);
            return $outcomeProperty->getValue($job) === 'store_redirect_android';
        });
    }

    public function test_password_gate_intercepts_and_unlocks(): void
    {
        $app = $this->createApp();
        $link = $this->createLink([
            'app_id' => $app->id,
            'short_code' => 'lockedcode',
            'password' => Hash::make('secret_pass'),
        ]);

        $domain = $this->domain->domain;

        // 1. Visit locked link, should see password view
        $response = $this->get("http://{$domain}/l/{$link->short_code}");
        $response->assertStatus(200);
        $response->assertViewIs('redirect.password');

        // 2. Submit wrong password, should fail with validation error
        $wrongResponse = $this->post("http://{$domain}/l/{$link->short_code}/unlock", [
            'password' => 'wrong_pass',
        ]);
        $wrongResponse->assertSessionHasErrors('password');

        // 3. Submit correct password, should store in session and redirect back to short code route
        $correctResponse = $this->post("http://{$domain}/l/{$link->short_code}/unlock", [
            'password' => 'secret_pass',
        ]);
        $correctResponse->assertRedirect(route('tenant.redirect', ['shortCode' => $link->short_code]));
        $this->assertTrue(session("link_unlocked_{$link->id}"));

        // 4. Visit again, should bypass password gate and show landing page
        $finalResponse = $this->get("http://{$domain}/l/{$link->short_code}");
        $finalResponse->assertStatus(200);
        $finalResponse->assertViewIs('redirect.landing');
    }
}
