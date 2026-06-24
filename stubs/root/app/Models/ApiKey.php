<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'key',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        if ($this->revoked_at) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public static function generate(User $user, string $name, ?int $expiryDays = null): string
    {
        $plainKey = 'df_'.Str::random(40);

        // Store plaintext key (not hashed) for easy viewing in admin
        static::create([
            'user_id' => $user->id,
            'name' => $name,
            'key' => $plainKey,
            'expires_at' => $expiryDays ? now()->addDays($expiryDays) : null,
        ]);

        return $plainKey;
    }
}
