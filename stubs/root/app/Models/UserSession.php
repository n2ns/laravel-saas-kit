<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'sid',
        'user_id',
        'client_id',
        'product_code',
        'device_id_hash',
        'device_name',
        'platform',
        'app_version',
        'user_agent',
        'ip_address',
        'last_seen_at',
        'expires_at',
        'revoked_at',
        'revoked_reason',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(UserSessionToken::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
