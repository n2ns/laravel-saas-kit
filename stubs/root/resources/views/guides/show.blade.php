@extends('layouts.app')

@php
    $locale = app()->getLocale();
    $contentLocale = $contentLocale ?? $locale;
    $productName = $product->getLocalized('name');
@endphp

@section('title', $guide->getTranslation('title', $contentLocale) . ' - ' . $productName)
@section('meta_description', $guide->getTranslation('excerpt', $contentLocale))
@section('og_type', 'article')

@php($guideOgImage = $guide->thumbnail ? url(Storage::disk('public')->url($guide->thumbnail)) : ($product->image ? asset($product->image) : null))
@if($guideOgImage)
    @section('og_image', $guideOgImage)
    @section('og_image_alt', $guide->getTranslation('title', $contentLocale))
@endif

@section('extra_meta')
    @unless(request()->routeIs('admin.*'))
        @push('structured_data')
            @include('partials.structured-data.json-ld', ['data' => \App\Support\StructuredData::techArticle($guide)])
            @include('partials.structured-data.json-ld', ['data' => \App\Support\StructuredData::breadcrumbList([
                ['name' => $productName, 'url' => localized_route('catalog.show', ['slug' => $productCode])],
                ['name' => 'Guides', 'url' => localized_route('catalog.guides.index', ['productCode' => $productCode])],
                ['name' => $guide->getTranslation('title', $contentLocale), 'url' => url()->current()],
            ])])
        @endpush
    @endunless
@endsection

@section('content')
<div class="relative min-h-screen pt-10">
    <!-- Background Gradient -->
    <div class="absolute top-0 inset-x-0 h-[500px] bg-gradient-to-b from-primary-600/10 to-transparent pointer-events-none"></div>

    <article class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 pt-4 relative z-10">
        <!-- Header -->
        <header class="text-left mb-16 animate-fade-in border-l-2 border-primary-500 pl-8">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-6">
                <a href="{{ localized_route('catalog.show', ['slug' => $productCode]) }}" class="hover:text-slate-300 transition-colors">{{ $productName }}</a>
                <i data-lucide="chevron-right" class="w-3 h-3"></i>
                <a href="{{ localized_route('catalog.guides.index', ['productCode' => $productCode]) }}" class="hover:text-slate-300 transition-colors">Guides</a>
                <i data-lucide="chevron-right" class="w-3 h-3"></i>
                <span class="text-slate-400 truncate max-w-xs">{{ $guide->getTranslation('title', $contentLocale) }}</span>
            </div>

            <div class="flex items-center justify-start gap-4 mb-6">
                <span class="px-3 py-1 bg-primary-500/10 text-primary-400 text-xs font-normal rounded-full border border-primary-500/20 uppercase tracking-widest leading-6">
                    {{ $guide->type }}
                </span>
                <span class="text-text-tertiary h-1 w-1 rounded-full bg-slate-700"></span>
                @if($displayDate = $guide->published_at ?? $guide->updated_at ?? $guide->created_at)
                    <time class="text-text-tertiary font-normal text-xs">
                        {{ $displayDate->format('M d, Y') }}
                    </time>
                @endif
                <span class="text-slate-400 h-1 w-1 rounded-full bg-slate-700"></span>
                <span class="text-slate-400 font-normal text-xs">{{ $guide->getReadingTime($contentLocale) }} min read</span>
            </div>

            <h1 class="text-2xl md:text-5xl font-bold mb-10 text-white tracking-tight leading-tight">
                {{ $guide->getTranslation('title', $contentLocale) }}
            </h1>

            @if($guide->getTranslation('excerpt', $contentLocale))
                <p class="text-base text-slate-400 mb-8 leading-relaxed font-normal italic">
                    {{ $guide->getTranslation('excerpt', $contentLocale) }}
                </p>
            @endif

            @if($guide->thumbnail)
                <div class="rounded-3xl overflow-hidden glass-card aspect-video mb-16">
                    <img src="{{ Storage::disk('public')->url($guide->thumbnail) }}" alt="{{ $guide->getTranslation('title', $contentLocale) }}" class="w-full h-full object-cover">
                </div>
            @endif
        </header>

        <!-- Content -->
        <div class="prose prose-invert max-w-none
            prose-headings:text-white prose-headings:font-bold prose-headings:tracking-tight
            prose-p:text-text-secondary prose-p:leading-relaxed
            prose-a:text-primary-400 prose-a:no-underline hover:prose-a:text-neon-cyan prose-a:transition-colors
            prose-code:text-primary-300 prose-code:bg-slate-900/50 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:before:content-none prose-code:after:content-none
            prose-pre:bg-[#0d1117]! prose-pre:overflow-x-auto prose-code:font-['Fira_Code','JetBrains_Mono',monospace] prose-code:[font-variant-ligatures:none]
            prose-pre:border prose-pre:border-white/5 prose-pre:rounded-2xl prose-pre:p-6 prose-pre:shadow-2xl
            prose-blockquote:border-l-primary-500 prose-blockquote:bg-primary-500/5 prose-blockquote:py-2 prose-blockquote:px-6 prose-blockquote:rounded-r-xl prose-blockquote:italic prose-blockquote:text-text-secondary
            prose-img:rounded-2xl prose-img:shadow-2xl
            prose-strong:text-white prose-strong:font-semibold
            animate-slide-up">
            {!! $htmlContent !!}
        </div>

        <!-- CTA -->
        <div class="mt-16 p-8 rounded-2xl bg-primary-500/5 border border-primary-500/20 text-center">
            <p class="text-white font-semibold mb-2">Try {{ $productName }} for free</p>
            <p class="text-slate-400 text-sm mb-6">Autofill forms in seconds — no manual typing needed.</p>
            <a href="{{ localized_route('catalog.show', ['slug' => $productCode]) }}" class="btn-primary inline-flex items-center gap-2">
                <i data-lucide="chrome" class="w-4 h-4"></i>
                Get {{ $productName }}
            </a>
        </div>

        <!-- Footer / Navigation -->
        <footer class="mt-12 pt-10 border-t border-white/5 flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-500 to-neon-cyan flex items-center justify-center p-0.5">
                    <div class="w-full h-full rounded-full bg-slate-900 flex items-center justify-center overflow-hidden">
                        <img src="{{ asset('favicon-512.png') }}" alt="SaaS Starter" class="w-8 h-8 opacity-90">
                    </div>
                </div>
                <div>
                    <p class="text-white font-semibold">{{ $guide->author->name ?? 'SaaS Starter' }}</p>
                    <p class="text-slate-400 text-sm">{{ $productName }} Team</p>
                </div>
            </div>

            <a href="{{ localized_route('catalog.guides.index', ['productCode' => $productCode]) }}"
               class="px-6 py-3 bg-slate-900/50 hover:bg-slate-800/80 text-white rounded-full border border-white/10 transition-all flex items-center gap-2">
                <i data-lucide="book-open" class="w-4 h-4"></i>
                All Guides
            </a>
        </footer>
    </article>
</div>
@endsection
