<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Cashier\Subscription as CashierSubscription;

class Subscription extends CashierSubscription
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_TRIALING = 'trialing';

    public const STATUS_PAST_DUE = 'past_due';

    public const STATUS_CANCELLED = 'canceled'; // Stripe uses 'canceled'

    /**
     * @return array<int, string>
     */
    public static function productCatalogRelations(): array
    {
        return [
            'plan.product.catalogItem.profile',
            'plan.product.catalogItem.detail.translations',
            'plan.product.catalogItem.translations',
        ];
    }

    /**
     * Get the plan associated with the subscription.
     *
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isTrialing(): bool
    {
        return $this->stripe_status === self::STATUS_TRIALING;
    }

    public function isCancelled(): bool
    {
        return $this->stripe_status === self::STATUS_CANCELLED || $this->ends_at !== null;
    }

    public function onGracePeriod(): bool
    {
        return $this->ends_at && $this->ends_at->isFuture();
    }

    protected function casts(): array
    {
        return [
            'ends_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function getCurrentPeriodEndAttribute(): ?Carbon
    {
        return $this->current_period_ends_at;
    }

    public function getCancelledAtAttribute(): ?Carbon
    {
        return $this->ends_at;
    }
}
