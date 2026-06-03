<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppRequest;
use App\Models\App;
use App\Models\Tenant;
use App\Services\AasaService;
use App\Services\AssetlinksService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppController extends Controller
{
    public function __construct(
        private AasaService $aasa,
        private AssetlinksService $assetlinks,
    ) {}

    private function currentTenant(Request $request): Tenant
    {
        $tenantId = session('current_tenant_id');
        return Tenant::findOrFail($tenantId);
    }

    public function index(Request $request): View
    {
        $tenant = $this->currentTenant($request);
        $apps = App::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('apps.index', compact('tenant', 'apps'));
    }

    public function create(Request $request): View
    {
        $tenant = $this->currentTenant($request);
        return view('apps.create', compact('tenant'));
    }

    public function store(AppRequest $request): RedirectResponse
    {
        $tenant = $this->currentTenant($request);

        $data = $request->validated();
        $data['tenant_id'] = $tenant->id;

        App::create($data);

        $this->aasa->bust($tenant->id);
        $this->assetlinks->bust($tenant->id);

        return redirect()->route('apps.index')->with('success', 'App registered successfully.');
    }

    public function edit(Request $request, App $app): View
    {
        $tenant = $this->currentTenant($request);
        abort_unless($app->tenant_id === $tenant->id, 403);

        return view('apps.edit', compact('tenant', 'app'));
    }

    public function update(AppRequest $request, App $app): RedirectResponse
    {
        $tenant = $this->currentTenant($request);
        abort_unless($app->tenant_id === $tenant->id, 403);

        $app->update($request->validated());

        $this->aasa->bust($tenant->id);
        $this->assetlinks->bust($tenant->id);

        return redirect()->route('apps.index')->with('success', 'App updated.');
    }

    public function destroy(Request $request, App $app): RedirectResponse
    {
        $tenant = $this->currentTenant($request);
        abort_unless($app->tenant_id === $tenant->id, 403);

        $app->delete();

        $this->aasa->bust($tenant->id);
        $this->assetlinks->bust($tenant->id);

        return redirect()->route('apps.index')->with('success', 'App removed.');
    }
}
