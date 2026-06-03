<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\Link;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = session('current_tenant_id');
        $tenant = $tenantId ? Tenant::find($tenantId) : null;

        // Defensive fallback: load from user's tenants if session is empty or stale
        if (! $tenant) {
            $tenant = Auth::user()->tenants()->first();

            if ($tenant) {
                session(['current_tenant_id' => $tenant->id]);
            }
        }

        // Still no tenant — shouldn't happen if EnsureTenantAccess middleware ran,
        // but handle gracefully
        if (! $tenant) {
            abort(403, 'No workspace found. Please contact support.');
        }

        // Quick stats for the dashboard
        $stats = [
            'total_apps' => App::where('tenant_id', $tenant->id)->count(),
            'total_links' => Link::where('tenant_id', $tenant->id)->count(),
            'active_links' => Link::where('tenant_id', $tenant->id)->where('is_active', true)->count(),
        ];

        return view('dashboard.index', compact('tenant', 'stats'));
    }
}
