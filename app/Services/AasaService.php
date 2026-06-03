<?php

namespace App\Services;

use App\Models\App;
use Illuminate\Support\Facades\Cache;

class AasaService
{
    public function generate(string $tenantId): array
    {
        return Cache::remember("aasa:{$tenantId}", 300, function () use ($tenantId) {
            $apps = App::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->whereIn('platform', ['ios', 'both'])
                ->whereNotNull('ios_app_id')
                ->get();

            $details = $apps->map(fn ($app) => [
                'appIDs' => [$app->ios_app_id],
                'components' => [
                    ['/' => '/l/*', 'comment' => 'short links'],
                ],
            ])->values()->all();

            $appIds = $apps->pluck('ios_app_id')->all();

            return [
                'applinks' => [
                    'details' => $details,
                ],
                'activitycontinuation' => [
                    'apps' => $appIds,
                ],
                'webcredentials' => [
                    'apps' => $appIds,
                ],
            ];
        });
    }

    public function bust(string $tenantId): void
    {
        Cache::forget("aasa:{$tenantId}");
    }
}
