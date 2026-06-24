@extends('layouts.app')

@section('title', __('checkout.payment_success'))

@section('content')
<div class="py-10 px-4 flex flex-col items-center relative overflow-hidden">
    <!-- Ambient Background Glows -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full -z-10 opacity-20 dark:opacity-30">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-green-500 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-primary-500 rounded-full blur-[120px]"></div>
    </div>

    <div class="max-w-lg w-full pt-4 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-500">
        <div class="px-8 md:px-10 text-left">
            <!-- Success Icon -->
            <div class="mx-auto ml-0 w-24 h-24 bg-green-100 dark:bg-green-500/10 rounded-full flex items-center justify-center mb-8 relative">
                <div class="absolute inset-0 bg-green-500 rounded-full animate-ping opacity-20"></div>
                <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <!-- Title -->
            <h1 class="text-2xl md:text-4xl font-bold text-slate-900 dark:text-white mb-3 tracking-tight leading-tight font-bold">
                {{ __('checkout.payment_success') }}
            </h1>

            <!-- Subtitle -->
            <p class="text-base md:text-base text-slate-600 dark:text-text-tertiary mb-8 max-w-sm leading-relaxed">
                {{ __('checkout.thank_you_for_subscribing') }}
                @if($product)
                    <span class="text-green-500 font-bold block mt-1 text-base">{{ $product->getLocalized('name') }}</span>
                @endif
            </p>

            <!-- Features Unlocked Card -->
            <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 rounded-2xl p-6 mb-8 text-left transition-all hover:bg-slate-100 dark:hover:bg-slate-800">
                <h3 class="font-bold text-slate-900 dark:text-white mb-4 flex items-center font-bold">
                    <span class="w-1.5 h-6 bg-green-500 rounded-full mr-3"></span>
                    {{ __('checkout.features_unlocked') }}
                </h3>
                <ul class="grid grid-cols-1 gap-3">
                    <li class="flex items-center text-slate-600 dark:text-text-secondary">
                        <div class="w-5 h-5 bg-green-500/10 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <span class="font-normal">{{ __('checkout.unlimited_ai_chat') }}</span>
                    </li>
                    <li class="flex items-center text-slate-600 dark:text-text-secondary">
                        <div class="w-5 h-5 bg-green-500/10 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <span class="font-normal">{{ __('checkout.all_ai_models') }}</span>
                    </li>
                    <li class="flex items-center text-slate-600 dark:text-text-secondary">
                        <div class="w-5 h-5 bg-green-500/10 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <span class="font-normal">{{ __('checkout.priority_support') }}</span>
                    </li>
                </ul>
            </div>

            <!-- CTA Button -->
            <a href="{{ localized_route('dashboard') }}" class="group relative inline-flex items-center justify-center w-full px-8 py-4 bg-green-600 hover:bg-green-500 text-white font-bold rounded-2xl transition-all shadow-lg hover:shadow-green-500/40 hover:-translate-y-0.5 overflow-hidden">
                <span class="relative z-10">{{ __('checkout.go_to_dashboard') }}</span>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:animate-[shimmer_1.5s_infinite]"></div>
            </a>

        </div>
    </div>
</div>
@endsection
