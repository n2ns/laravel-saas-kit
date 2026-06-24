@extends('layouts.app')

@section('title', $product->getLocalized('name') . ' - ' . __('products.subscription_unavailable'))
@section('robots', 'noindex, follow')

@section('content')
<div class="relative flex flex-col items-center justify-center min-h-[60vh] pt-10 pb-16 bg-bg-body type-{{ $product->product_type }} product-{{ $product->code }} overflow-hidden">
    <!-- Background Effects -->
    <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-[var(--brand-glow)] rounded-full blur-[120px] pointer-events-none opacity-50"></div>
    <div class="absolute bottom-0 right-1/4 w-[500px] h-[500px] bg-[var(--brand-glow)] rounded-full blur-[120px] pointer-events-none opacity-50"></div>

    <div class="container mx-auto px-4 relative z-10 pt-4 text-center">
        <!-- Icon -->
        <div class="mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-amber-500/10 border border-amber-500/30">
                <svg class="w-10 h-10 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>

        <!-- Title -->
        <h1 class="text-2xl md:text-5xl font-bold text-white mb-4 tracking-tight leading-tight font-bold">
            {{ __('products.subscription_unavailable') }}
        </h1>

        <!-- Pause Reason Message -->
        <p class="text-base text-slate-400 max-w-xl mx-auto mb-8">
            {{ __('products.pause_reasons.' . $product->pause_reason) }}
        </p>

        <!-- Product Name Badge -->
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10 mb-8">
            <span class="text-sm text-slate-300">{{ $product->getLocalized('name') }}</span>
        </div>

        <!-- CTA -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ $product->publicUrl() }}" 
               class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold transition-all">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                {{ __('products.back_to_product') }}
            </a>
        </div>
    </div>
</div>
@endsection
