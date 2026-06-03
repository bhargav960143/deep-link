<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\Domain;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = session('current_tenant_id');
        $tenant = $tenantId ? Tenant::find($tenantId) : null;

        if (! $tenant) {
            $tenant = Auth::user()->tenants()->first();
            if ($tenant) {
                session(['current_tenant_id' => $tenant->id]);
            }
        }

        if (! $tenant) {
            abort(403, 'No workspace found.');
        }

        $apps = App::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $domains = Domain::where('tenant_id', $tenant->id)->orderBy('domain')->get();
        $primaryDomain = $domains->first()?->domain ?? config('app.url');

        return view('guide.index', compact('tenant', 'apps', 'domains', 'primaryDomain'));
    }
}
