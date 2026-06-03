<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class App extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'platform',
        'ios_bundle_id',
        'ios_team_id',
        'ios_app_id',
        'ios_store_url',
        'ios_min_version',
        'android_package_name',
        'android_sha256_fingerprints',
        'android_store_url',
        'uri_scheme',
        'web_fallback_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'android_sha256_fingerprints' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $app) {
            $app->uuid ??= (string) Str::uuid();
        });

        static::saving(function (self $app) {
            if ($app->ios_team_id && $app->ios_bundle_id) {
                $app->ios_app_id = $app->ios_team_id . '.' . $app->ios_bundle_id;
            }
            if ($app->android_sha256_fingerprints) {
                $app->android_sha256_fingerprints = array_values(array_unique(
                    array_map('strtoupper', (array) $app->android_sha256_fingerprints)
                ));
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function hasIos(): bool
    {
        return in_array($this->platform, ['ios', 'both']);
    }

    public function hasAndroid(): bool
    {
        return in_array($this->platform, ['android', 'both']);
    }

    public function iosAppStoreId(): ?string
    {
        if (! $this->ios_store_url) {
            return null;
        }
        preg_match('/id(\d+)/', $this->ios_store_url, $matches);
        return $matches[1] ?? null;
    }
}
