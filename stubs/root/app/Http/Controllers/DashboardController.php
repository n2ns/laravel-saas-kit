<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Overview tab.
     */
    public function index(Request $request): View
    {
        return $this->renderDashboard($request, 'overview');
    }

    /**
     * Subscriptions tab.
     */
    public function subscriptions(Request $request): View
    {
        return $this->renderDashboard($request, 'subscriptions');
    }

    /**
     * Orders tab.
     */
    public function orders(Request $request): View
    {
        return $this->renderDashboard($request, 'orders');
    }

    /**
     * Settings tab.
     */
    public function settings(Request $request): View
    {
        return $this->renderDashboard($request, 'settings');
    }

    /**
     * Render the single-page dashboard with the requested tab pre-selected.
     * All tabs share the same data so they are deep-linkable and consistent.
     */
    private function renderDashboard(Request $request, string $activeTab): View
    {
        $user = $request->user();

        $orders = $user->orders()
            ->with(['plan', 'gateway'])
            ->orderByDesc('created_at')
            ->get();

        $subscriptions = $user->subscriptions()
            ->with(Subscription::productCatalogRelations())
            ->whereIn('stripe_status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIALING,
                Subscription::STATUS_PAST_DUE,
            ])
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->orderByDesc('created_at')
            ->get();

        return view('dashboard.index', [
            'user' => $user,
            'orders' => $orders,
            'subscriptions' => $subscriptions,
            'oauthAccounts' => $user->oauthAccounts,
            'activeTab' => $activeTab,
        ]);
    }
}
