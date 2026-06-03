<?php

namespace App\Http\Controllers;

use App\Jobs\LogLinkClick;
use App\Models\Link;
use App\Services\BotDetector;
use App\Services\PlatformDetector;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LinkRedirectController extends Controller
{
    public function __construct(
        private BotDetector $botDetector,
        private PlatformDetector $platformDetector,
    ) {}

    public function handle(Request $request, string $shortCode): Response|View
    {
        $tenantId = tenancy()->tenant->id;

        $link = Cache::remember(
            "link:{$tenantId}:{$shortCode}",
            60,
            fn () => Link::with(['app', 'domain'])
                ->whereHas('domain', fn ($q) => $q->where('tenant_id', $tenantId))
                ->where('short_code', $shortCode)
                ->first()
        );

        if (! $link) {
            return $this->errorResponse('not_found', 'Link not found', 404);
        }

        if (! $link->is_active) {
            $this->logClick($request, $link, 'link_inactive');
            return $this->errorResponse('inactive', 'This link is no longer active');
        }

        if ($link->isExpired()) {
            $this->logClick($request, $link, 'link_expired');
            return $this->errorResponse('expired', 'This link has expired', 410);
        }

        if ($link->isMaxClicksReached()) {
            $this->logClick($request, $link, 'max_clicks_reached');
            return $this->errorResponse('max_clicks', 'This link is no longer available');
        }

        $ua = $request->userAgent() ?? '';

        // Bot detection — serve OG-only response
        if ($this->botDetector->isBot($ua)) {
            $this->logClick($request, $link, 'bot_filtered');
            return $this->botResponse($link, $request);
        }

        // Password check
        if ($link->hasPassword()) {
            $sessionKey = "link_unlocked_{$link->id}";
            if (! $request->session()->get($sessionKey)) {
                return response()->view('redirect.password', ['link' => $link]);
            }
        }

        $platform = $this->platformDetector->detect($ua);
        $outcome = $this->guessOutcome($platform['platform'], $link);

        $this->logClick($request, $link, $outcome, $platform);

        return response()->view('redirect.landing', [
            'link' => $link,
            'app' => $link->app,
            'platform' => $platform['platform'],
            'webFallback' => $link->web_fallback_url ?? $link->app?->web_fallback_url,
            'canonicalUrl' => 'https://' . $link->domain->domain . '/l/' . $link->short_code,
        ]);
    }

    public function unlock(Request $request, string $shortCode): View|\Illuminate\Http\RedirectResponse
    {
        $tenantId = tenancy()->tenant->id;
        $link = Link::whereHas('domain', fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('short_code', $shortCode)
            ->firstOrFail();

        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->password, $link->getRawOriginal('password'))) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $request->session()->put("link_unlocked_{$link->id}", true);

        return redirect()->route('tenant.redirect', ['shortCode' => $shortCode]);
    }

    private function guessOutcome(string $platform, Link $link): string
    {
        return match ($platform) {
            'ios' => $link->app?->ios_store_url ? 'store_redirect_ios' : 'web_fallback',
            'android' => $link->app?->android_store_url ? 'store_redirect_android' : 'web_fallback',
            default => 'web_fallback',
        };
    }

    private function logClick(Request $request, Link $link, string $outcome, array $platform = []): void
    {
        if (empty($platform)) {
            $platform = $this->platformDetector->detect($request->userAgent() ?? '');
        }

        LogLinkClick::dispatch(
            $link->id,
            $request->ip() ?? '0.0.0.0',
            $request->userAgent() ?? '',
            $outcome,
            $platform['platform'] ?? 'unknown',
            $platform['device_type'] ?? 'desktop',
            $platform['os_version'] ?? null,
            $platform['browser'] ?? null,
            $request->header('Referer'),
            $link->utm_source,
            $link->utm_medium,
            $link->utm_campaign,
        );
    }

    private function errorResponse(string $type, string $message, int $status = 200): Response
    {
        return response()->view('redirect.error', compact('type', 'message'), $status);
    }

    private function botResponse(Link $link, Request $request): Response
    {
        $webFallback = $link->web_fallback_url ?? $link->app?->web_fallback_url;
        $canonicalUrl = 'https://' . $link->domain->domain . '/l/' . $link->short_code;

        return response()->view('redirect.landing', [
            'link' => $link,
            'app' => $link->app,
            'platform' => 'bot',
            'webFallback' => $webFallback,
            'canonicalUrl' => $canonicalUrl,
            'isBot' => true,
        ]);
    }
}
