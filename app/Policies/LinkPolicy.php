<?php

namespace App\Policies;

use App\Models\Link;
use App\Models\User;

class LinkPolicy
{
    /**
     * Any authenticated user with tenant access can view links.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Check if the user can create links (plan limits enforced).
     */
    public function create(User $user): bool
    {
        $tenant = \App\Models\Tenant::find(session('current_tenant_id'));

        if (! $tenant) {
            return false;
        }

        $plan = $tenant->plan;
        $currentCount = Link::where('tenant_id', $tenant->id)->count();

        return $plan->isUnlimited('links_limit') || $currentCount < $plan->links_limit;
    }

    /**
     * User can view a link if it belongs to their current tenant.
     */
    public function view(User $user, Link $link): bool
    {
        return $link->tenant_id === session('current_tenant_id');
    }

    /**
     * User can update a link if it belongs to their current tenant.
     */
    public function update(User $user, Link $link): bool
    {
        return $link->tenant_id === session('current_tenant_id');
    }

    /**
     * User can delete a link if it belongs to their current tenant
     * and they have an owner or admin role.
     */
    public function delete(User $user, Link $link): bool
    {
        if ($link->tenant_id !== session('current_tenant_id')) {
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
