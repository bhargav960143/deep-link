<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domain extends Model
{
    protected $table = 'domains';
    protected $keyType = 'int';

    protected $fillable = [
        'tenant_id', 'domain', 'type', 'is_primary',
        'status', 'verification_token', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
