{{--
    Sub-Products Section Component (Laravel 12 Anonymous Component)

    Usage: <x-products.sub-products :section="$section" />

    @props array $section Configuration array with:
        - items: Array of sub-product configurations
            - layout: 'horizontal' or 'overlay'
            - color: Theme color
            - icon_type: Icon type (vscode, terminal)
            - name_key: Translation key for name
            - desc_key: Translation key for description
            - image: Image path (for horizontal layout)
            - bg_image: Background image path (for overlay layout)
            - cta: CTA button configuration
--}}

@props(['section'])

@php
    $items = $section['items'] ?? [];
@endphp

<div {{ $attributes->merge(['class' => 'pb-32 space-y-24']) }}>
    @foreach($items as $item)
        @php
            $layout = $item['layout'] ?? 'horizontal';
            $cta = null;
            if (isset($item['cta']) && is_array($item['cta'])) {
                $cta = $item['cta'];
                if (! isset($cta['type'])) {
                    $cta['type'] = 'external';
                }
            }
        @endphp

        <div class="relative group">
            {{-- Glow Effect --}}
            <div class="absolute -inset-1 bg-[var(--brand-primary)] rounded-3xl blur opacity-25 group-hover:opacity-50 transition duration-1000"></div>

            @if($layout === 'horizontal')
                {{-- Horizontal Layout (Image on right) --}}
                <div class="relative bg-white rounded-3xl border border-black/10 overflow-hidden grid md:grid-cols-2 gap-8 items-center p-8 md:p-12">

                    <div class="order-2 md:order-1 space-y-6">
                        {{-- Icon --}}
                        <div class="w-16 h-16 rounded-2xl bg-[var(--brand-muted)] flex items-center justify-center border border-[var(--brand-border)]">
                            @if($item['icon_type'] === 'vscode')
                                <svg viewBox="0 0 24 24" class="w-8 h-8 text-[var(--brand-primary)]" fill="currentColor"><path d="M17.583 2.213L12.4 6.996 8.418 4.01 2 8.553v6.894l6.418 4.543 3.982-2.986 5.183 4.783L22 18.962V5.038l-4.417-2.825zM8.418 16.494L4 13.662v-3.324l4.418 2.832v3.324zm0-4.955L4 8.707l4.418-2.832 3.982 2.99-3.982 2.674zm9.582 6.406l-4.4-4.06v-3.77l4.4 4.06v3.77z"/></svg>
                            @elseif($item['icon_type'] === 'terminal')
                                <svg viewBox="0 0 24 24" class="w-8 h-8 text-[var(--brand-primary)]" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            @else
                                <i data-lucide="box" class="w-8 h-8 text-[var(--brand-primary)]"></i>
                            @endif
                        </div>

                        {{-- Name --}}
                        <h2 class="text-2xl md:text-2xl font-bold text-slate-950">
                            {{ __($item['name_key']) }}
                        </h2>

                        {{-- Description --}}
                        <p class="text-base text-slate-600 leading-relaxed">
                            {{ __($item['desc_key']) }}
                        </p>

                        {{-- CTA Button --}}
                        @if($cta)
                            <div class="pt-4">
                                <x-products.cta-button
                                    :cta="$cta"
                                    :has-paid-plans="false"
                                    class="!w-auto px-6 py-3 font-semibold text-sm"
                                />
                            </div>
                        @endif
                    </div>

                    {{-- Product Image --}}
                    <div class="order-1 md:order-2">
                        <x-products.responsive-image
                            :src="$item['image']"
                            :alt="__($item['name_key'])"
                            img-class="w-full h-auto rounded-xl shadow-2xl border border-black/10 transform group-hover:scale-105 transition duration-700"
                            picture-class=""
                            loading="lazy"
                        />
                    </div>
                </div>

            @else
                {{-- Overlay Layout (Background image with text overlay) --}}
                <div class="relative bg-white rounded-3xl border border-black/10 overflow-hidden text-center md:text-left">
                    {{-- Background Image --}}
                    <div class="absolute inset-0 z-0">
                        <x-products.responsive-image
                            :src="$item['bg_image']"
                            :alt="__($item['name_key'])"
                            img-class="w-full h-full object-cover opacity-20 group-hover:opacity-30 transition duration-700"
                            picture-class="absolute inset-0"
                            loading="lazy"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-white via-white/80 to-transparent"></div>
                    </div>

                    <div class="relative z-10 p-8 md:p-16 flex flex-col md:flex-row items-center gap-10">
                        <div class="space-y-6 max-w-2xl">
                            {{-- Icon --}}
                            <div class="w-16 h-16 rounded-2xl bg-[var(--brand-muted)] flex items-center justify-center border border-[var(--brand-border)] mx-auto md:mx-0">
                                @if($item['icon_type'] === 'terminal')
                                    <svg viewBox="0 0 24 24" class="w-8 h-8 text-[var(--brand-primary)]" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                @else
                                    <i data-lucide="box" class="w-8 h-8 text-[var(--brand-primary)]"></i>
                                @endif
                            </div>

                            {{-- Name --}}
                            <h2 class="text-2xl md:text-2xl font-bold text-slate-950">
                                {{ __($item['name_key']) }}
                            </h2>

                            {{-- Description --}}
                            <p class="text-base md:text-lg text-slate-700 leading-relaxed font-normal">
                                {{ __($item['desc_key']) }}
                            </p>

                            {{-- CTA Button --}}
                            @if($cta)
                                <div class="pt-4 flex flex-col sm:flex-row gap-4 justify-center md:justify-start">
                                    <x-products.cta-button
                                        :cta="$cta"
                                        :has-paid-plans="false"
                                        class="!w-auto px-8 py-3 font-semibold text-sm"
                                    />
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endforeach
</div>
