<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    use HasFactory, HasDomains;

    public static function getCustomColumns(): array
    {
        return ['id', 'name', 'plan_slug', 'plan_expires_at', 'trial_ends_at'];
    }

    protected function casts(): array
    {
        return [
            'plan_expires_at' => 'datetime',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->withPivot(['role', 'invited_at', 'accepted_at'])
            ->withTimestamps();
    }

    /**
     * Relationship to the Plan model — enables eager loading.
     * Usage: Tenant::with('planRelation')->get()
     */
    public function planRelation(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_slug', 'slug');
    }

    /**
     * Accessor that always returns a valid Plan instance.
     * Falls back to the free plan if no plan is assigned or found.
     * Usage: $tenant->plan->links_limit
     */
    public function getPlanAttribute(): Plan
    {
        $plan = $this->planRelation;

        if ($plan) {
            return $plan;
        }

        // Fallback: return the free plan from DB, or a default in-memory model
        return Plan::where('slug', 'free')->first() ?? new Plan([
            'slug' => 'free',
            'name' => 'Free',
            'links_limit' => 100,
            'clicks_limit' => 10000,
            'apps_limit' => 1,
            'team_members_limit' => 1,
            'custom_domains_limit' => 0,
            'api_access' => false,
            'webhooks' => false,
            'analytics_retention_days' => 30,
        ]);
    }

    public function isOnPlan(string $slug): bool
    {
        return $this->plan_slug === $slug;
    }
}

