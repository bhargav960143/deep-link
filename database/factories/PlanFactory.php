<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'name' => 'Free',
            'slug' => 'free',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'links_limit' => 100,
            'clicks_limit' => 10000,
            'apps_limit' => 1,
            'team_members_limit' => 1,
            'custom_domains_limit' => 0,
            'api_access' => false,
            'webhooks' => false,
            'analytics_retention_days' => 30,
            'is_active' => true,
        ];
    }

    public function pro(): static
    {
        return $this->state([
            'name' => 'Pro',
            'slug' => 'pro',
            'price_monthly' => 249900,
            'links_limit' => 10000,
            'clicks_limit' => 500000,
            'apps_limit' => 5,
            'team_members_limit' => 3,
            'custom_domains_limit' => 1,
            'api_access' => true,
        ]);
    }

    public function business(): static
    {
        return $this->state([
            'name' => 'Business',
            'slug' => 'business',
            'price_monthly' => 799900,
            'links_limit' => -1,
            'clicks_limit' => -1,
            'apps_limit' => 20,
            'team_members_limit' => 10,
            'custom_domains_limit' => 5,
            'api_access' => true,
            'webhooks' => true,
        ]);
    }
}
