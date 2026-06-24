@extends('layouts.app')

@php
    $productName = $product->getLocalized('name');
    $pricingDesc = \Illuminate\Support\Str::limit($productName . ' subscription plans and pricing. ' . $product->getLocalized('subtitle'), 155);
@endphp

@section('title', $productName . ' ' . __('products.pricing.title') . ' | ' . config('app.name'))
@section('meta_description', $pricingDesc)
@section('meta_keywords', $productName . ' pricing, plans, subscription, ' . config('app.name'))
@if($product->image)
    @section('og_image', asset($product->image))
    @section('og_image_alt', $productName)
@endif

@section('content')
<div class="relative flex flex-col pt-10 pb-20 bg-bg-body type-{{ $product->product_type }} product-{{ $product->code }} overflow-hidden min-h-screen">
    <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-[var(--brand-glow)] rounded-full blur-[120px] pointer-events-none opacity-40"></div>
    <div class="absolute bottom-0 right-1/4 w-[500px] h-[500px] bg-[var(--brand-glow)] rounded-full blur-[120px] pointer-events-none opacity-40"></div>
    <div class="absolute inset-0 bg-dot-grid opacity-[0.025] pointer-events-none"></div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 pt-6 w-full">
        <div class="text-center mb-14">
            @php
                $pageTitle = $product->getLocalized('name');
                $pageSubtitle = $product->getLocalized('subtitle');
            @endphp
            <p class="section-label mb-3">{{ __('products.pricing.title') }}</p>
            <h1 class="text-2xl md:text-5xl font-bold text-slate-950 mb-4 leading-tight tracking-tight">
                {{ $pageTitle }}
            </h1>
            @if($pageSubtitle)
                <p class="text-sm md:text-base text-slate-600 max-w-2xl mx-auto leading-relaxed">
                    {{ $pageSubtitle }}
                </p>
            @endif
        </div>

        <x-pricing.plan-grid :product="$product" :plans="$plans" />

        <div class="text-center mt-14">
            <a href="{{ $product->publicUrl() }}" class="btn-ghost text-slate-500">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                {{ __('products.back_to_product') }}
            </a>
        </div>
    </div>
</div>
@endsection
