{{--
    Preview Section Component (Laravel 12 Anonymous Component)

    Usage: <x-products.preview :section="$section" />

    @props array $section Configuration array with:
        - display: Display mode (single, carousel, gallery)
        - images: Array of images [{src, alt}]
        - max_height: Max height CSS value
        - ctas: Array of CTA buttons
--}}

@props([
    'section',
    'hasPaidPlans' => false,
])

@php
    // Zero-Style Refactoring: Ignore max_height and color from seeder
    $display = $section['display'] ?? 'single';
    $images = $section['images'] ?? [];
    $ctas = $section['ctas'] ?? [];
    $imageCount = count($images);
@endphp

<div {{ $attributes->merge(['class' => 'rounded-3xl border border-black/10 overflow-hidden bg-white shadow-2xl mb-16 relative group aspect-[21/9]']) }}>
    {{-- Gradient Overlay --}}
    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent z-10"></div>

    {{-- Image Display --}}
    @if($display === 'single' && count($images) > 0)
        <x-products.responsive-image
            :src="$images[0]['src']"
            :alt="$images[0]['alt'] ?? ''"
            img-class="absolute inset-0 w-full h-full object-cover object-center opacity-90 group-hover:opacity-100 transition duration-700 transform group-hover:scale-[1.02]"
        />

    @elseif($display === 'carousel' && $imageCount > 1)
        <div x-data="{ current: 0 }" class="relative h-full">
            @foreach($images as $i => $img)
                <div x-show="current === {{ $i }}"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="absolute inset-0 w-full h-full">
                    <x-products.responsive-image
                        :src="$img['src']"
                        :alt="$img['alt'] ?? ''"
                        img-class="w-full h-full object-cover"
                    />
                </div>
            @endforeach
            {{-- Carousel Controls --}}
            <button @click="current = (current - 1 + {{ $imageCount }}) % {{ $imageCount }}"
                    class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-black/50 hover:bg-black/70 flex items-center justify-center text-slate-950">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <button @click="current = (current + 1) % {{ $imageCount }}"
                    class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-black/50 hover:bg-black/70 flex items-center justify-center text-slate-950">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </div>

    @elseif($display === 'gallery' && $imageCount > 1)
        <div class="grid grid-cols-3 gap-2 p-2 h-full">
            @foreach($images as $img)
                <x-products.responsive-image
                            :src="$img['src']"
                            :alt="$img['alt'] ?? ''"
                            img-class="w-full h-full object-cover rounded-lg cursor-pointer hover:scale-105 transition"
                />
            @endforeach
        </div>
    @endif

    {{-- CTA Buttons --}}
    @if(count($ctas) > 0)
        <div class="absolute bottom-0 left-0 right-0 p-8 md:p-12 z-20 text-center flex flex-col md:flex-row justify-center gap-8">
            @foreach($ctas as $cta)
                <x-products.cta-button
                    :cta="$cta"
                    :product-code="null"
                    :has-paid-plans="$hasPaidPlans"
                    class="flex-shrink-0"
                />
            @endforeach
        </div>
    @endif
</div>
