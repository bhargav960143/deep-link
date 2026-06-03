<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    use HasDomains;

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

    public function plan(): Plan
    {
        return Plan::where('slug', $this->plan_slug)->firstOrNew(['slug' => 'free']);
    }

    public function isOnPlan(string $slug): bool
    {
        return $this->plan_slug === $slug;
    }
}
