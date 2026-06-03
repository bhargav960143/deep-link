<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPlans();
    }

    private function seedPlans(): void
    {
        $plans = [
            [
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
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price_monthly' => 249900,
                'price_yearly' => 2399900,
                'links_limit' => 10000,
                'clicks_limit' => 500000,
                'apps_limit' => 5,
                'team_members_limit' => 3,
                'custom_domains_limit' => 1,
                'api_access' => true,
                'webhooks' => false,
                'analytics_retention_days' => 365,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'price_monthly' => 799900,
                'price_yearly' => 7679900,
                'links_limit' => -1,
                'clicks_limit' => -1,
                'apps_limit' => 20,
                'team_members_limit' => 10,
                'custom_domains_limit' => 5,
                'api_access' => true,
                'webhooks' => true,
                'analytics_retention_days' => 730,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
