<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait BelongsToTenant
 *
 * Automatically scopes all queries to the current tenant.
 * Works in both central context (session-based) and tenant context (domain-based via stancl/tenancy).
 *
 * Usage: `use BelongsToTenant;` in any model that has a `tenant_id` column.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            $tenantId = static::resolveTenantId();

            if ($tenantId) {
                $query->where($query->getModel()->getTable() . '.tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (! $model->tenant_id) {
                $tenantId = static::resolveTenantId();
                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }

    /**
     * Resolve the current tenant ID from either session (central dashboard)
     * or the tenancy context (tenant domain routes like AASA/redirect).
     */
    protected static function resolveTenantId(): ?string
    {
        // 1. Session context — central dashboard routes
        $sessionTenantId = session('current_tenant_id');
        if ($sessionTenantId) {
            return $sessionTenantId;
        }

        // 2. Tenancy context — tenant domain routes (AASA, redirects)
        if (function_exists('tenancy') && tenancy()->tenant) {
            return tenancy()->tenant->id;
        }

        return null;
    }
}
