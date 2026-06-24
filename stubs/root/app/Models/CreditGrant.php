<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditGrant extends Model
{
    protected $fillable = [
        'user_id',
        'product_code',
        'quantity',
        'used',
        'source_type',
        'source_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'used' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function remaining(): int
    {
        return max(0, $this->quantity - $this->used);
    }
}
