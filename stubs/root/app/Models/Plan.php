<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    public const TIER_FREE = 'free';

    public const TIER_PRO = 'pro';

    public const TIER_ENTERPRISE = 'enterprise';

    public const TIER_ADDON = 'addon';

    public const BILLING_FREE = 'free';

    public const BILLING_MONTHLY = 'monthly';

    public const BILLING_YEARLY = 'yearly';

    public const BILLING_LIFETIME = 'lifetime';

    public const BILLING_ONE_TIME = 'one_time';

    protected $table = 'plans';

    protected $fillable = [
        'product_id',
        'code',
        'name',
        'description',
        'tier',
        'billing_cycle',
        'price',
        'currency',
        'trial_days',
        'features',
        'display_payload',
        'stripe_price_id',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'trial_days' => 'integer',
            'features' => 'array',
            'display_payload' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'plan_id');
    }

    public function isRecurring(): bool
    {
        return in_array($this->billing_cycle, [self::BILLING_MONTHLY, self::BILLING_YEARLY], true);
    }

    public function isLifetime(): bool
    {
        return $this->billing_cycle === self::BILLING_LIFETIME;
    }

    public function isCreditPack(): bool
    {
        return $this->credits() > 0;
    }

    public function credits(): int
    {
        $features = $this->getAttribute('features');
        $features = is_array($features) ? $features : [];
        $credits = $features['credits'] ?? null;

        return is_numeric($credits) ? max(0, (int) $credits) : 0;
    }

    public function isFree(): bool
    {
        return $this->billing_cycle === self::BILLING_FREE || $this->price <= 0;
    }

    public function getProviderPriceId(string $gateway): ?string
    {
        return match ($gateway) {
            PaymentGateway::STRIPE => $this->stripe_price_id,
            default => null,
        };
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeWithLocalizedTranslations(Builder $query, ?string $locale = null): Builder
    {
        return $query;
    }

    public function getLocalized(string $attribute, ?string $locale = null): mixed
    {
        return $this->getAttribute($attribute);
    }

    /**
     * @return array<string, mixed>
     */
    public function getContentPayloadAttribute(): array
    {
        return $this->display_payload ?? [];
    }

    /**
     * @param  array<string, mixed>|string|null  $value
     */
    public function setContentPayloadAttribute(array|string|null $value): void
    {
        $this->attributes['display_payload'] = is_string($value) ? $value : json_encode($value);
    }

    public static function findByCode(string $code): ?static
    {
        return static::query()->where('code', $code)->first();
    }
}
