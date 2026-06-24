@extends('layouts.app')

@php
    $productName = $catalogItem->getLocalized('name') ?? $catalogItem->code;
    $seoTitle = $productName . ' Privacy Policy';
    $seoDescription = 'Privacy policy for ' . $productName . '. Learn what data it accesses, how it is used, and the permissions it requires.';

    $privacyUpdated = $privacy['updated'] ?? null;
    $privacySections = $privacy['sections'] ?? [];
@endphp

@section('title', $seoTitle)
@section('meta_description', $seoDescription)

@section('content')
<div class="bg-bg-body min-h-screen">

    <!-- Hero -->
    <section class="relative overflow-hidden pt-12 pb-8 md:pt-14 md:pb-10 border-b border-white/[0.05]">
        <div class="absolute inset-0 z-0">
            <div class="absolute top-1/2 left-1/4 -translate-y-1/2 w-[600px] h-[300px] bg-primary-600/8 blur-[120px] rounded-full pointer-events-none"></div>
        </div>
        <div class="absolute inset-0 bg-dot-grid opacity-[0.03]"></div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <a href="{{ localized_route('catalog.show', ['slug' => $slug]) }}"
               class="inline-flex items-center gap-1.5 text-slate-500 hover:text-slate-300 text-sm mb-6 transition-colors group">
                <i data-lucide="arrow-left" class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform"></i>
                {{ $productName }}
            </a>

            <p class="section-label mb-3">Legal</p>
            <h1 class="text-3xl md:text-5xl font-bold text-white mb-3 tracking-tight leading-tight">
                {{ $productName }} Privacy Policy
            </h1>
            <p class="text-slate-400 text-base leading-relaxed max-w-2xl">
                How {{ $productName }} handles your data and the permissions it uses.
            </p>
        </div>
    </section>

    <!-- Content -->
    <section class="pt-8 pb-12 md:pb-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($privacyUpdated)
                <p class="text-slate-500 font-mono text-sm mb-8">Last updated: {{ $privacyUpdated }}</p>
            @endif

            <div class="space-y-8">
                @foreach($privacySections as $index => $section)
                    <div>
                        <h2 class="text-lg md:text-xl font-bold text-white mb-3 tracking-tight flex items-start gap-3">
                            <span class="w-8 h-8 rounded-lg bg-primary-500/10 text-primary-400 flex items-center justify-center shrink-0 text-sm font-mono mt-0.5">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            {{ $section['title'] ?? '' }}
                        </h2>
                        <div class="ml-11">
                            @if(!empty($section['body']))
                                <p class="text-slate-400 leading-relaxed text-base mb-3">{!! $section['body'] !!}</p>
                            @endif
                            @if(!empty($section['items']))
                                <ul class="space-y-1.5 mb-3">
                                    @foreach($section['items'] as $item)
                                        <li class="text-slate-400 text-base leading-relaxed flex items-start gap-2">
                                            <span class="text-primary-500 mt-1.5 shrink-0">
                                                <i data-lucide="dot" class="w-4 h-4"></i>
                                            </span>
                                            <span>{!! $item !!}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            @if(!empty($section['footer']))
                                <p class="text-slate-400 leading-relaxed text-base">{!! $section['footer'] !!}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Contact -->
            <div class="mt-12 p-6 rounded-2xl border border-white/[0.07] bg-white/[0.02]">
                <p class="text-slate-500 text-sm m-0">
                    <strong class="text-slate-300">{{ config('app.company_name') }}</strong><br>
                    @if(config('app.company_address'))
                        {{ config('app.company_address') }}<br>
                    @endif
                    <a href="mailto:{{ config('app.privacy_email') }}" class="text-primary-400 hover:text-primary-300 transition-colors">{{ config('app.privacy_email') }}</a>
                </p>
            </div>
        </div>
    </section>

</div>
@endsection
