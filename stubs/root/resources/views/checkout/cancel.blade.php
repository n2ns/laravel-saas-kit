@extends('layouts.app')

@section('title', __('checkout.payment_cancelled'))

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#fff4e8] to-[#fff9f2] py-12 px-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl shadow-orange-900/10 border border-black/10 p-8 text-left">
        <!-- Cancel Icon -->
        <div class="mx-auto ml-0 w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-6">
            <svg class="w-10 h-10 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>

        <!-- Title -->
        <h1 class="text-2xl md:text-4xl font-bold text-text-primary dark:text-slate-950 mb-3 tracking-tight leading-tight">
            {{ __('checkout.payment_cancelled') }}
        </h1>

        <!-- Subtitle -->
        <p class="text-base md:text-base text-slate-600 dark:text-text-secondary mb-8 max-w-sm leading-relaxed">
            {{ __('checkout.payment_cancelled_desc') }}
        </p>

        <!-- Buttons -->
        <div class="space-y-3">
            @if($product)
            <a href="{{ $product->pricingUrl(app()->getLocale()) }}" class="inline-flex items-center justify-center w-full px-6 py-3 bg-[#a34f1f] hover:opacity-90 text-white font-semibold rounded-full transition-colors">
                {{ __('checkout.try_again') }}
            </a>
            @endif
            
            <a href="{{ localized_route('home') }}" class="inline-flex items-center justify-center w-full px-6 py-3 bg-white hover:bg-orange-50 border border-black/10 text-slate-700 font-semibold rounded-full transition-colors">
                {{ __('checkout.return_home') }}
            </a>
        </div>

        <!-- Help Text -->
        <p class="mt-6 text-sm text-slate-500 dark:text-text-secondary">
            {{ __('checkout.need_help') }} 
            <a href="{{ localized_route('support') }}" class="text-[#a34f1f] hover:underline">{{ __('checkout.contact_us') }}</a>
        </p>
    </div>
</div>
@endsection
