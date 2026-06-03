<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = session('current_tenant_id');
        $tenant = $tenantId ? Tenant::find($tenantId) : Auth::user()->tenants()->first();

        return view('dashboard.index', compact('tenant'));
    }
}
