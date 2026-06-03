<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Link extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'app_id', 'domain_id', 'short_code', 'destination_path',
        'web_fallback_url', 'ios_fallback_url', 'android_fallback_url',
        'og_title', 'og_description', 'og_image_url',
        'link_type', 'is_active', 'show_interstitial', 'password', 'expires_at', 'max_clicks',
        'title', 'tags', 'utm_source', 'utm_medium', 'utm_campaign', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_active' => 'boolean',
            'show_interstitial' => 'boolean',
            'expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($l) => $l->uuid ??= (string) Str::uuid());
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(LinkClick::class);
    }

    public function getShortUrlAttribute(): string
    {
        return 'https://' . $this->domain->domain . '/l/' . $this->short_code;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isMaxClicksReached(): bool
    {
        return $this->max_clicks && $this->click_count >= $this->max_clicks;
    }

    public function isAvailable(): bool
    {
        return $this->is_active && ! $this->isExpired() && ! $this->isMaxClicksReached();
    }

    public function hasPassword(): bool
    {
        return ! is_null($this->getRawOriginal('password'));
    }
}
