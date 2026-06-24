@props([
    'guides',
    'productCode',
])

@php
    $guideCount = $guides?->count() ?? 0;
@endphp

@if($guideCount > 0)
<section {{ $attributes->merge(['class' => 'mt-12 border-t border-white/[0.08] pt-8']) }}>
    <div class="flex flex-wrap items-end justify-between gap-4 mb-5">
        <div>
            <p class="section-label mb-2">{{ __('products.guides_label') }}</p>
            <h2 class="text-xl md:text-2xl font-semibold text-white tracking-tight">
                {{ __('products.latest_guides') }}
            </h2>
        </div>

        <a href="{{ localized_route('catalog.guides.index', ['productCode' => $productCode]) }}"
           class="btn-ghost px-4 py-2 text-sm rounded-lg group inline-flex items-center gap-2 shrink-0">
            {{ __('products.all_guides') }}
            <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-0.5 transition-transform"></i>
        </a>
    </div>

    <div class="divide-y divide-white/[0.06] border-y border-white/[0.06]">
        @foreach($guides as $guide)
            @php
                $guideTitle = $guide->getTranslation('title', app()->getLocale());
                $guideExcerpt = $guide->getTranslation('excerpt', app()->getLocale());
                $guideUrl = localized_route('catalog.guides.show', ['productCode' => $productCode, 'slug' => $guide->slug]);
            @endphp

            <a href="{{ $guideUrl }}" class="group grid gap-3 py-5 md:grid-cols-[1fr_auto] md:items-center">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-2 text-xs text-slate-500">
                        <span class="ui-badge-brand">{{ $guide->type }}</span>
                        @if($guide->published_at)
                            <span>{{ $guide->published_at->format('M d, Y') }}</span>
                        @endif
                        <span>{{ $guide->getReadingTime() }} min read</span>
                    </div>

                    <h3 class="text-base md:text-lg font-semibold text-slate-100 group-hover:text-[var(--brand-primary)] transition-colors line-clamp-2">
                        {{ $guideTitle }}
                    </h3>

                    @if($guideExcerpt)
                        <p class="mt-2 text-sm text-slate-500 leading-6 line-clamp-2 max-w-3xl">
                            {{ $guideExcerpt }}
                        </p>
                    @endif
                </div>

                <span class="inline-flex items-center gap-2 text-sm font-semibold text-slate-400 group-hover:text-[var(--brand-primary)] transition-colors md:justify-self-end">
                    {{ __('products.read_guide') }}
                    <i data-lucide="arrow-up-right" class="w-4 h-4 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
                </span>
            </a>
        @endforeach
    </div>
</section>
@endif
