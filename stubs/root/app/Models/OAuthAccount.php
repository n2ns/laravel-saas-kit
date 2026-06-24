<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OAuthAccount extends Model
{
    use HasFactory;

    protected $table = 'oauth_accounts';

    public const PROVIDER_GOOGLE = 'google';

    public const PROVIDER_GITHUB = 'github';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_email',
        'access_token',
        'refresh_token',
        'token_expires_at',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isTokenExpired(): bool
    {
        if (! $this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Find OAuth account by provider and provider ID.
     */
    public static function findByProvider(string $provider, string $providerId): ?self
    {
        return static::where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();
    }
}
