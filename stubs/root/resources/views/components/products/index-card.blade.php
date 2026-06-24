{{--
    Product card for products index page.

    Usage:
      <x-products.index-card :product="$product" />
--}}

@props([
    'product' => [],
])

@php
    $title = $product['title'] ?? '';
    $description = $product['description'] ?? '';
    $tag = $product['tag'] ?? '';
    $image = $product['image'] ?? '';
    $link = $product['link'] ?? '#';
    $pricingLink = $product['pricing_link'] ?? null;
    $isExternal = (bool) ($product['is_external'] ?? false);

    $itemCategory = $product['display_category'] ?? $product['segment'] ?? 'application-product';
    $isProduct = (bool) ($product['is_product'] ?? false);
    $isDeveloperTool = (bool) ($product['is_developer_tool'] ?? false);
    $isConcept = (bool) ($product['is_concept'] ?? false);
    $githubUrl = $product['github_url'] ?? null;
@endphp

<div x-show="!category || category === '{{ $itemCategory }}'"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     class="group ui-card ui-card-lift flex flex-col h-full overflow-hidden">

    <!-- Card Image -->
    <a href="{{ $link }}" @if($isExternal) target="_blank" rel="noopener noreferrer" @endif class="block h-[180px] relative overflow-hidden bg-bg-surface flex-shrink-0">
        <x-products.responsive-image
            :src="$image"
            :alt="$title"
            img-class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-bg-body to-transparent opacity-50"></div>
    </a>

    <div class="p-5 flex flex-col flex-grow">
        <!-- Tags row -->
        <div class="flex flex-wrap justify-between items-center mb-3 gap-2">
            <div class="flex flex-wrap items-center gap-1.5">
                @if($isProduct)
                    <span class="ui-badge-warning">{{ __('products.badge_application_product') }}</span>
                @elseif($isConcept)
                    <span class="ui-badge-cyan">{{ __('products.badge_concept') }}</span>
                @elseif($isDeveloperTool)
                    <span class="ui-badge-success">{{ __('products.badge_developer_tool') }}</span>
                @else
                    <span class="ui-badge-neutral">{{ __('products.badge_project') }}</span>
                @endif
                @if($tag)
                    <span class="ui-badge-neutral">{{ $tag }}</span>
                @endif
            </div>
        </div>

        <!-- Title -->
        <h3 class="text-[15px] font-bold text-white mb-2 tracking-tight">
            <a href="{{ $link }}" class="hover:text-primary-400 transition-colors">{{ $title }}</a>
        </h3>

        <!-- Description -->
        <p class="text-sm text-slate-400 mb-5 flex-grow leading-relaxed line-clamp-2">
            {{ $description }}
        </p>

        <!-- Actions -->
        <div class="flex justify-between items-center gap-3 mt-auto pt-4 border-t border-white/[0.06]">
            @if($link !== '#')
                <a href="{{ $link }}" @if($isExternal) target="_blank" rel="noopener noreferrer" @endif
                   class="text-xs font-semibold text-slate-400 hover:text-white transition-colors flex items-center gap-1.5 group/btn">
                    {{ __('products.view_details') }}
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover/btn:translate-x-0.5 transition-transform"></i>
                </a>
            @else
                <span class="text-xs font-semibold text-slate-500">{{ __('products.badge_concept') }}</span>
            @endif

            @if($isProduct && $pricingLink)
                <a href="{{ $pricingLink }}"
                   class="ui-badge-warning px-3 py-1.5 rounded-lg hover:bg-amber-500 hover:text-white transition-all text-xs font-bold whitespace-nowrap">
                    {{ __('products.pricing.title') }}
                </a>
            @elseif($githubUrl)
                <a href="{{ $githubUrl }}" target="_blank" rel="noopener noreferrer"
                   class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-slate-400 hover:bg-white hover:text-slate-950 transition-all duration-200 group/gh"
                   title="View on GitHub">
                    <svg class="w-4 h-4 group-hover/gh:scale-110 transition-transform" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.603-3.369-1.34-3.369-1.34-.454-1.156-1.11-1.464-1.11-1.464-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.579.688.481C19.137 20.162 22 16.418 22 12c0-5.523-4.477-10-10-10z"/>
                    </svg>
                </a>
            @endif
        </div>
    </div>
</div>
