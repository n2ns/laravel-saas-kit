<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements FilamentUser, OAuthenticatable
{
    use Billable, HasApiTokens, HasFactory, Notifiable;

    /**
     * Override Cashier's default subscription model.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Check if user can access Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }

    /**
     * Helper to check if user is an admin.
     */
    public function isAdmin(): bool
    {
        $adminEmails = config('app.admin_emails', []);

        return (bool) $this->is_admin || in_array(strtolower(trim((string) $this->email)), $adminEmails, true);
    }

    protected $fillable = [
        'name',
        'email',
        'is_admin',
        'registration_source',
        'avatar',
        'email_verified_at',
        'password',
        'first_client',
        'banned_at',
        'auth_epoch',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'banned_at' => 'datetime',
            'is_admin' => 'boolean',
        ];
    }

    public function getStatus(): string
    {
        return $this->banned_at ? 'banned' : 'active';
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    public function getPlan(): string
    {
        if ($this->relationLoaded('subscriptions')) {
            $proSubscription = $this->subscriptions
                ->filter(fn ($subscription): bool => $subscription instanceof Subscription && in_array($subscription->stripe_status, [
                    Subscription::STATUS_ACTIVE,
                    Subscription::STATUS_TRIALING,
                    Subscription::STATUS_PAST_DUE,
                ], true))
                ->filter(fn ($subscription): bool => $subscription->ends_at === null || $subscription->ends_at->isFuture())
                ->first(fn ($subscription): bool => $subscription->relationLoaded('plan')
                    && $subscription->plan
                    && in_array($subscription->plan->tier, ['pro', 'enterprise'], true));

            return $proSubscription instanceof Subscription && $proSubscription->plan?->billing_cycle === 'lifetime'
                ? 'lifetime'
                : ($proSubscription instanceof Subscription ? 'pro' : 'free');
        }

        // Check for active pro subscription
        /** @var Subscription|null $proSubscription */
        $proSubscription = $this->subscriptions()
            ->with('plan')
            ->whereIn('stripe_status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING, Subscription::STATUS_PAST_DUE])
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->whereHas('plan', fn ($q) => $q->where('tier', 'pro')->orWhere('tier', 'enterprise'))
            ->first();

        if ($proSubscription) {
            return $proSubscription->plan?->billing_cycle === 'lifetime' ? 'lifetime' : 'pro';
        }

        return 'free';
    }

    // ========== Relationships ==========

    public function oauthAccounts(): HasMany
    {
        return $this->hasMany(OAuthAccount::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<CreditGrant, $this>
     */
    public function creditGrants(): HasMany
    {
        return $this->hasMany(CreditGrant::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    /**
     * @return HasMany<BlogPost, $this>
     */
    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    /**
     * @return HasMany<UserSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    /**
     * @return HasMany<CatalogInterestSignup, $this>
     */
    public function catalogInterestSignups(): HasMany
    {
        return $this->hasMany(CatalogInterestSignup::class);
    }

    // ========== Subscription Helpers ==========

    /**
     * Check if user has Pro tier subscription for a product.
     */
    public function hasProSubscription(string $productCode): bool
    {
        // @phpstan-ignore-next-line argument.templateType
        return $this->subscriptions()
            ->whereIn('stripe_status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING, Subscription::STATUS_PAST_DUE])
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->whereHas('plan', fn ($q) => $q
                ->whereHas('product', fn ($pq) => $pq->where('code', $productCode))
                ->where(fn ($tq) => $tq->where('tier', 'pro')->orWhere('tier', 'enterprise'))
            )
            ->exists();
    }

    /**
     * Get active subscription for a product.
     * Includes STATUS_PAST_DUE to allow for grace period during payment retries.
     */
    public function getSubscriptionFor(string $productCode): ?Subscription
    {
        return $this->subscriptions()
            ->whereHas('plan.product', fn ($q) => $q->where('code', $productCode))
            ->whereIn('stripe_status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIALING,
                Subscription::STATUS_PAST_DUE,
            ])
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->first();
    }

    /**
     * Check if user has active subscription for a product.
     */
    public function hasActiveSubscription(string $productCode): bool
    {
        return $this->getSubscriptionFor($productCode) !== null;
    }
}
