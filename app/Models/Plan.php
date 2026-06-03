<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'razorpay_plan_id_monthly', 'razorpay_plan_id_yearly',
        'price_monthly', 'price_yearly', 'links_limit', 'clicks_limit',
        'apps_limit', 'team_members_limit', 'custom_domains_limit',
        'api_access', 'webhooks', 'analytics_retention_days', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'api_access' => 'boolean',
            'webhooks' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function isUnlimited(string $field): bool
    {
        return $this->$field === -1;
    }
}
