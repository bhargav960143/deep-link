<?php

namespace App\Policies;

use App\Models\App;
use App\Models\User;

class AppPolicy
{
    /**
     * Any authenticated user with tenant access can view apps.
     * (Tenant ownership is enforced by the global scope + middleware.)
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Check if the user can create apps (plan limits enforced).
     */
    public function create(User $user): bool
    {
        $tenant = \App\Models\Tenant::find(session('current_tenant_id'));

        if (! $tenant) {
            return false;
        }

        $plan = $tenant->plan;
        $currentCount = App::where('tenant_id', $tenant->id)->count();

        return $plan->isUnlimited('apps_limit') || $currentCount < $plan->apps_limit;
    }

    /**
     * User can view an app if it belongs to their current tenant.
     * Global scope already filters, but this is an explicit policy check.
     */
    public function view(User $user, App $app): bool
    {
        return $app->tenant_id === session('current_tenant_id');
    }

    /**
     * User can update an app if it belongs to their current tenant.
     */
    public function update(User $user, App $app): bool
    {
        return $app->tenant_id === session('current_tenant_id');
    }

    /**
     * User can delete an app if it belongs to their current tenant
     * and they have an owner or admin role.
     */
    public function delete(User $user, App $app): bool
    {
        if ($app->tenant_id !== session('current_tenant_id')) {
            return false;
        }

        $role = $user->tenants()
            ->where('tenants.id', session('current_tenant_id'))
            ->first()
            ?->pivot
            ?->role;

        return in_array($role, ['owner', 'admin']);
    }
}
