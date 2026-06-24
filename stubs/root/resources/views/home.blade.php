@extends('layouts.app')

@section('title', __('messages.home.title'))
@section('meta_description', __('messages.home.hero_subtitle'))

@php
    $primaryProduct = $landingProduct ?? ($featuredProducts[0] ?? null);
    $heroTitle = $isSingleProductLanding && $primaryProduct
        ? $primaryProduct['title'].' '.__('messages.home.single_product_suffix')
        : __('messages.home.hero_title');
    $heroSubtitle = $isSingleProductLanding && $primaryProduct
        ? $primaryProduct['description']
        : __('messages.home.hero_subtitle');
    $primaryCtaUrl = $isSingleProductLanding && $primaryProduct
        ? ($primaryProduct['pricing_link'] ?? $primaryProduct['link'])
        : localized_route('products.index');
    $primaryCtaLabel = $isSingleProductLanding && $primaryProduct
        ? ($primaryProduct['pricing_link'] ? __('messages.home.hero_pricing_cta') : __('messages.home.hero_product_cta'))
        : __('messages.home.product_catalog_cta');
@endphp

@section('content')
<div class="home-page bg-[#f7f8f3] text-[#111310]">

<!-- ============================================================
     Landing Hero
     ============================================================ -->
<section class="relative overflow-hidden pt-32 pb-16 md:pt-40 md:pb-24">
    <div class="absolute inset-0 pointer-events-none"
         style="background: radial-gradient(circle at 15% 0%, rgba(184, 121, 91, 0.32), transparent 34%), radial-gradient(circle at 86% 8%, rgba(94, 132, 133, 0.42), transparent 36%), linear-gradient(180deg, #f7f8f3 0%, #eef2ee 100%);">
    </div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <h1 class="text-5xl font-bold leading-[0.98] tracking-normal text-[#0d0f0c] md:text-7xl">
                {{ $heroTitle }}
            </h1>
            <p class="mx-auto mt-7 max-w-2xl text-lg leading-8 text-[#30352f] md:text-xl">
                {{ $heroSubtitle }}
            </p>

            <div class="mt-9 flex flex-wrap items-center justify-center gap-4">
                <a href="{{ $primaryCtaUrl }}"
                   class="inline-flex items-center justify-center gap-2 rounded-full bg-[#a34f1f] px-7 py-3.5 text-base font-bold text-white shadow-sm transition hover:opacity-90">
                    {{ $primaryCtaLabel }}
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
                <a href="{{ localized_route('blog.index') }}"
                   class="inline-flex items-center justify-center rounded-full border border-[#111310] px-7 py-3.5 text-base font-bold text-[#111310] transition hover:bg-[#111310] hover:text-white">
                    {{ __('messages.home.hero_secondary_cta') }}
                </a>
            </div>
        </div>

        <div class="mt-14 grid grid-cols-2 gap-3 md:-mx-8 md:mt-20 md:flex md:min-h-[360px] md:items-end md:justify-center md:gap-5 md:overflow-hidden md:px-8">
            <div class="hidden h-52 w-44 shrink-0 rounded-lg bg-[#0f5132] p-5 text-white shadow-2xl shadow-black/10 sm:block md:h-64 md:w-52">
                <div class="text-4xl font-bold">{{ __('messages.home.proof_paid') }}</div>
                <div class="mt-16 text-lg font-semibold leading-tight">{{ __('messages.home.proof_paid_label') }}</div>
            </div>

            <div class="col-span-2 h-56 w-full shrink-0 overflow-hidden rounded-lg bg-white shadow-2xl shadow-black/10 sm:col-span-1 md:h-80 md:w-64">
                @if($primaryProduct)
                    <img src="{{ asset($primaryProduct['image']) }}" alt="{{ $primaryProduct['title'] }}" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full items-end bg-[#5e8485] p-6 text-white">
                        <div class="text-2xl font-bold">{{ config('app.name') }}</div>
                    </div>
                @endif
            </div>

            <div class="col-span-2 grid shrink-0 gap-3 sm:col-span-1 md:flex md:flex-col md:gap-5">
                <div class="min-h-32 rounded-lg bg-[#5d3f75] p-5 text-white shadow-2xl shadow-black/10 md:h-36 md:w-64">
                    <div class="text-3xl font-bold md:text-4xl">{{ __('messages.home.proof_sessions') }}</div>
                    <div class="mt-6 text-sm font-semibold md:text-base">{{ __('messages.home.proof_sessions_label') }}</div>
                </div>
                <div class="min-h-36 rounded-lg bg-[#244f78] p-5 text-white shadow-2xl shadow-black/10 md:h-52 md:w-64">
                    <div class="text-3xl font-bold md:text-4xl">{{ __('messages.home.proof_content') }}</div>
                    <div class="mt-8 text-sm font-semibold md:mt-16 md:text-base">{{ __('messages.home.proof_content_label') }}</div>
                </div>
            </div>

            <div class="hidden h-56 w-48 shrink-0 rounded-lg bg-[#0b6148] p-5 text-white shadow-2xl shadow-black/10 md:block md:h-72 md:w-60">
                <div class="text-4xl font-bold">{{ __('messages.home.proof_admin') }}</div>
                <div class="mt-28 text-lg font-semibold leading-tight">{{ __('messages.home.proof_admin_label') }}</div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     Product Landing / Catalog Section
     ============================================================ -->
<section class="bg-white py-16 md:py-24">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[0.92fr_1.08fr] lg:px-8">
        <div>
            <h2 class="max-w-xl text-3xl font-bold leading-tight tracking-normal text-[#111310] md:text-5xl">
                {{ __('messages.home.products_title') }}
            </h2>
            <p class="mt-5 max-w-xl text-base leading-8 text-slate-600 md:text-lg">
                {{ __('messages.home.products_subtitle') }}
            </p>
        </div>

        <div class="grid gap-4">
            @forelse($featuredProducts as $product)
                <a href="{{ $product['link'] }}" @if(!empty($product['is_external'])) target="_blank" rel="noopener noreferrer" @endif
                   class="group grid gap-5 rounded-lg border border-slate-200 bg-[#f7f8f3] p-4 transition hover:-translate-y-0.5 hover:border-[#a34f1f]/40 hover:shadow-xl hover:shadow-slate-200/80 sm:grid-cols-[140px_1fr]">
                    <div class="h-32 overflow-hidden rounded-md bg-slate-100 sm:h-full">
                        <img src="{{ asset($product['image']) }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" alt="{{ $product['title'] }}" loading="lazy">
                    </div>
                    <div class="flex flex-col justify-between py-1">
                        <div>
                            <div class="mb-3 flex items-center justify-between gap-3">
                                @if(!empty($product['tag']))
                                    <span class="text-sm font-semibold text-[#a34f1f]">{{ $product['tag'] }}</span>
                                @else
                                    <span class="text-sm font-semibold text-[#a34f1f]">{{ __('messages.nav.products') }}</span>
                                @endif
                                @if($isSingleProductLanding && !empty($product['pricing_link']))
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-700">{{ __('messages.nav.pricing') }}</span>
                                @endif
                            </div>
                            <h3 class="text-2xl font-bold tracking-normal text-[#111310]">{{ $product['title'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $product['description'] }}</p>
                        </div>
                        <div class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-[#111310]">
                            {{ $isSingleProductLanding && !empty($product['pricing_link']) ? __('messages.home.hero_pricing_cta') : __('messages.home.hero_product_cta') }}
                            <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
                        </div>
                    </div>
                </a>
            @empty
                <div class="rounded-lg border border-slate-200 bg-[#f7f8f3] p-8">
                    <h3 class="text-2xl font-bold text-[#111310]">{{ config('app.name') }}</h3>
                    <p class="mt-3 text-slate-600">{{ __('messages.home.hero_subtitle') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</section>

<!-- ============================================================
     Workflow Section
     ============================================================ -->
<section class="bg-[#eef2ee] py-16 md:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl">
            <h2 class="text-3xl font-bold leading-tight tracking-normal text-[#111310] md:text-5xl">
                {{ __('messages.home.workflow_title') }}
            </h2>
            <p class="mt-5 text-base leading-8 text-slate-600 md:text-lg">
                {{ __('messages.home.workflow_subtitle') }}
            </p>
        </div>

        <div class="mt-12 grid gap-5 md:grid-cols-3">
            @foreach(__('messages.home.workflow_steps') as $index => $step)
                <div class="rounded-lg bg-white p-7 shadow-sm">
                    <div class="text-4xl font-bold text-[#a34f1f]">{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</div>
                    <h3 class="mt-10 text-xl font-bold text-[#111310]">{{ $step['title'] }}</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $step['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ============================================================
     Latest from the Blog Section
     ============================================================ -->
@if($latestBlogPosts->isNotEmpty())
<section class="bg-white py-14 md:py-20">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <h2 class="text-3xl font-bold tracking-normal text-[#111310]">
                {{ __('messages.home.blog_title') }}
            </h2>
            <a href="{{ localized_route('blog.index') }}"
               class="inline-flex shrink-0 items-center gap-2 rounded-full border border-[#111310] px-5 py-2.5 text-sm font-bold text-[#111310] transition hover:bg-[#111310] hover:text-white">
                {{ __('messages.home.blog_cta') }}
                <i data-lucide="arrow-right" class="h-4 w-4"></i>
            </a>
        </div>

        <div class="divide-y divide-slate-200 border-y border-slate-200">
            @foreach($latestBlogPosts as $post)
                @php
                    $postTitle = $post->getTranslation('title', app()->getLocale());
                    $postUrl = localized_route('blog.show', ['slug' => $post->slug]);
                @endphp
                <a href="{{ $postUrl }}" class="group flex items-center justify-between gap-6 py-5">
                    <span class="line-clamp-1 text-base font-semibold text-[#111310] transition group-hover:text-[#a34f1f] md:text-lg">
                        {{ $postTitle }}
                    </span>
                    <span class="flex shrink-0 items-center gap-3">
                        <span class="text-xs text-slate-500 md:text-sm">{{ $post->published_at->format('M d, Y') }}</span>
                        <i data-lucide="arrow-up-right" class="h-4 w-4 text-slate-400 transition-all group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-[#a34f1f]"></i>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

</div>
@endsection
