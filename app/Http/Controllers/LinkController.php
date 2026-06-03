<?php

namespace App\Http\Controllers;

use App\Http\Requests\LinkRequest;
use App\Models\App;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Tenant;
use App\Services\ShortCodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LinkController extends Controller
{
    public function __construct(private ShortCodeGenerator $codeGen) {}

    private function tenant(): Tenant
    {
        return Tenant::findOrFail(session('current_tenant_id'));
    }

    public function index(Request $request): View
    {
        $tenant = $this->tenant();
        $links = Link::with(['app', 'domain'])
            ->where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('links.index', compact('tenant', 'links'));
    }

    public function create(Request $request): View
    {
        $tenant = $this->tenant();
        $apps = App::where('tenant_id', $tenant->id)->where('is_active', true)->get();
        $domains = Domain::where('tenant_id', $tenant->id)->where('status', 'active')->get();

        return view('links.create', compact('tenant', 'apps', 'domains'));
    }

    public function store(LinkRequest $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $data = $request->validated();

        if (empty($data['short_code'])) {
            $data['short_code'] = $this->codeGen->generate((int) $data['domain_id']);
        }

        $data['tenant_id'] = $tenant->id;
        $data['created_by'] = $request->user()->id;

        // Don't store empty password
        if (empty($data['password'])) {
            unset($data['password']);
        }

        Link::create($data);

        return redirect()->route('links.index')->with('success', 'Link created.');
    }

    public function edit(Request $request, Link $link): View
    {
        $tenant = $this->tenant();
        abort_unless($link->tenant_id === $tenant->id, 403);

        $apps = App::where('tenant_id', $tenant->id)->where('is_active', true)->get();
        $domains = Domain::where('tenant_id', $tenant->id)->where('status', 'active')->get();

        return view('links.edit', compact('tenant', 'link', 'apps', 'domains'));
    }

    public function update(LinkRequest $request, Link $link): RedirectResponse
    {
        $tenant = $this->tenant();
        abort_unless($link->tenant_id === $tenant->id, 403);

        $data = $request->validated();

        if (empty($data['short_code'])) {
            $data['short_code'] = $link->short_code; // keep existing
        }

        if (empty($data['password'])) {
            unset($data['password']); // don't clear existing password if field blank
        }

        $link->update($data);

        return redirect()->route('links.index')->with('success', 'Link updated.');
    }

    public function destroy(Request $request, Link $link): RedirectResponse
    {
        abort_unless($link->tenant_id === $this->tenant()->id, 403);
        $link->delete();

        return redirect()->route('links.index')->with('success', 'Link deleted.');
    }
}
