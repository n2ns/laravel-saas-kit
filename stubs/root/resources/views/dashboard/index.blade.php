@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')
<div class="dashboard-page-bg bg-bg-body min-h-[calc(100vh-80px)]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
         x-data="{
            activeTab: '{{ $activeTab ?? 'overview' }}',
            tabUrls: {
                overview: '{{ localized_route('dashboard') }}',
                subscriptions: '{{ localized_route('dashboard.subscriptions') }}',
                orders: '{{ localized_route('dashboard.orders') }}',
                settings: '{{ localized_route('dashboard.settings') }}'
            },
            go(tab) {
                if (this.activeTab === tab) return;
                this.activeTab = tab;
                window.history.pushState({ tab }, '', this.tabUrls[tab]);
            },
            init() {
                window.addEventListener('popstate', (e) => {
                    this.activeTab = (e.state && e.state.tab) || '{{ $activeTab ?? 'overview' }}';
                });
            }
         }">

        <div class="flex flex-col md:flex-row gap-0 md:gap-10">

            <!-- ============================================================
                 Sidebar
                 ============================================================ -->
            <aside class="w-full md:w-60 py-4 md:py-8 md:sticky md:top-20 md:h-[calc(100vh-80px)] flex flex-col border-b md:border-b-0 md:border-r border-black/[0.08]">

                <!-- User info -->
                <div class="flex items-center gap-3 px-2 mb-4 md:mb-8">
                    <img src="{{ $user['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) }}"
                         alt="{{ $user['name'] }}"
                         class="w-10 h-10 rounded-full border border-black/10 flex-shrink-0">
                    <div class="flex flex-col overflow-hidden">
                        <span class="font-semibold text-sm text-slate-950 truncate">{{ $user['name'] }}</span>
                        <span class="text-xs text-slate-500 truncate">{{ $user['email'] }}</span>
                    </div>
                </div>

                <nav role="tablist" aria-label="{{ __('Dashboard') }}"
                     class="flex flex-col gap-0.5 flex-1">
                    @php
                        $navItems = [
                            ['tab' => 'overview',       'icon' => 'layout-dashboard', 'label' => __('Overview')],
                            ['tab' => 'subscriptions',  'icon' => 'layers',            'label' => __('Subscriptions')],
                            ['tab' => 'orders',         'icon' => 'file-text',         'label' => __('Orders')],
                            ['tab' => 'settings',       'icon' => 'settings',          'label' => __('Settings')],
                        ];
                    @endphp
                    @foreach($navItems as $item)
                    <button id="tab-{{ $item['tab'] }}"
                            @click="go('{{ $item['tab'] }}')"
                            role="tab"
                            :aria-selected="activeTab === '{{ $item['tab'] }}' ? 'true' : 'false'"
                            aria-controls="panel-{{ $item['tab'] }}"
                            :class="activeTab === '{{ $item['tab'] }}'
                                ? 'bg-orange-50 text-slate-950 border-l-2 border-primary-500'
                                : 'text-slate-600 hover:text-slate-950 hover:bg-orange-50 border-l-2 border-transparent'"
                            class="group flex items-center gap-2.5 px-4 py-2.5 w-full rounded-r-xl text-sm font-normal transition-all text-left cursor-pointer">
                        <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 flex-shrink-0 opacity-70 group-hover:opacity-100 transition-opacity"></i>
                        <span>{{ $item['label'] }}</span>
                    </button>
                    @endforeach

                    <div class="mt-auto pt-4 border-t border-black/[0.08]">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
                            <button type="submit"
                                    class="group flex items-center gap-2.5 px-4 py-2.5 w-full rounded-xl text-sm font-normal text-slate-500 hover:text-red-400 hover:bg-orange-50 transition-all text-left cursor-pointer">
                                <i data-lucide="log-out" class="w-4 h-4 flex-shrink-0 opacity-60 group-hover:opacity-100 transition-opacity"></i>
                                <span>{{ __('Logout') }}</span>
                            </button>
                        </form>
                    </div>
                </nav>
            </aside>

            <!-- ============================================================
                 Main Content
                 ============================================================ -->
            <main class="flex-1 py-10 overflow-hidden min-w-0">

                @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex items-center gap-3">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    {{ session('success') }}
                </div>
                @endif

                <!-- ── Overview ── -->
                <div id="panel-overview" role="tabpanel" aria-labelledby="tab-overview" x-show="activeTab === 'overview'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-8">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-950 tracking-tight mb-1">{{ __('Welcome back, :name!', ['name' => $user['name']]) }}</h2>
                        <p class="text-xs text-slate-500">{{ __('Here\'s an overview of your account and products.') }}</p>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="ui-card p-6 rounded-2xl flex items-center gap-4">
                            <div class="w-11 h-11 rounded-xl bg-primary-500/10 border border-primary-500/20 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="package" class="w-5 h-5 text-primary-400"></i>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-slate-950 mb-0.5">{{ $subscriptions->count() }}</div>
                                <div class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold">{{ __('Subscriptions') }}</div>
                            </div>
                        </div>
                        <div class="ui-card p-6 rounded-2xl flex items-center gap-4">
                            <div class="w-11 h-11 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="zap" class="w-5 h-5 text-amber-400"></i>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-slate-950 mb-0.5">{{ $subscriptions->filter(fn($s) => $s->plan && in_array($s->plan->tier, ['pro', 'enterprise']))->count() }}</div>
                                <div class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold">{{ __('Paid Plans') }}</div>
                            </div>
                        </div>
                        <div class="ui-card p-6 rounded-2xl flex items-center gap-4">
                            <div class="w-11 h-11 rounded-xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="credit-card" class="w-5 h-5 text-cyan-400"></i>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-slate-950 mb-0.5">{{ $orders->count() }}</div>
                                <div class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold">{{ __('Orders') }}</div>
                            </div>
                        </div>
                    </div>

                    @if($subscriptions->isNotEmpty())
                    @php
                        $primarySubscription = $subscriptions->first();
                        $primaryPlan = $primarySubscription?->plan;
                        $primaryProduct = $primaryPlan?->product;
                    @endphp
                    <div class="ui-card p-6 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-5">
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-slate-950 mb-1">{{ __('Subscription Snapshot') }}</h3>
                            <p class="text-xs text-slate-500">
                                {{ $primaryProduct?->name ?? __('Active product') }} · {{ $primaryPlan?->name ?? __('Subscription') }}
                            </p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:min-w-[520px]">
                            <div>
                                <div class="section-label mb-1">{{ __('Status') }}</div>
                                <span class="ui-badge-success">{{ __(ucfirst($primarySubscription->stripe_status)) }}</span>
                            </div>
                            <div>
                                <div class="section-label mb-1">{{ __('Billing') }}</div>
                                <div class="text-sm text-slate-200">{{ ucfirst($primaryPlan?->billing_cycle ?? 'monthly') }}</div>
                            </div>
                            <div>
                                <div class="section-label mb-1">{{ __('Next Step') }}</div>
                                @if($primarySubscription->trial_ends_at && $primarySubscription->trial_ends_at->isFuture())
                                    <div class="text-sm text-blue-300">{{ __('Trial ends :date', ['date' => $primarySubscription->trial_ends_at->format('M d, Y')]) }}</div>
                                @elseif($primarySubscription->ends_at && $primarySubscription->ends_at->isFuture())
                                    <div class="text-sm text-amber-300">{{ __('Access ends :date', ['date' => $primarySubscription->ends_at->format('M d, Y')]) }}</div>
                                @elseif($primarySubscription->current_period_ends_at && $primarySubscription->current_period_ends_at->isFuture())
                                    <div class="text-sm text-emerald-300">{{ __('Renews :date', ['date' => $primarySubscription->current_period_ends_at->format('M d, Y')]) }}</div>
                                @else
                                    <div class="text-sm text-emerald-300">{{ __('Active recurring') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Quick Actions -->
                    <div class="space-y-3">
                        <h3 class="text-[11px] font-semibold text-slate-500 uppercase tracking-[0.15em]">{{ __('Quick Actions') }}</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <a href="{{ localized_route('products.index') }}"
                               class="ui-card ui-card-lift p-5 rounded-2xl flex flex-col items-center gap-3 text-slate-700 hover:text-slate-950 no-underline text-sm font-normal cursor-pointer text-center">
                                <i data-lucide="shopping-bag" class="w-5 h-5 text-slate-600"></i>
                                <span>{{ __('Browse Products') }}</span>
                            </a>
                            <button @click="go('subscriptions')"
                                    class="ui-card ui-card-lift p-5 rounded-2xl flex flex-col items-center gap-3 text-slate-700 hover:text-slate-950 text-sm font-normal cursor-pointer text-center border-0">
                                <i data-lucide="layers" class="w-5 h-5 text-slate-600"></i>
                                <span>{{ __('Manage Subscriptions') }}</span>
                            </button>
                            <a href="{{ localized_route('support') }}"
                               class="ui-card ui-card-lift p-5 rounded-2xl flex flex-col items-center gap-3 text-slate-700 hover:text-slate-950 no-underline text-sm font-normal cursor-pointer text-center">
                                <i data-lucide="life-buoy" class="w-5 h-5 text-slate-600"></i>
                                <span>{{ __('Contact Support') }}</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- ── Orders ── -->
                <div id="panel-orders" role="tabpanel" aria-labelledby="tab-orders" x-show="activeTab === 'orders'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-950 tracking-tight mb-1">{{ __('Order History') }}</h2>
                        <p class="text-xs text-slate-500">{{ __('Recent transactions and billing history.') }}</p>
                    </div>

                    @if($orders->isEmpty())
                    <div class="p-14 text-center rounded-2xl ui-card border-dashed">
                        <div class="w-14 h-14 rounded-2xl bg-orange-50 border border-black/[0.08] flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="file-text" class="w-6 h-6 text-slate-500"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-950 mb-2">{{ __('No orders found') }}</h3>
                        <p class="text-sm text-slate-500">{{ __('Your order history will appear here after purchase.') }}</p>
                    </div>
                    @else
                    <div class="overflow-x-auto rounded-2xl border border-black/[0.08] bg-white scrollbar-thin">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr class="border-b border-black/[0.08]">
                                    <th class="px-5 py-3.5 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Order') }}</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Plan') }}</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Amount') }}</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Date') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/[0.04]">
                                @foreach($orders as $order)
                                <tr class="hover:bg-orange-50 transition-colors">
                                    <td class="px-5 py-4 font-mono text-xs text-primary-400">#{{ substr($order->order_number, -8) }}</td>
                                    <td class="px-5 py-4 text-slate-700 text-sm">{{ $order->plan->name ?? 'N/A' }}</td>
                                    <td class="px-5 py-4 font-bold text-slate-950 text-sm">{{ strtoupper($order->currency) }} {{ number_format($order->total, 2) }}</td>
                                    <td class="px-5 py-4">
                                        <span class="ui-badge-success">{{ __($order->status->value) }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-slate-500 text-xs">{{ $order->created_at->format('M d, Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>

                <!-- ── Subscriptions ── -->
                <div id="panel-subscriptions" role="tabpanel" aria-labelledby="tab-subscriptions" x-show="activeTab === 'subscriptions'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-950 tracking-tight mb-1">{{ __('Your Subscriptions') }}</h2>
                        <p class="text-xs text-slate-500">{{ __('Manage your active product subscriptions.') }}</p>
                    </div>

                    @if($subscriptions->isEmpty())
                    <div class="p-14 text-center rounded-2xl ui-card border-dashed">
                        <div class="w-14 h-14 rounded-2xl bg-orange-50 border border-black/[0.08] flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="layers" class="w-6 h-6 text-slate-500"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-950 mb-2">{{ __('No active subscriptions') }}</h3>
                        <p class="text-sm text-slate-500 mb-6">{{ __('You don\'t have any active subscriptions yet.') }}</p>
                        <a href="{{ localized_route('products.index') }}" class="btn-brand">
                            {{ __('Explore Products') }}
                        </a>
                    </div>
                    @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        @foreach($subscriptions as $subscription)
                        @php
                            $plan  = $subscription->plan;
                            $product = $plan?->product;
                            $statusColors = ['active' => 'success', 'trialing' => 'info', 'past_due' => 'warning', 'canceled' => 'neutral'];
                            $statusBadge  = $statusColors[$subscription->stripe_status] ?? 'neutral';
                        @endphp
                        <div class="ui-card ui-card-lift p-6 rounded-2xl">
                            <div class="flex items-start gap-4 mb-5">
                                @if($product?->image)
                                <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" class="w-14 h-14 rounded-xl object-cover border border-black/[0.08] flex-shrink-0">
                                @else
                                <div class="w-14 h-14 rounded-xl bg-primary-500/10 border border-primary-500/20 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="package" class="w-6 h-6 text-primary-400"></i>
                                </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-bold text-slate-950 truncate mb-0.5">{{ $product?->name ?? $subscription->type }}</h3>
                                    <p class="text-xs text-slate-500 truncate">{{ $plan?->name ?? 'Subscription' }}</p>
                                </div>
                                <span class="ui-badge-{{ $statusBadge }} shrink-0">
                                    {{ __(ucfirst($subscription->stripe_status)) }}
                                </span>
                            </div>

                            <div class="space-y-2.5 py-4 border-y border-black/[0.08] mb-5">
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">{{ __('Billing') }}</span>
                                    <span class="text-slate-700">{{ ucfirst($plan?->billing_cycle ?? 'monthly') }}</span>
                                </div>
                                @if($plan?->price)
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">{{ __('Price') }}</span>
                                    <span class="text-slate-950 font-bold">{{ strtoupper($plan->currency ?? 'USD') }} {{ number_format($plan->price, 2) }}</span>
                                </div>
                                @endif
                                @if($subscription->trial_ends_at && $subscription->trial_ends_at->isFuture())
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">{{ __('Trial Ends') }}</span>
                                    <span class="text-blue-400">{{ $subscription->trial_ends_at->format('M d, Y') }}</span>
                                </div>
                                @endif
                                @if($subscription->ends_at)
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">{{ __('Access Ends') }}</span>
                                    <span class="text-amber-400">{{ $subscription->ends_at->format('M d, Y') }}</span>
                                </div>
                                @elseif($subscription->current_period_ends_at)
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">{{ __('Renews On') }}</span>
                                    <span class="text-emerald-400">{{ $subscription->current_period_ends_at->format('M d, Y') }}</span>
                                </div>
                                @endif
                            </div>

                            <div class="flex gap-2.5">
                                @if($product)
                                <a href="{{ localized_route('checkout.portal', ['product' => $product->code]) }}"
                                   class="flex-1 text-center py-2 rounded-xl border border-black/[0.10] text-xs font-semibold text-slate-600 hover:text-slate-950 hover:bg-orange-50 transition-all">
                                    {{ __('Manage') }}
                                </a>
                                <a href="{{ $product->publicUrl(app()->getLocale()) }}"
                                   class="flex-1 text-center py-2 rounded-xl bg-primary-600/15 border border-primary-500/20 text-xs font-semibold text-primary-400 hover:bg-primary-600/25 transition-all">
                                    {{ __('View Product') }}
                                </a>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <!-- ── Settings ── -->
                <div id="panel-settings" role="tabpanel" aria-labelledby="tab-settings" x-show="activeTab === 'settings'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-10">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-950 tracking-tight mb-1">{{ __('Account Settings') }}</h2>
                        <p class="text-xs text-slate-500">{{ __('View your profile details and connected sign-in methods.') }}</p>
                    </div>

                    <div class="space-y-8">
                        <!-- Basic Profile -->
                        <section class="space-y-4">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-slate-950">
                                <span class="w-1 h-5 bg-primary-500 rounded-full"></span>
                                {{ __('Profile Details') }}
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="ui-card p-4 rounded-xl flex flex-col gap-1">
                                    <span class="section-label">{{ __('Full Name') }}</span>
                                    <span class="text-slate-200 text-sm">{{ $user->name }}</span>
                                </div>
                                <div class="ui-card p-4 rounded-xl flex flex-col gap-1">
                                    <span class="section-label">{{ __('Email Address') }}</span>
                                    <span class="text-slate-200 text-sm">{{ $user->email }}</span>
                                </div>
                            </div>
                        </section>

                        <!-- Connected Accounts -->
                        <section class="space-y-4">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-slate-950">
                                <span class="w-1 h-5 bg-blue-500 rounded-full"></span>
                                {{ __('Connected Accounts') }}
                            </h3>
                            <div class="space-y-2">
                                @forelse($oauthAccounts as $account)
                                <div class="flex items-center justify-between p-4 rounded-xl ui-card">
                                    <div class="flex items-center gap-3">
                                        @if($account->provider === 'google')
                                        <svg viewBox="0 0 24 24" width="18" height="18">
                                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                        </svg>
                                        @endif
                                        <span class="text-sm font-semibold text-slate-700">{{ ucfirst($account->provider) }}</span>
                                    </div>
                                    <span class="ui-badge-success">
                                        <i data-lucide="check" class="w-3 h-3"></i>
                                        {{ __('Linked') }}
                                    </span>
                                </div>
                                @empty
                                <p class="text-slate-500 text-sm">{{ __('No external accounts linked.') }}</p>
                                @endforelse
                            </div>
                        </section>

                        <!-- Danger Zone -->
                        <section class="space-y-4 pt-8 border-t border-black/[0.08]">
                            <h3 class="text-sm font-bold text-red-500">{{ __('Danger Zone') }}</h3>
                            <div class="p-5 rounded-xl bg-red-500/[0.04] border border-red-500/[0.12] flex justify-between items-center gap-6">
                                <div>
                                    <h4 class="font-bold text-slate-200 text-sm mb-1">{{ __('Account Deletion') }}</h4>
                                    <p class="text-xs text-slate-500">{{ __('Account deletion is handled by support so we can verify ownership and explain the impact on subscriptions and invoices.') }}</p>
                                </div>
                                <a href="{{ localized_route('support') }}"
                                   class="px-5 py-2 rounded-xl bg-red-500/10 text-red-300 hover:text-red-100 font-semibold text-sm border border-red-500/20 hover:bg-red-500/15 transition-colors whitespace-nowrap no-underline">
                                    {{ __('Contact Support') }}
                                </a>
                            </div>
                        </section>
                    </div>
                </div>

            </main>
        </div>
    </div>
</div>
@endsection
