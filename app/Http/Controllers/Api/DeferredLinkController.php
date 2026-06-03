<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LinkClick;
use App\Services\PlatformDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeferredLinkController extends Controller
{
    public function __construct(private PlatformDetector $platformDetector) {}

    public function show(Request $request): JsonResponse
    {
        $ip = $request->ip() ?? '0.0.0.0';
        $ua = $request->userAgent() ?? '';
        
        $platformInfo = $this->platformDetector->detect($ua);
        $platform = $platformInfo['platform'] ?? 'unknown';

        $ipHash = hash_hmac('sha256', $ip . $platform, config('app.key'));

        $recentClick = LinkClick::with('link')
            ->whereHas('link.domain', fn($q) => $q->where('tenant_id', tenancy()->tenant->id))
            ->where('ip_hash', $ipHash)
            ->where('platform', $platform)
            ->where('clicked_at', '>=', now()->subHour())
            ->orderBy('clicked_at', 'desc')
            ->first();

        if (! $recentClick || ! $recentClick->link) {
            return response()->json(['found' => false], 404);
        }

        $link = $recentClick->link;

        return response()->json([
            'found' => true,
            'click_token' => \Illuminate\Support\Facades\Crypt::encryptString((string)$recentClick->id),
            'link' => [
                'id' => $link->id,
                'short_code' => $link->short_code,
                'destination_path' => $link->destination_path,
                'utm_source' => $link->utm_source,
                'utm_medium' => $link->utm_medium,
                'utm_campaign' => $link->utm_campaign,
                'tags' => $link->tags,
            ],
        ]);
    }
}
