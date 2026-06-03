<?php

namespace Tests\Unit;

use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkModelTest extends TestCase
{
    use RefreshDatabase;
    public function test_is_expired_when_past(): void
    {
        $link = new Link();
        $link->expires_at = now()->subDay();

        $this->assertTrue($link->isExpired());
    }

    public function test_is_not_expired_when_future(): void
    {
        $link = new Link();
        $link->expires_at = now()->addDay();

        $this->assertFalse($link->isExpired());
    }

    public function test_is_not_expired_when_null(): void
    {
        $link = new Link();
        $link->expires_at = null;

        $this->assertFalse($link->isExpired());
    }

    public function test_max_clicks_reached(): void
    {
        $link = new Link();
        $link->max_clicks = 10;
        $link->click_count = 10;

        $this->assertTrue($link->isMaxClicksReached());
    }

    public function test_max_clicks_not_reached(): void
    {
        $link = new Link();
        $link->max_clicks = 10;
        $link->click_count = 5;

        $this->assertFalse($link->isMaxClicksReached());
    }

    public function test_max_clicks_null_means_unlimited(): void
    {
        $link = new Link();
        $link->max_clicks = null;
        $link->click_count = 999999;

        $this->assertFalse($link->isMaxClicksReached());
    }

    public function test_is_available_when_active_and_valid(): void
    {
        $link = new Link();
        $link->is_active = true;
        $link->expires_at = now()->addDay();
        $link->max_clicks = null;

        $this->assertTrue($link->isAvailable());
    }

    public function test_is_not_available_when_inactive(): void
    {
        $link = new Link();
        $link->is_active = false;

        $this->assertFalse($link->isAvailable());
    }

    public function test_is_not_available_when_expired(): void
    {
        $link = new Link();
        $link->is_active = true;
        $link->expires_at = now()->subDay();

        $this->assertFalse($link->isAvailable());
    }
}
