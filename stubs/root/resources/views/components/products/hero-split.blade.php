{{--
    Hero Split Section Component (Laravel 12 Anonymous Component)

    Usage: <x-products.hero-split :hero="$heroData" />

    @props array $hero Configuration with:
        - product_type: 产品类型
        - theme_profile: 可选主题标识（优先于 product_type）
        - tag/tag_key, title/title_key
        - subtitle/subtitle_key
        - release_status
        - product_tags
        - metadata
        - stats
        - ctas
        - has_paid_plans
        - code
        - description
--}}

@props(['hero'])

@php
    $productType = $hero['product_type'] ?? 'web';
    $themeProfile = $hero['theme_profile'] ?? $productType;
    $releaseStatus = $hero['release_status'] ?? 'stable';
    $productTags = $hero['product_tags'] ?? [];
    $heroStats = $hero['stats'] ?? [];

    $statusColors = [
        'alpha' => 'amber',
        'beta' => 'blue',
        'stable' => 'emerald',
    ];
    $statusColor = $statusColors[$releaseStatus] ?? 'emerald';

    $title = $hero['title'] ?? (isset($hero['title_key']) ? __($hero['title_key']) : '');
    $subtitle = $hero['subtitle'] ?? (isset($hero['subtitle_key']) ? __($hero['subtitle_key']) : '');

    $ctas = $hero['ctas'] ?? [];
    $hasPricingCta = false;
    foreach ($ctas as $cta) {
        if (($cta['type'] ?? '') === 'pricing') {
            $hasPricingCta = true;
            break;
        }
    }

    if (! $hasPricingCta && isset($hero['code']) && ($hero['has_paid_plans'] ?? false)) {
        $ctas[] = [
            'type' => 'pricing',
            'slug' => $hero['code'],
            'color' => 'amber',
        ];
    }
@endphp

<div {{ $attributes->merge(['class' => "product-hero-split relative py-10 overflow-hidden type-{$themeProfile}"]) }}>
    <div class="absolute top-0 left-0 w-full h-full pointer-events-none opacity-20">
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-[var(--brand-primary)] rounded-full blur-[120px]"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
            <div class="lg:col-span-5 order-2 lg:order-1 relative" x-data="{ lightbox: false }">
                @if(isset($hero['preview_image']))
                    @php
                        // Image aspect handling. Default keeps the square crop used by
                        // existing products; 'wide'/'auto' show landscape banners in full
                        // (natural ratio, no crop) so nothing gets clipped.
                        $previewAspect = $hero['preview_aspect'] ?? 'square';
                        $previewImgClass = match ($previewAspect) {
                            'wide', 'auto', 'landscape' => 'w-full h-auto object-contain',
                            'video' => 'w-full h-auto object-cover aspect-video',
                            default => 'w-full h-auto object-cover aspect-[4/3] lg:aspect-square',
                        };
                        $previewHasOverlay = ! in_array($previewAspect, ['wide', 'auto', 'landscape'], true);
                    @endphp
                    <div class="relative z-10 group">
                        <div class="absolute -top-12 -bottom-24 -left-12 -right-12 bg-[var(--brand-glow)] blur-[160px] rounded-full pointer-events-none transition-colors duration-1000"></div>
                        <div class="absolute -inset-1 bg-[var(--brand-primary)] opacity-25 group-hover:opacity-40 blur rounded-2xl transition duration-1000"></div>
                        <div class="relative rounded-2xl border border-black/10 overflow-hidden bg-white shadow-2xl shadow-orange-900/10 transition-transform duration-500 hover:scale-[1.01] cursor-zoom-in"
                             @click="lightbox = true"
                             role="button"
                             tabindex="0"
                             @keydown.enter="lightbox = true"
                             aria-label="{{ $title }} — click to enlarge">
                            <x-products.responsive-image
                                :src="$hero['preview_image']"
                                :alt="$title"
                                picture-class=""
                                :img-class="$previewImgClass"
                            />
                            @if($previewHasOverlay)
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/40 to-transparent"></div>
                            @endif
                            {{-- Zoom affordance --}}
                            <div class="absolute top-3 right-3 z-10 flex h-8 w-8 items-center justify-center rounded-lg bg-black/40 text-white/80 opacity-0 backdrop-blur-sm transition-opacity duration-200 group-hover:opacity-100">
                                <i data-lucide="maximize-2" class="h-4 w-4"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Lightbox (teleported to body so it overlays the whole viewport) --}}
                    <template x-teleport="body">
                        <div x-show="lightbox"
                             x-cloak
                             @keydown.escape.window="lightbox = false"
                             x-transition.opacity.duration.200ms
                             class="fixed inset-0 z-[100] flex items-center justify-center bg-black/85 p-4 backdrop-blur-sm md:p-10">
                            {{-- Backdrop: click blank space to close --}}
                            <div class="absolute inset-0" @click="lightbox = false"></div>

                            {{-- Close button (top-right) --}}
                            <button type="button"
                                    @click="lightbox = false"
                                    aria-label="Close preview"
                                    class="absolute top-4 right-4 z-10 flex h-11 w-11 items-center justify-center rounded-full border border-white/15 bg-white/10 text-white transition hover:bg-white/20">
                                <i data-lucide="x" class="h-5 w-5"></i>
                            </button>

                            {{-- Enlarged image (clicking the image itself does not close) --}}
                            <img src="{{ asset($hero['preview_image']) }}"
                                 alt="{{ $title }}"
                                 @click.stop
                                 class="relative z-[1] max-h-full max-w-full rounded-xl border border-white/10 object-contain shadow-2xl">
                        </div>
                    </template>
                @else
                    <div class="aspect-square rounded-3xl bg-white border border-black/10 flex items-center justify-center relative shadow-xl shadow-orange-900/5">
                         <div class="absolute inset-0 bg-[var(--brand-glow)] blur-[100px]"></div>
                        <div class="w-24 h-24 rounded-2xl bg-[var(--brand-muted)] flex items-center justify-center border border-[var(--brand-border)] relative z-10">
                             <i data-lucide="{{ $hero['icon'] ?? 'box' }}" class="w-12 h-12 text-[var(--brand-primary)]"></i>
                         </div>
                    </div>
                @endif
            </div>

            <div class="lg:col-span-7 order-1 lg:order-2 space-y-6 pt-4 lg:pt-1">
                <div class="flex flex-wrap items-center gap-2">
                    @if($productType)
                    <span class="px-2.5 py-1 rounded-lg bg-white border border-black/10 text-slate-600 text-xs font-bold leading-none">
                        {{ __("products.product_type.{$productType}") }}
                    </span>
                    @endif

                    <span class="px-2.5 py-1 rounded-lg bg-orange-100 border border-orange-200 text-[#a34f1f] text-xs font-bold leading-none">
                        {{ __("products.release_status.{$releaseStatus}") }}
                    </span>

                    <div class="h-4 w-px bg-black/10 mx-1"></div>

                    @foreach($productTags as $tag)
                    <span class="px-2.5 py-1 rounded-lg bg-white border border-black/10 text-slate-600 text-xs font-bold transition-colors hover:bg-orange-50">
                        {{ $tag }}
                    </span>
                    @endforeach
                </div>

                <h1 class="text-2xl md:text-5xl font-bold text-slate-950 tracking-tight leading-tight">
                    {{ $title }}
                </h1>

                <div class="max-w-xl space-y-4">
                    <p class="text-base md:text-lg text-slate-600 leading-relaxed font-normal">
                        {{ $subtitle }}
                    </p>
                    @if($hero['description'] ?? null)
                    <p class="text-base text-slate-600 leading-relaxed">
                        {!! nl2br(e($hero['description'])) !!}
                    </p>
                    @endif
                </div>

                @if(count($ctas) > 0)
                <div class="flex flex-wrap gap-4 pt-4">
                    @foreach($ctas as $cta)
                        <x-products.cta-button
                            :cta="$cta"
                            :product-code="$hero['code'] ?? null"
                            :has-paid-plans="(bool) ($hero['has_paid_plans'] ?? false)"
                        />
                    @endforeach
                </div>
                @endif

                @if(! empty($heroStats))
                <div class="flex items-center gap-8 pt-6 border-t border-black/10">
                     @if(isset($heroStats['npm_downloads']))
                     <div>
                         <div class="text-xs text-slate-500 uppercase font-bold tracking-widest mb-1">NPM Downloads</div>
                         <div class="text-slate-950 font-bold">{{ number_format($heroStats['npm_downloads']) }}</div>
                     </div>
                     @endif

                     @if(isset($heroStats['vscode_downloads']))
                     <div>
                         <div class="text-xs text-slate-500 uppercase font-bold tracking-widest mb-1">IDE Installs</div>
                         <div class="text-slate-950 font-bold">{{ number_format($heroStats['vscode_downloads']) }}</div>
                     </div>
                     @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
