<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Product;
use App\Services\StripeService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    protected StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Create a checkout session and redirect to Stripe.
     */
    public function create(Request $request, string $productCode, string $planTier = 'pro')
    {

        // User must be authenticated
        if (! Auth::check()) {
            $checkoutUrl = localized_route('checkout.create', ['product' => $productCode, 'plan' => $planTier]);
            session(['url.intended' => $checkoutUrl]);

            return redirect(localized_route('login'));
        }

        $user = Auth::user();

        // Find product
        $product = Product::where('code', $productCode)->first();
        if (! $product) {
            abort(404, 'Product not found: '.$productCode);
        }

        // Check if product subscriptions are paused
        if ($product->isPaused()) {
            return redirect(localized_route('catalog.pricing', ['slug' => $productCode]))
                ->with('error', __('products.pause_reasons.'.$product->pause_reason));
        }

        // Find plan by code, such as starter_plus_monthly, or by tier, such as pro.
        $plan = Plan::where('product_id', $product->id)
            ->where(function ($query) use ($planTier) {
                $query->where('tier', $planTier)
                    ->orWhere('code', $planTier);
            })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();

        if (! $plan) {
            abort(404, 'Plan not found for product: '.$productCode.' with identifier: '.$planTier);
        }

        // Credit packs are add-ons. Users may buy them even with an active subscription.
        if (! $plan->isCreditPack() && $user->hasActiveSubscription($productCode)) {
            return redirect(localized_route('dashboard'))
                ->with('info', __('checkout.already_have_active_subscription'));
        }

        try {
            $stripeLocale = $request->query('lang', app()->getLocale());

            $session = $this->stripeService->createCheckoutSession($user, $product, $plan, $stripeLocale);

            return $session;
        } catch (Exception $e) {
            Log::error('Failed to create checkout session', [
                'user_id' => $user->id,
                'product' => $productCode,
                'plan' => $planTier,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to create checkout session. Please try again.');
        }
    }

    /**
     * Payment success page.
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        $productCode = $request->query('product');

        if ($sessionId) {
            // OPTIMIZATION: Check local DB first to avoid redundant Stripe API calls on refresh
            $alreadyFulfilled = Order::where('provider_order_id', $sessionId)->exists();

            if (! $alreadyFulfilled) {
                // OFFICIAL PATTERN: Trigger idempotent fulfillment on success redirect.
                // This ensures instant access even if Webhook is delayed.
                try {
                    $this->stripeService->fulfillCheckout($sessionId);
                } catch (Exception $e) {
                    Log::warning('Success page fulfillment failed', [
                        'session_id' => $sessionId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $product = Product::where('code', $productCode)->first();

        return view('checkout.success', [
            'sessionId' => $sessionId,
            'product' => $product,
            'productCode' => $productCode,
        ]);
    }

    /**
     * Payment cancelled page.
     */
    public function cancel(Request $request)
    {
        $productCode = $request->query('product');

        $product = Product::where('code', $productCode)->first();

        return view('checkout.cancel', [
            'product' => $product,
            'productCode' => $productCode,
        ]);
    }

    /**
     * Redirect to Stripe Customer Portal for subscription management.
     */
    public function portal(Request $request, ?string $productCode = null)
    {
        if (! Auth::check()) {
            return redirect(localized_route('login'));
        }

        $user = Auth::user();

        if (! $user->stripe_id) {
            return redirect(localized_route('dashboard'))->with('error', 'No active subscription found.');
        }

        try {
            $returnUrl = $productCode
                ? (Product::findByCode($productCode)?->publicUrl(app()->getLocale()) ?? localized_route('catalog.show', ['slug' => $productCode]))
                : localized_route('dashboard');

            $session = $this->stripeService->createPortalSession($user, $returnUrl);

            return $session;
        } catch (Exception $e) {
            Log::error('Failed to create portal session', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to access subscription management. Please try again.');
        }
    }
}
