<?php

namespace App\Services;

use App\Models\App;
use Illuminate\Support\Facades\Cache;

class AssetlinksService
{
    public function generate(string $tenantId): array
    {
        return Cache::remember("assetlinks:{$tenantId}", 300, function () use ($tenantId) {
            $apps = App::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->whereIn('platform', ['android', 'both'])
                ->whereNotNull('android_package_name')
                ->whereNotNull('android_sha256_fingerprints')
                ->get();

            return $apps->map(fn ($app) => [
                'relation' => ['delegate_permission/common.handle_all_urls'],
                'target' => [
                    'namespace' => 'android_app',
                    'package_name' => $app->android_package_name,
                    'sha256_cert_fingerprints' => $app->android_sha256_fingerprints,
                ],
            ])->values()->all();
        });
    }

    public function bust(string $tenantId): void
    {
        Cache::forget("assetlinks:{$tenantId}");
    }
}
