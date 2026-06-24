@extends('layouts.app')

@section('title', __('privacy.title'))
@section('meta_description', __('privacy.subtitle'))

@section('content')
<!-- Hero Section -->
<section class="py-10 bg-gradient-to-br from-slate-900 via-primary-950 to-slate-950 border-b border-white/5 relative overflow-hidden">
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-primary-500/10 blur-[120px] rounded-full pointer-events-none"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-left pt-4 relative z-10">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-5xl font-bold text-white mb-6 leading-tight tracking-tight">
                {{ __('privacy.title') }}
            </h1>
            <p class="text-base md:text-lg text-text-tertiary max-w-2xl leading-relaxed">
                {{ __('privacy.subtitle') }}
            </p>
        </div>
    </div>
</section>

<!-- Privacy Content -->
<section class="section-padding">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="prose prose-base prose-invert max-w-4xl">
                <p class="text-slate-500 mb-12 font-mono text-sm leading-relaxed text-left">{{ __('privacy.last_updated') }}</p>
                
                <div class="space-y-12">
                    @foreach([
                        'intro', 'collection', 'use', 'sharing', 'cookies', 'security',
                        'retention', 'rights', 'international', 'children', 'changes', 'contact'
                    ] as $index => $section)
                    <div class="animate-fade-in text-left">
                        <h2 class="text-lg md:text-2xl font-bold text-white mb-4 tracking-tight flex items-center">
                            <span class="w-8 h-8 rounded-lg bg-primary-500/10 text-primary-400 flex items-center justify-center mr-3 text-sm font-mono">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            {{ __('privacy.sections.' . $section . '_title') }}
                        </h2>
                        <p class="text-slate-400 leading-relaxed text-base md:text-lg">
                            {{ __('privacy.sections.' . $section . '_body') }}
                        </p>
                    </div>
                    @endforeach
                </div>
            
            <!-- Company Information Footer -->
            <div class="mt-16 p-6 bg-slate-900 border border-white/5 rounded-2xl">
                <p class="text-slate-500 text-sm m-0">
                    <strong class="text-text-secondary">{{ config('app.company_name') }}</strong><br>
                    @if(config('app.company_address'))
                        {{ config('app.company_address') }}<br>
                    @endif
                    {{ __('Email') }}: <a href="mailto:{{ config('app.privacy_email') }}" class="text-primary-400 hover:text-primary-300 transition-colors">{{ config('app.privacy_email') }}</a>
                </p>
            </div>
        </div>
    </div>
</section>
@endsection
