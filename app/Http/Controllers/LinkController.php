<?php

namespace App\Http\Controllers;

use App\Http\Requests\LinkRequest;
use App\Models\App;
use App\Models\Domain;
use App\Models\Link;
use App\Services\ShortCodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LinkController extends Controller
{
    public function __construct(private ShortCodeGenerator $codeGen) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Link::class);

        // Global scope auto-filters by tenant_id
        $links = Link::with(['app', 'domain'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('links.index', compact('links'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Link::class);

        // Global scope auto-filters by tenant_id
        $apps = App::where('is_active', true)->get();
        $domains = Domain::where('status', 'active')->get();

        return view('links.create', compact('apps', 'domains'));
    }

    public function store(LinkRequest $request): RedirectResponse
    {
        $this->authorize('create', Link::class);

        $data = $request->validated();
        $data['show_interstitial'] = $request->boolean('show_interstitial');

        if (empty($data['short_code'])) {
            $data['short_code'] = $this->codeGen->generate((int) $data['domain_id']);
        }

        // tenant_id is auto-set by BelongsToTenant trait
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
        $this->authorize('update', $link);

        // Global scope auto-filters by tenant_id
        $apps = App::where('is_active', true)->get();
        $domains = Domain::where('status', 'active')->get();

        return view('links.edit', compact('link', 'apps', 'domains'));
    }

    public function update(LinkRequest $request, Link $link): RedirectResponse
    {
        $this->authorize('update', $link);

        $data = $request->validated();
        $data['show_interstitial'] = $request->boolean('show_interstitial');

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
        $this->authorize('delete', $link);

        $link->delete();

        return redirect()->route('links.index')->with('success', 'Link deleted.');
    }
}
