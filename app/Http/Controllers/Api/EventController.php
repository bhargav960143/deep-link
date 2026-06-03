<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'click_token' => 'required|string',
            'event_name' => 'required|string|max:255',
            'properties' => 'nullable|array',
        ]);

        try {
            $clickId = \Illuminate\Support\Facades\Crypt::decryptString($validated['click_token']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(['error' => 'Invalid click token'], 400);
        }

        $click = \App\Models\LinkClick::with('link.domain')->findOrFail($clickId);

        if ($click->link->domain->tenant_id !== tenancy()->tenant->id) {
            return response()->json(['error' => 'Unauthorized click token'], 403);
        }

        $ip = $request->ip() ?? '0.0.0.0';
        $ua = $request->userAgent() ?? '';
        
        $platformDetector = app(\App\Services\PlatformDetector::class);
        $platformInfo = $platformDetector->detect($ua);
        $platform = $platformInfo['platform'] ?? 'unknown';

        $ipHash = hash_hmac('sha256', $ip . $platform, config('app.key'));

        DB::table('link_events')->insert([
            'link_id' => $click->link_id,
            'event_name' => $validated['event_name'],
            'properties' => isset($validated['properties']) ? json_encode($validated['properties']) : null,
            'ip_hash' => $ipHash,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
