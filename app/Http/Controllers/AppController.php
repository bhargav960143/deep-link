<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppRequest;
use App\Models\App;
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

    public function index(Request $request): View
    {
        $this->authorize('viewAny', App::class);

        // Global scope auto-filters by tenant_id from session
        $apps = App::orderBy('created_at', 'desc')->get();

        return view('apps.index', ['apps' => $apps]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', App::class);

        return view('apps.create');
    }

    public function store(AppRequest $request): RedirectResponse
    {
        $this->authorize('create', App::class);

        $data = $request->validated();
        // tenant_id is auto-set by BelongsToTenant trait on creating

        App::create($data);

        $tenantId = session('current_tenant_id');
        $this->aasa->bust($tenantId);
        $this->assetlinks->bust($tenantId);

        return redirect()->route('apps.index')->with('success', 'App registered successfully.');
    }

    public function edit(Request $request, App $app): View
    {
        $this->authorize('update', $app);

        return view('apps.edit', compact('app'));
    }

    public function update(AppRequest $request, App $app): RedirectResponse
    {
        $this->authorize('update', $app);

        $app->update($request->validated());

        $tenantId = session('current_tenant_id');
        $this->aasa->bust($tenantId);
        $this->assetlinks->bust($tenantId);

        return redirect()->route('apps.index')->with('success', 'App updated.');
    }

    public function destroy(Request $request, App $app): RedirectResponse
    {
        $this->authorize('delete', $app);

        $app->delete();

        $tenantId = session('current_tenant_id');
        $this->aasa->bust($tenantId);
        $this->assetlinks->bust($tenantId);

        return redirect()->route('apps.index')->with('success', 'App removed.');
    }
}
