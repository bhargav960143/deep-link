<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_relation_returns_correct_plan(): void
    {
        $plan = Plan::factory()->create(['slug' => 'pro']);
        $tenant = Tenant::factory()->create(['plan_slug' => 'pro']);

        $this->assertEquals('pro', $tenant->planRelation->slug);
        $this->assertEquals('pro', $tenant->plan->slug);
    }

    public function test_plan_attribute_falls_back_to_free_plan_from_db(): void
    {
        $freePlan = Plan::factory()->create(['slug' => 'free', 'links_limit' => 50]);
        $tenant = Tenant::factory()->create(['plan_slug' => 'non-existent']);

        $this->assertEquals('free', $tenant->plan->slug);
        $this->assertEquals(50, $tenant->plan->links_limit);
    }

    public function test_plan_attribute_falls_back_to_in_memory_default_if_no_free_plan_in_db(): void
    {
        $tenant = Tenant::factory()->create(['plan_slug' => 'non-existent']);

        // No free plan in db
        $this->assertEquals('free', $tenant->plan->slug);
        $this->assertEquals(100, $tenant->plan->links_limit);
    }

    public function test_is_on_plan(): void
    {
        $tenant = Tenant::factory()->create(['plan_slug' => 'business']);

        $this->assertTrue($tenant->isOnPlan('business'));
        $this->assertFalse($tenant->isOnPlan('free'));
    }
}
