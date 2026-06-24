@extends('layouts.app')

@section('title', __('refund.title'))
@section('meta_description', __('refund.subtitle'))

@section('content')
<!-- Hero Section -->
<section class="py-10 bg-gradient-to-b from-[#fff4e8] to-[#fff9f2] border-b border-black/10 relative overflow-hidden">
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-orange-200/40 blur-[120px] rounded-full pointer-events-none"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-left pt-4 relative z-10">
        <div class="max-w-3xl">
            <h1 class="text-2xl md:text-5xl font-bold text-slate-950 mb-6 leading-tight tracking-tight">
                {{ __('refund.title') }}
            </h1>
            <p class="text-base md:text-lg text-text-tertiary max-w-2xl leading-relaxed">
                {{ __('refund.subtitle') }}
            </p>
        </div>
    </div>
</section>

<!-- Refund Content -->
<section class="section-padding">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="prose prose-base max-w-4xl">
            <p class="text-slate-500 mb-12 font-mono text-sm text-left">{{ __('Last Updated') }}: December 2025</p>
            
            <div class="space-y-8">
                @foreach([
                    'intro', 
                    'non_refundable', 
                    'exceptions', 
                    'third_party', 
                    'process', 
                    'changes', 
                    'contact'
                ] as $section)
                <div class="animate-fade-in text-left">
                    <h2 class="text-lg md:text-2xl font-bold text-slate-950 mb-4 tracking-tight">
                        {{ __('refund.sections.' . $section . '_title') }}
                    </h2>
                    <p class="text-slate-600 leading-relaxed text-base md:text-lg m-0">
                        {!! __('refund.sections.' . $section . '_body') !!}
                    </p>
                </div>
                @endforeach
            </div>

            <div class="mt-16 pt-8 border-t border-black/10">
                <p class="text-slate-500 text-sm">
                    {{ __('If you have any questions about this Refund Policy, please contact us at :email.', ['email' => '']) }} <a href="mailto:{{ config('app.support_email') }}" class="text-[#a34f1f] hover:text-slate-950">{{ config('app.support_email') }}</a>.
                </p>
            </div>
        </div>
    </div>
</section>
@endsection
