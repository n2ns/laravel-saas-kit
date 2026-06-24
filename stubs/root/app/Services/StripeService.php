<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Mail\SubscriptionActivatedMail;
use App\Models\CreditGrant;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use App\Support\LocaleProfile;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Cashier;
use Stripe\BillingPortal\Session as BillingPortalSession;

/**
 * StripeService
 *
 * Implements official "Post-Checkout Provisioning" best practices from Stripe and Laravel Cashier.
 *
 * Logic Flow:
 * 1. Checkout Session Creation: Attaches metadata to both Session and Subscription.
 * 2. Fulfillment (Idempotent): Guaranteed activation via Webhooks or Success-page polling.
 */
class StripeService
{
    /**
     * Create a Stripe Checkout Session.
     */
    public function createCheckoutSession(User $user, Product $product, Plan $plan, ?string $locale = 'auto')
    {
        if (! $plan->stripe_price_id) {
            throw new Exception('Plan does not have a Stripe price ID configured');
        }

        $locale = $locale ?? 'auto';
        $stripeLocale = $this->determineStripeLocale($locale);
        [$successUrl, $cancelUrl] = $this->checkoutReturnUrls($product, $locale);

        // Core Metadata used for fulfillment
        $metadata = [
            'user_id' => (string) $user->id,
            'product_id' => (string) $product->id,
            'plan_id' => (string) $plan->id,
            'source' => 'web_checkout',
        ];

        if ($plan->isLifetime() || $plan->isCreditPack()) {
            return $user->checkout([$plan->stripe_price_id => 1], [
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'locale' => $stripeLocale,
                'metadata' => $metadata,
                'allow_promotion_codes' => true,
            ]);
        }

        // Subscription Mode
        return $user->newSubscription($product->code, $plan->stripe_price_id)
            ->withMetadata($metadata) // Attaches to Subscription object
            ->checkout([
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'locale' => $stripeLocale,
                'metadata' => $metadata, // Attaches to Checkout Session object
                'allow_promotion_codes' => true,
            ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function checkoutReturnUrls(Product $product, ?string $locale): array
    {
        $localizedLocale = $locale === 'auto' ? null : $locale;
        $localePrefix = LocaleProfile::prefixFor($localizedLocale);
        $localePath = "{$localePrefix}/";

        return [
            url($localePath.'checkout/success?session_id={CHECKOUT_SESSION_ID}&product='.$product->code),
            url($localePath.'checkout/cancel?product='.$product->code),
        ];
    }

    /**
     * Fulfill a Checkout Session (Initial Purchase).
     * Official Entry Point for checkout.session.completed.
     */
    public function fulfillCheckout(string $sessionId): bool
    {
        try {
            $session = $this->getStripeClient()->checkout->sessions->retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                Log::info('StripeService: Session not paid yet', ['session_id' => $sessionId]);

                return false;
            }

            $metadata = $session->metadata;
            $userId = $metadata['user_id'] ?? null;
            $planId = $metadata['plan_id'] ?? null;

            if (! $userId || ! $planId) {
                Log::error('StripeService: Missing metadata in session', ['session_id' => $sessionId]);

                return false;
            }

            $user = User::find($userId);
            $plan = Plan::find($planId);

            if (! $user || ! $plan) {
                Log::error('StripeService: User or Plan not found', ['user_id' => $userId, 'plan_id' => $planId]);

                return false;
            }

            return DB::transaction(function () use ($user, $plan, $session) {
                $subscription = null;
                if ($session->mode === 'subscription' && $session->subscription) {
                    $stripeSubscription = $this->retrieveStripeSubscription((string) $session->subscription);
                    $subscription = $this->syncSubscriptionFromStripe($stripeSubscription, $user, $plan);
                }

                $order = $this->createStripeOrderOnce(
                    [
                        'gateway_id' => $this->stripeGatewayId(),
                        'provider_order_id' => (string) $session->id,
                    ],
                    [
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                        'subscription_id' => $subscription?->id,
                        'type' => OrderType::New,
                        'status' => OrderStatus::Paid,
                        'total' => $session->amount_total / 100,
                        'currency' => strtoupper($session->currency),
                        'provider_payment_id' => $session->payment_intent ?? null,
                        'paid_at' => now(),
                    ],
                );

                if (! $order?->wasRecentlyCreated) {
                    return true;
                }

                $this->grantCreditsForOrder($order, $plan);

                $user->increment('auth_epoch');
                try {
                    Mail::to($user)->send(new SubscriptionActivatedMail($user, $plan));
                } catch (Exception $e) {
                    Log::error('StripeService: Failed to send subscription activation email: '.$e->getMessage());
                }

                return true;
            });

        } catch (Exception $e) {
            Log::error('StripeService: Checkout fulfillment failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Handle Manual Refund from Stripe Dashboard (charge.refunded Webhook).
     *
     * @param  array  $charge  The Stripe Charge object
     */
    public function handleRefund(array $charge): bool
    {
        $paymentIntentId = $charge['payment_intent'] ?? null;
        $amountRefunded = ($charge['amount_refunded'] ?? 0) / 100;
        $isFullRefund = $charge['refunded'] ?? false;

        Log::info("StripeService: Processing refund for PI: {$paymentIntentId}", [
            'amount' => $amountRefunded,
            'is_full' => $isFullRefund,
        ]);

        if (! $paymentIntentId) {
            return false;
        }

        // 1. Find the local order
        $order = Order::where('provider_payment_id', $paymentIntentId)->first();

        if (! $order) {
            Log::warning("StripeService: Order not found for PI {$paymentIntentId} during refund.");

            return false;
        }

        return DB::transaction(function () use ($order, $amountRefunded, $isFullRefund) {
            // 2. Update Order Status
            $order->update([
                'status' => $isFullRefund ? OrderStatus::Refunded : OrderStatus::PartialRefund,
                'refunded_amount' => $amountRefunded,
                'refunded_at' => now(),
            ]);

            // 3. If full refund, cancel subscription if exists
            if ($isFullRefund && $order->subscription_id) {
                $subscription = Subscription::find($order->subscription_id);
                if ($subscription) {
                    $subscription->update([
                        'stripe_status' => Subscription::STATUS_CANCELLED,
                        'ends_at' => now(),
                    ]);
                    $subscription->user?->increment('auth_epoch');
                    Log::info("StripeService: Cancelled subscription for User [{$order->user_id}] due to full refund.");
                }
            }

            return true;
        });
    }

    /**
     * Handle subscription creation (Webhook).
     */
    public function handleSubscriptionCreated(array $stripeSubscription): void
    {
        Log::info('StripeService: Handling subscription created', ['subscription_id' => $stripeSubscription['id']]);

        $this->syncSubscriptionFromStripe($stripeSubscription);
    }

    /**
     * Handle subscription update (Renewal/Change).
     */
    public function handleSubscriptionUpdated(array $stripeSubscription): void
    {
        Log::info('StripeService: Handling subscription updated', ['subscription_id' => $stripeSubscription['id']]);

        $subscription = $this->syncSubscriptionFromStripe($stripeSubscription);

        if ($subscription) {
            $subscription->user->increment('auth_epoch');
        }
    }

    /**
     * Handle invoice paid (Recurring payments).
     *
     * @param  array<string, mixed>  $stripeInvoice
     */
    public function handleInvoicePaid(array $stripeInvoice): void
    {
        Log::info('StripeService: Handling invoice paid', ['invoice_id' => $stripeInvoice['id']]);

        // Only handle subscription cycle/renewals (avoid duplicating initial checkout)
        if (($stripeInvoice['billing_reason'] ?? '') !== 'subscription_cycle') {
            Log::info('StripeService: Skipping non-renewal invoice', ['reason' => $stripeInvoice['billing_reason']]);

            return;
        }

        $stripeSubscriptionId = $stripeInvoice['subscription'] ?? null;
        if (! $stripeSubscriptionId) {
            return;
        }

        $subscription = Subscription::where('stripe_id', $stripeSubscriptionId)->first();
        if (! $subscription) {
            Log::warning('StripeService: Subscription not found for renewal invoice', ['subscription_id' => $stripeSubscriptionId]);

            return;
        }

        DB::transaction(function () use ($subscription, $stripeInvoice) {
            $order = $this->createStripeOrderOnce(
                [
                    'gateway_id' => $this->stripeGatewayId(),
                    'provider_order_id' => (string) $stripeInvoice['id'],
                ],
                [
                    'user_id' => $subscription->user_id,
                    'plan_id' => $subscription->plan_id,
                    'subscription_id' => $subscription->id,
                    'type' => OrderType::Renewal,
                    'status' => OrderStatus::Paid,
                    'total' => $stripeInvoice['amount_paid'] / 100,
                    'currency' => strtoupper($stripeInvoice['currency']),
                    'provider_payment_id' => $stripeInvoice['payment_intent'] ?? null,
                    'paid_at' => now(),
                ],
            );

            if ($order?->wasRecentlyCreated) {
                $subscription->user->increment('auth_epoch');
            }
        });
    }

    /**
     * Handle subscription deletion (Webhook/Cancellation).
     */
    public function handleSubscriptionDeleted(array $stripeSubscription): void
    {
        Log::info('StripeService: Handling subscription deleted', ['subscription_id' => $stripeSubscription['id']]);

        $subscription = Subscription::where('stripe_id', $stripeSubscription['id'])->first();

        if ($subscription && $subscription->user) {
            $currentPeriodEndsAt = isset($stripeSubscription['current_period_end'])
                ? Carbon::createFromTimestamp($stripeSubscription['current_period_end'])
                : now();

            // Sync subscription status. A deleted Stripe subscription no longer grants access.
            $subscription->update([
                'ends_at' => $currentPeriodEndsAt,
                'current_period_ends_at' => $currentPeriodEndsAt,
                'stripe_status' => $stripeSubscription['status'] ?? Subscription::STATUS_CANCELLED,
            ]);
            $subscription->user->increment('auth_epoch');
        }
    }

    /**
     * Determine best Stripe locale.
     */
    protected function determineStripeLocale(?string $locale): string
    {
        $stripeSupported = [
            'ar', 'bg', 'cs', 'da', 'de', 'el', 'en', 'es', 'et', 'fi', 'fr', 'he', 'hu',
            'id', 'it', 'ja', 'ko', 'lt', 'lv', 'ms', 'nb', 'nl', 'pl', 'pt', 'ro', 'ru',
            'sk', 'sl', 'sv', 'th', 'tr', 'vi', 'zh', 'zh-HK', 'zh-TW',
        ];
        // Official Stripe Supported Locales (as of 2026)
        // Simplified Chinese must be 'zh'
        $strictMap = [
            'zh_CN' => 'zh',
            'zh' => 'zh',
            'zh-CN' => 'zh',
            'zh-HK' => 'zh-HK',
            'zh-TW' => 'zh-TW',
            'es' => 'es',
            'de' => 'de',
            'en' => 'en',
            'en-US' => 'en',
        ];

        if (isset($strictMap[$locale])) {
            return $strictMap[$locale];
        }

        // Final fallback: check for base language (e.g., 'es-ES' -> 'es')
        $base = explode('-', str_replace('_', '-', $locale ?? LocaleProfile::default()))[0];
        if (isset($strictMap[$base])) {
            return $strictMap[$base];
        }

        return 'auto';
    }

    /**
     * Portal Session.
     */
    public function createPortalSession(User $user, string $returnUrl): BillingPortalSession
    {
        $options = [];
        if (config('services.stripe.portal_configuration')) {
            $options['configuration'] = config('services.stripe.portal_configuration');
        }

        return $user->redirectToBillingPortal($returnUrl, $options);
    }

    /**
     * Get Stripe Client instance.
     */
    protected function getStripeClient()
    {
        return Cashier::stripe();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     */
    private function createStripeOrderOnce(array $attributes, array $values): ?Order
    {
        try {
            return Order::query()->firstOrCreate($attributes, $values);
        } catch (QueryException $e) {
            if (! $this->isDuplicateOrderException($e)) {
                throw $e;
            }

            return Order::query()->where($attributes)->first();
        }
    }

    private function isDuplicateOrderException(QueryException $e): bool
    {
        return (string) $e->getCode() === '23000'
            || str_contains($e->getMessage(), 'orders_provider_order_gateway_unique');
    }

    private function stripeGatewayId(): int
    {
        return PaymentGateway::where('code', PaymentGateway::STRIPE)->value('id') ?? 1;
    }

    private function grantCreditsForOrder(Order $order, Plan $plan): void
    {
        $credits = $plan->credits();
        if ($credits <= 0) {
            return;
        }

        $plan->loadMissing('product');

        CreditGrant::query()->firstOrCreate(
            [
                'source_type' => 'order',
                'source_id' => (string) $order->id,
                'product_code' => $plan->product->code ?? 'starter',
            ],
            [
                'user_id' => $order->user_id,
                'quantity' => $credits,
                'used' => 0,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function retrieveStripeSubscription(string $subscriptionId): array
    {
        $subscription = $this->getStripeClient()->subscriptions->retrieve($subscriptionId);

        return $this->stripeObjectToArray($subscription);
    }

    /**
     * @param  array<string, mixed>|object  $stripeSubscription
     */
    private function syncSubscriptionFromStripe(array|object $stripeSubscription, ?User $knownUser = null, ?Plan $knownPlan = null): ?Subscription
    {
        $payload = $this->stripeObjectToArray($stripeSubscription);
        $stripeId = $payload['id'] ?? null;

        if (! $stripeId) {
            return null;
        }

        $existingSubscription = Subscription::where('stripe_id', $stripeId)->with('user', 'plan')->first();
        $metadata = $this->stripeObjectToArray($payload['metadata'] ?? []);
        $existingPlan = $existingSubscription?->plan;
        $existingUser = $existingSubscription?->user;
        $plan = $knownPlan
            ?? ($existingPlan instanceof Plan ? $existingPlan : null)
            ?? $this->resolvePlanFromStripeSubscription($payload, $metadata);
        $user = $knownUser
            ?? ($existingUser instanceof User ? $existingUser : null)
            ?? $this->resolveUserFromStripeSubscription($payload, $metadata);

        if (! $user || ! $plan) {
            Log::warning('StripeService: Cannot sync subscription without user or plan', [
                'subscription_id' => $stripeId,
                'user_id' => $metadata['user_id'] ?? null,
                'plan_id' => $metadata['plan_id'] ?? null,
            ]);

            return $existingSubscription;
        }

        $price = $this->stripeSubscriptionPrice($payload);
        $currentPeriodEndsAt = isset($payload['current_period_end'])
            ? Carbon::createFromTimestamp((int) $payload['current_period_end'])
            : null;
        $trialEndsAt = isset($payload['trial_end'])
            ? Carbon::createFromTimestamp((int) $payload['trial_end'])
            : null;
        $status = (string) ($payload['status'] ?? Subscription::STATUS_ACTIVE);
        $isCancelAtPeriodEnd = (bool) ($payload['cancel_at_period_end'] ?? false);
        $isCanceled = $status === Subscription::STATUS_CANCELLED;

        return Subscription::updateOrCreate(
            ['stripe_id' => (string) $stripeId],
            [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'type' => $plan->product->code,
                'stripe_status' => $status,
                'stripe_price' => $price['id'] ?? $plan->stripe_price_id,
                'quantity' => $price['quantity'] ?? 1,
                'trial_ends_at' => $trialEndsAt,
                'current_period_ends_at' => $currentPeriodEndsAt,
                'ends_at' => ($isCancelAtPeriodEnd || $isCanceled) ? ($currentPeriodEndsAt ?? now()) : null,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $metadata
     */
    private function resolveUserFromStripeSubscription(array $payload, array $metadata): ?User
    {
        if (! empty($metadata['user_id'])) {
            return User::find($metadata['user_id']);
        }

        if (! empty($payload['customer'])) {
            return User::where('stripe_id', (string) $payload['customer'])->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $metadata
     */
    private function resolvePlanFromStripeSubscription(array $payload, array $metadata): ?Plan
    {
        if (! empty($metadata['plan_id'])) {
            return Plan::find($metadata['plan_id']);
        }

        $price = $this->stripeSubscriptionPrice($payload);

        if (! empty($price['id'])) {
            return Plan::where('stripe_price_id', (string) $price['id'])->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{id: string|null, quantity: int|null}
     */
    private function stripeSubscriptionPrice(array $payload): array
    {
        $items = $this->stripeObjectToArray($payload['items'] ?? []);
        $data = $items['data'] ?? [];
        $firstItem = is_array($data) ? ($data[0] ?? []) : [];
        $firstItem = $this->stripeObjectToArray($firstItem);
        $price = $this->stripeObjectToArray($firstItem['price'] ?? []);

        return [
            'id' => isset($price['id']) ? (string) $price['id'] : null,
            'quantity' => isset($firstItem['quantity']) ? (int) $firstItem['quantity'] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function stripeObjectToArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        if (is_object($value)) {
            return get_object_vars($value);
        }

        return [];
    }
}
