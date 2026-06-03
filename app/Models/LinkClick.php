<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkClick extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'link_id', 'clicked_at', 'platform', 'os_version', 'browser',
        'device_type', 'outcome', 'country_code', 'region', 'city',
        'ip_hash', 'referrer_domain', 'utm_source', 'utm_medium', 'utm_campaign',
        'is_unique',
    ];

    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
            'is_unique' => 'boolean',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
