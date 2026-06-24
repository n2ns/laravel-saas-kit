@extends('layouts.app')

@php
    $productName = $product->getLocalized('name');
    $title = $productName . ' Guides';
@endphp

@section('title', $title . ' - ' . config('app.name'))
@section('meta_description', 'Step-by-step guides and tutorials for ' . $productName . '.')
@section('meta_keywords', $productName . ' guides, tutorials, how-to, documentation, SaaS Starter')
@if($product->image)
    @section('og_image', asset($product->image))
    @section('og_image_alt', $productName)
@endif

@section('content')
<div class="bg-bg-body min-h-screen">

    <!-- Page header -->
    <div class="border-b border-white/[0.06] relative overflow-hidden">
        <div class="absolute inset-0 bg-dot-grid opacity-[0.025] pointer-events-none"></div>
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary-600/8 rounded-full blur-[100px] pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14 pb-12 relative z-10">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-4">
                <a href="{{ localized_route('catalog.show', ['slug' => $productCode]) }}" class="hover:text-slate-300 transition-colors">{{ $productName }}</a>
                <i data-lucide="chevron-right" class="w-3 h-3"></i>
                <span class="text-slate-400">Guides</span>
            </div>
            <p class="section-label mb-3">Guides</p>
            <h1 class="text-3xl md:text-5xl font-bold text-white tracking-tighter leading-tight mb-4">
                {{ $title }}
            </h1>
            <p class="text-slate-400 text-base max-w-2xl leading-relaxed">
                Step-by-step guides to help you get the most out of {{ $productName }}.
            </p>
        </div>
    </div>

    <!-- Articles -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-14">
            @forelse($guides as $guide)
                <article class="ui-card ui-card-lift rounded-2xl overflow-hidden group flex flex-col h-full">

                    <!-- Thumbnail -->
                    @if($guide->thumbnail)
                        <div class="aspect-video overflow-hidden flex-shrink-0">
                            <img src="{{ Storage::disk('public')->url($guide->thumbnail) }}"
                                 alt="{{ $guide->title }}"
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                        </div>
                    @else
                        <div class="aspect-video bg-white/[0.03] flex items-center justify-center flex-shrink-0 border-b border-white/[0.06]">
                            <i data-lucide="book-open" class="w-10 h-10 text-slate-700"></i>
                        </div>
                    @endif

                    <div class="p-6 flex flex-col flex-grow">
                        <!-- Meta -->
                        <div class="flex items-center gap-3 mb-4">
                            <span class="ui-badge-brand">{{ $guide->type }}</span>
                            <span class="text-slate-500 text-xs">
                                {{ $guide->published_at->format('M d, Y') }}
                            </span>
                        </div>

                        <!-- Title -->
                        <h2 class="text-base font-bold text-white mb-3 group-hover:text-primary-400 transition-colors line-clamp-2 leading-snug tracking-tight">
                            <a href="{{ localized_route('catalog.guides.show', ['productCode' => $productCode, 'slug' => $guide->slug]) }}">
                                {{ $guide->getTranslation('title', app()->getLocale()) }}
                            </a>
                        </h2>

                        <!-- Excerpt -->
                        <p class="text-slate-400 text-sm mb-5 line-clamp-2 leading-relaxed flex-grow">
                            {{ $guide->getTranslation('excerpt', app()->getLocale()) }}
                        </p>

                        <!-- Footer -->
                        <div class="flex items-center justify-between pt-4 border-t border-white/[0.06] mt-auto">
                            <a href="{{ localized_route('catalog.guides.show', ['productCode' => $productCode, 'slug' => $guide->slug]) }}"
                               class="text-xs font-semibold text-primary-400 hover:text-primary-300 flex items-center gap-1.5 transition-colors group/link">
                                Read guide
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover/link:translate-x-0.5 transition-transform"></i>
                            </a>
                            <span class="text-slate-600 text-xs">
                                {{ $guide->getReadingTime() }} min read
                            </span>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full py-24 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-white/[0.04] border border-white/[0.08] flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="book-open" class="w-6 h-6 text-slate-500"></i>
                    </div>
                    <p class="text-slate-400 text-sm">No guides published yet.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="flex justify-center">
            {{ $guides->links() }}
        </div>
    </div>
</div>
@endsection
