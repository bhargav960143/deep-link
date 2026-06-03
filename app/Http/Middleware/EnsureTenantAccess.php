<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the authenticated user actually belongs to the tenant stored in their session.
 *
 * Prevents session manipulation attacks where a user could change
 * `current_tenant_id` to access another tenant's data.
 */
class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = session('current_tenant_id');
        $user = $request->user();

        // If no tenant in session, try to set it from user's tenants
        if (! $tenantId) {
            $firstTenant = $user->tenants()->first();

            if (! $firstTenant) {
                abort(403, 'You are not a member of any workspace.');
            }

            session(['current_tenant_id' => $firstTenant->id]);
            view()->share('tenant', $firstTenant);
            return $next($request);
        }

        // Verify user actually belongs to this tenant
        $belongsToTenant = $user->tenants()
            ->where('tenants.id', $tenantId)
            ->first();

        if (! $belongsToTenant) {
            // Clear the invalid session value and try fallback
            session()->forget('current_tenant_id');

            $firstTenant = $user->tenants()->first();

            if ($firstTenant) {
                session(['current_tenant_id' => $firstTenant->id]);
                view()->share('tenant', $firstTenant);
                return redirect()->route('dashboard')
                    ->with('warning', 'You were redirected to your default workspace.');
            }

            abort(403, 'You do not have access to this workspace.');
        }

        view()->share('tenant', $belongsToTenant);
        return $next($request);
    }
}
