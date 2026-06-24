<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentGateway extends Model
{
    use HasFactory;

    public const STRIPE = 'stripe';

    public const PADDLE = 'paddle';

    public const LEMON = 'lemon';

    public const MANUAL = 'manual';

    protected $fillable = [
        'code',
        'name',
        'is_active',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'config' => 'array',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'gateway_id');
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
