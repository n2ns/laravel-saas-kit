@extends('layouts.app')

@section('title', __('messages.home.title'))
@section('meta_description', __('messages.home.hero_subtitle'))

@section('content')
<div class="home-page bg-bg-body">

<!-- ============================================================
     Hero Section
     ============================================================ -->
<section class="flex items-center justify-center relative overflow-hidden pt-14 pb-16 md:pt-16 md:pb-20">

    <!-- Background layers -->
    <div class="absolute inset-0 z-0 bg-[#030712]">
        <img src="{{ asset('images/hero-bg.webp') }}" class="w-full h-full object-cover object-center opacity-30" alt="SaaS Starter site background">
        <canvas id="saas-starter-hero-stars" class="absolute inset-0 h-full w-full pointer-events-none" aria-hidden="true"></canvas>
        <div class="absolute inset-0 bg-slate-950/55"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-bg-body via-transparent to-bg-body/60"></div>
        <!-- Dot grid texture -->
        <div class="absolute inset-0 bg-dot-grid opacity-[0.035]"></div>
    </div>

    <!-- Ambient glow -->
    <div class="absolute inset-0 z-0 pointer-events-none">
        <div class="absolute top-1/3 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[400px] bg-primary-600/10 blur-[120px] rounded-full"></div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">

        <!-- Headline -->
        <h1 class="text-3xl md:text-5xl lg:text-7xl font-bold text-white mb-6 leading-[1.05] tracking-tighter">
            {{ __('messages.home.hero_title') }}
        </h1>

        <!-- Subtitle -->
        <p class="text-base md:text-lg text-slate-400 mb-12 leading-relaxed max-w-2xl mx-auto">
            {{ __('messages.home.hero_subtitle') }}
        </p>

        <!-- CTA Buttons -->
        <div class="flex flex-wrap items-center justify-center gap-4 mb-20">
            <a href="{{ localized_route('products.index') }}"
               class="btn-brand px-7 py-3.5 text-base rounded-xl">
                {{ __('messages.nav.products') }}
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
            <a href="{{ localized_route('blog.index') }}"
               class="btn-secondary px-7 py-3.5 text-base rounded-xl">
                {{ __('messages.nav.blog') }}
            </a>
        </div>

        <!-- Stats Panel — connected-cell technique -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-white/[0.07] rounded-2xl overflow-hidden border border-white/[0.07]">
            <div class="bg-bg-body px-6 py-5 text-center">
                <div class="text-xl font-bold text-white mb-0.5 tracking-tight">{{ __('messages.home.stats.ai_first') }}</div>
                <div class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold">{{ __('messages.home.stats.architecture') }}</div>
            </div>
            <div class="bg-bg-body px-6 py-5 text-center">
                <div class="text-xl font-bold text-white mb-0.5 tracking-tight">{{ __('messages.home.stats.cloud') }}</div>
                <div class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold">{{ __('messages.home.stats.native') }}</div>
            </div>
            <div class="bg-bg-body px-6 py-5 text-center">
                <div class="text-xl font-bold text-white mb-0.5 tracking-tight">{{ __('messages.home.stats.secure') }}</div>
                <div class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold">{{ __('messages.home.stats.by_design') }}</div>
            </div>
            <div class="bg-bg-body px-6 py-5 text-center">
                <div class="text-xl font-bold text-white mb-0.5 tracking-tight">{{ __('messages.home.stats.global') }}</div>
                <div class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold">{{ __('messages.home.stats.reach') }}</div>
            </div>
        </div>

        <!-- Tech Ecosystem Strip -->
        <div class="mt-20 pt-10 border-t border-white/[0.06]">
            <p class="text-center text-[10px] tracking-[0.35em] font-semibold text-slate-600 uppercase mb-10">{{ __('messages.home.infrastructure_title') }}</p>
            <div class="flex flex-wrap justify-center items-center gap-x-8 gap-y-6 opacity-35 hover:opacity-50 transition-opacity duration-500">
                <div class="group transition-all duration-300 hover:opacity-100 hover:scale-110" title="Google">
                    <img src="{{ asset('images/tech/google.svg') }}" class="h-6 md:h-7 w-auto grayscale brightness-0 invert group-hover:grayscale-0 group-hover:invert-0 group-hover:brightness-100 transition-all duration-300" alt="Google">
                </div>
                <div class="group transition-all duration-300 hover:opacity-100 hover:scale-110" title="AWS">
                    <img src="{{ asset('images/tech/aws.svg') }}" class="h-7 md:h-9 w-auto grayscale brightness-0 invert group-hover:grayscale-0 group-hover:invert-0 group-hover:brightness-100 transition-all duration-300" alt="AWS">
                </div>
                <div class="group transition-all duration-300 hover:opacity-100 hover:scale-110" title="Cloudflare">
                    <img src="{{ asset('images/tech/cloudflare.svg') }}" class="h-6 md:h-7 w-auto grayscale brightness-0 invert group-hover:grayscale-0 group-hover:invert-0 group-hover:brightness-100 transition-all duration-300" alt="Cloudflare">
                </div>
                <div class="group transition-all duration-300 hover:opacity-100 hover:scale-110" title="Anthropic">
                    <img src="{{ asset('images/tech/anthropic.svg') }}" class="h-7 md:h-9 w-auto grayscale brightness-0 invert group-hover:grayscale-0 group-hover:invert-0 group-hover:brightness-100 transition-all duration-300" alt="Anthropic">
                </div>
                <div class="group transition-all duration-300 hover:opacity-100 hover:scale-110" title="OpenAI">
                    <img src="{{ asset('images/tech/openai.svg') }}" class="h-7 md:h-9 w-auto grayscale brightness-0 invert group-hover:grayscale-0 group-hover:invert-0 group-hover:brightness-100 transition-all duration-300" alt="OpenAI">
                </div>
                <div class="group transition-all duration-300 hover:opacity-100 hover:scale-110" title="Docker">
                    <img src="{{ asset('images/tech/docker.svg') }}" class="h-7 md:h-9 w-auto grayscale brightness-0 invert group-hover:grayscale-0 group-hover:invert-0 group-hover:brightness-100 transition-all duration-300" alt="Docker">
                </div>
                <div class="group transition-all duration-300 hover:opacity-100 hover:scale-110" title="Kubernetes">
                    <img src="{{ asset('images/tech/kubernetes.svg') }}" class="h-7 md:h-9 w-auto grayscale brightness-0 invert group-hover:grayscale-0 group-hover:invert-0 group-hover:brightness-100 transition-all duration-300" alt="Kubernetes">
                </div>
                <div class="group transition-all duration-300 hover:opacity-100 hover:scale-110" title="GitHub">
                    <img src="{{ asset('images/tech/github.svg') }}" class="h-7 md:h-9 w-auto grayscale brightness-0 invert group-hover:grayscale-0 group-hover:invert-0 group-hover:brightness-100 transition-all duration-300" alt="GitHub">
                </div>
                <div class="group transition-all duration-300 hover:opacity-100 hover:scale-110" title="Stripe">
                    <img src="{{ asset('images/tech/stripe.svg') }}" class="h-7 md:h-9 w-auto grayscale brightness-0 invert group-hover:grayscale-0 group-hover:invert-0 group-hover:brightness-100 transition-all duration-300" alt="Stripe">
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ============================================================
     Featured Products Section
     ============================================================ -->
<section class="pt-10 pb-16 md:pt-16 md:pb-24 relative overflow-hidden border-t border-white/[0.05]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <p class="section-label mb-3">{{ __('messages.nav.products') }}</p>
            <h2 class="text-3xl md:text-4xl font-bold text-white tracking-tight mb-4">
                {{ __('messages.home.products_title') }}
            </h2>
            <p class="text-slate-400 text-base max-w-2xl mx-auto leading-relaxed">
                {{ __('messages.home.products_subtitle') }}
            </p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($featuredProducts as $product)
                @php $color = $product['color'] ?? 'cyan'; @endphp
                <a href="{{ $product['link'] }}" @if(!empty($product['is_external'])) target="_blank" rel="noopener noreferrer" @endif
                   class="block rounded-2xl ui-card ui-card-lift relative group overflow-hidden h-full">
                    <div class="h-[180px] overflow-hidden relative border-b border-white/[0.06] bg-bg-surface">
                        <img src="{{ asset($product['image']) }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" alt="{{ $product['title'] }}" loading="lazy">
                        <div class="absolute inset-0 bg-gradient-to-t from-bg-body to-transparent opacity-55"></div>
                        @if(!empty($product['badges']))
                            <div class="absolute top-3 right-3 flex flex-wrap justify-end gap-2">
                                @foreach($product['badges'] as $badge)
                                    <span class="inline-flex overflow-hidden rounded-md border border-white/15 shadow-lg shadow-black/20 text-[10px] font-bold leading-none">
                                        <span class="bg-slate-950/90 px-2 py-1.5 text-white">{{ $badge['label'] }}</span>
                                        <span class="bg-lime-500 px-2 py-1.5 text-slate-950">{{ $badge['value'] }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="p-5 flex flex-col relative z-10">
                        <div class="flex justify-between items-start gap-3 mb-3">
                            <div class="w-9 h-9 rounded-lg bg-bg-body border border-white/10 flex items-center justify-center -mt-9 relative z-20 shadow-lg">
                                <i data-lucide="{{ $product['icon'] ?? 'box' }}" class="w-4 h-4 text-{{ $color }}-400"></i>
                            </div>
                            @if(!empty($product['tag']))
                                <span class="px-2 py-0.5 rounded-md bg-{{ $color }}-500/10 border border-{{ $color }}-500/20 text-[11px] font-semibold text-{{ $color }}-400">
                                    {{ $product['tag'] }}
                                </span>
                            @endif
                        </div>
                        <h3 class="text-[15px] font-bold text-white mb-1.5 tracking-tight">
                            {{ $product['title'] }}
                        </h3>
                        <p class="text-slate-400 text-sm leading-relaxed line-clamp-2">
                            {{ $product['description'] }}
                        </p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-12 text-center">
            <a href="{{ localized_route('products.index') }}"
               class="btn-secondary px-8 py-3.5 text-base rounded-xl group">
                {{ __('messages.home.products_more') }}
                <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>
    </div>
</section>


<!-- ============================================================
     Latest from the Blog Section
     ============================================================ -->
@if($latestBlogPosts->isNotEmpty())
<section class="pt-8 pb-10 md:pt-12 md:pb-16 relative overflow-hidden border-t border-white/[0.05]">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
            <div>
                <p class="section-label mb-3">{{ __('messages.nav.blog') }}</p>
                <h2 class="text-2xl md:text-3xl font-bold text-white tracking-tight">
                    {{ __('messages.home.blog_title') }}
                </h2>
            </div>
            <a href="{{ localized_route('blog.index') }}"
               class="btn-ghost px-5 py-2.5 text-sm rounded-lg group inline-flex items-center gap-2 shrink-0">
                {{ __('messages.home.blog_cta') }}
                <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>

        <div class="divide-y divide-white/[0.06] border-y border-white/[0.06]">
            @foreach($latestBlogPosts as $post)
                @php
                    $postTitle = $post->getTranslation('title', app()->getLocale());
                    $postUrl = localized_route('blog.show', ['slug' => $post->slug]);
                @endphp
                <a href="{{ $postUrl }}" class="flex items-center justify-between gap-6 py-4 group">
                    <span class="text-slate-200 text-sm md:text-base font-medium group-hover:text-primary-300 transition-colors line-clamp-1">
                        {{ $postTitle }}
                    </span>
                    <span class="flex items-center gap-3 shrink-0">
                        <span class="text-slate-500 text-xs md:text-sm">{{ $post->published_at->format('M d, Y') }}</span>
                        <i data-lucide="arrow-up-right" class="w-4 h-4 text-slate-600 group-hover:text-primary-300 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-all"></i>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

</div>
@endsection
