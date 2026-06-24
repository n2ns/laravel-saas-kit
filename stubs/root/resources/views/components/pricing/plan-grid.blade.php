@props([
    'product',
    'plans',
])

@php
    $gridColsClass = match($plans->count()) {
        1 => 'lg:grid-cols-1 max-w-md mx-auto',
        2 => 'lg:grid-cols-2 max-w-3xl mx-auto',
        4 => 'lg:grid-cols-2 xl:grid-cols-4',
        default => 'lg:grid-cols-3',
    };
@endphp

<div class="grid grid-cols-1 {{ $gridColsClass }} gap-6 items-stretch">
    @foreach($plans as $plan)
        @php
            $config = $plan->content_payload ?? [];
            $isPaid = ($plan->price > 0);
            $isHighlight = ($plan->tier === \App\Models\Plan::TIER_PRO);
            $useBrand = $isPaid;

            $locale = app()->getLocale();
            $badgeText = $config['badge_text'][$locale] ?? $config['badge_text']['en'] ?? null;
            $featuresList = $config['features_list'][$locale] ?? $config['features_list']['en'] ?? [];
            $ctaText = $config['cta_text'][$locale] ?? $config['cta_text']['en'] ?? ($plan->price > 0 ? __('checkout.subscribe_now') : __('products.install'));

            if ($isHighlight) {
                $wrapperClass = 'relative z-10 scale-[1.02]';
                $cardClass = 'ui-card flex flex-col h-full p-8 rounded-3xl border-2 border-[var(--tier-pro)] shadow-[0_0_40px_var(--tier-pro-glow)] bg-slate-800/60 backdrop-blur-xl';
                $titleClass = 'text-lg font-bold text-[var(--tier-pro)]';
                $badgeClass = 'px-2 py-0.5 rounded-md text-[11px] font-semibold bg-[var(--tier-pro-muted)] text-[var(--tier-pro)] border border-[var(--tier-pro-muted)]';
                $mainBtnClass = 'w-full py-3.5 rounded-xl bg-[var(--tier-pro)] hover:opacity-90 text-white font-bold text-sm transition-all text-center block shadow-lg shadow-[var(--tier-pro-glow)] hover:scale-[1.02]';
                $brandColorVar = 'var(--tier-pro)';
                $useBrand = true;
            } elseif ($useBrand) {
                $wrapperClass = '';
                $cardClass = 'ui-card flex flex-col h-full p-8 rounded-3xl border-[var(--brand-border)] bg-slate-800/40';
                $titleClass = 'text-lg font-bold text-[var(--brand-primary)]';
                $badgeClass = 'px-2 py-0.5 rounded-md text-[11px] font-semibold bg-[var(--brand-muted)] text-[var(--brand-primary)] border border-[var(--brand-border)]';
                $mainBtnClass = 'w-full py-3.5 rounded-xl bg-[var(--brand-primary)] hover:opacity-90 text-white font-bold text-sm transition-all text-center block hover:scale-[1.02]';
                $brandColorVar = 'var(--brand-primary)';
            } else {
                $wrapperClass = '';
                $cardClass = 'ui-card flex flex-col h-full p-8 rounded-3xl bg-white/[0.02]';
                $titleClass = 'text-lg font-bold text-slate-300';
                $badgeClass = '';
                $mainBtnClass = 'w-full py-3.5 rounded-xl bg-white/5 hover:bg-white/10 text-white border border-white/10 font-bold text-sm transition-all text-center block hover:scale-[1.02]';
                $brandColorVar = 'rgba(148,163,184,0.6)';
            }

            $ctaParams = ['product' => $product->code, 'plan' => $plan->code];
            if (request()->has('lang')) {
                $ctaParams['lang'] = request()->query('lang');
            }
            $ctaUrl = $config['cta_url'] ?? localized_route('checkout.create', $ctaParams);
            if ($plan->isFree() && ! isset($config['cta_url'])) {
                $ctaUrl = Auth::check() ? localized_route('dashboard') : localized_route('login');
            }
            if ($plan->tier === \App\Models\Plan::TIER_ENTERPRISE) {
                $ctaUrl = localized_route('support');
            }
            $target = (isset($config['cta_url']) || $plan->tier === \App\Models\Plan::TIER_ENTERPRISE) ? '_blank' : '_self';

            if (Auth::check() && Auth::user()->hasActiveSubscription($product->code) && ! $plan->isFree() && ! $plan->isCreditPack()) {
                $ctaUrl = localized_route('checkout.portal', ['product' => $product->code]);
                $ctaText = __('checkout.manage_subscription');
                $mainBtnClass = 'w-full py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm transition-all text-center block';
                $target = '_self';
            }
        @endphp

        <div class="{{ $wrapperClass }}">
            <div class="{{ $cardClass }}">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="{{ $titleClass }}">{{ $plan->name }}</h3>
                    @if($badgeText)
                        <span class="{{ $badgeClass }}">{{ $badgeText }}</span>
                    @endif
                </div>

                <div class="mb-6">
                    <div class="flex items-baseline gap-1">
                        <span class="text-2xl md:text-4xl font-bold text-white tracking-tight">
                            {{ $plan->price > 0 ? '$' . number_format($plan->price, 2) : '$0' }}
                        </span>
                        @if($plan->price > 0)
                            <span class="text-slate-400 text-sm ml-1">/ {{ $plan->billing_cycle }}</span>
                        @endif
                    </div>
                    <p class="mt-3 text-sm leading-relaxed {{ $useBrand ? '' : 'text-slate-400' }}"
                       style="{{ $useBrand ? "color: $brandColorVar; opacity: 0.85;" : "" }}">
                        {{ $plan->description }}
                    </p>
                </div>

                <div class="w-full h-px mb-7" style="background: {{ $useBrand ? $brandColorVar : 'rgba(255,255,255,0.08)' }}; opacity: {{ $useBrand ? '0.25' : '1' }}"></div>

                <ul class="space-y-3.5 mb-8 flex-1">
                    @foreach($featuresList as $index => $feature)
                        <li class="flex items-start gap-3">
                            <svg class="w-4 h-4 shrink-0 mt-0.5" style="color: {{ $useBrand ? $brandColorVar : 'rgba(100,116,139,0.8)' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                @if($index === 0 && $isHighlight)
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                @endif
                            </svg>
                            <span class="text-sm text-slate-300 leading-snug {{ ($index === 0 && $useBrand) ? 'text-white font-semibold' : '' }}">
                                {{ $feature }}
                            </span>
                        </li>
                    @endforeach
                </ul>

                <a href="{{ $ctaUrl }}" target="{{ $target }}"
                   @if($target === '_blank') rel="noopener noreferrer" @endif
                   class="{{ $mainBtnClass }} mt-auto">
                    {{ $ctaText }}
                </a>
            </div>
        </div>
    @endforeach
</div>
