@extends('layouts.app')

@section('title', __('checkout.payment_cancelled'))

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-12 px-4">
    <div class="max-w-md w-full bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 text-left">
        <!-- Cancel Icon -->
        <div class="mx-auto ml-0 w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-6">
            <svg class="w-10 h-10 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>

        <!-- Title -->
        <h1 class="text-2xl md:text-4xl font-bold text-text-primary dark:text-white mb-3 tracking-tight leading-tight">
            {{ __('checkout.payment_cancelled') }}
        </h1>

        <!-- Subtitle -->
        <p class="text-base md:text-base text-slate-600 dark:text-text-secondary mb-8 max-w-sm leading-relaxed">
            {{ __('checkout.payment_cancelled_desc') }}
        </p>

        <!-- Buttons -->
        <div class="space-y-3">
            @if($product)
            <a href="{{ $product->pricingUrl(app()->getLocale()) }}" class="inline-flex items-center justify-center w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                {{ __('checkout.try_again') }}
            </a>
            @endif
            
            <a href="{{ localized_route('home') }}" class="inline-flex items-center justify-center w-full px-6 py-3 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-semibold rounded-lg transition-colors">
                {{ __('checkout.return_home') }}
            </a>
        </div>

        <!-- Help Text -->
        <p class="mt-6 text-sm text-slate-500 dark:text-text-secondary">
            {{ __('checkout.need_help') }} 
            <a href="{{ localized_route('support') }}" class="text-blue-600 hover:underline">{{ __('checkout.contact_us') }}</a>
        </p>
    </div>
</div>
@endsection
