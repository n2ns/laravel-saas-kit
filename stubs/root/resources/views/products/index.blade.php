@extends('layouts.app')

@section('title', __('products.seo_title'))
@section('meta_description', __('products.seo_description'))
@section('meta_keywords', 'SaaS products, product catalog, subscriptions, product guides, SaaS Starter')

@section('content')
<div class="product-index-page bg-bg-body min-h-[calc(100vh-80px)]"
     x-data="{ category: (new URLSearchParams(window.location.search)).get('category') || null }"
     x-effect="history.replaceState(null, '', category ? '?category=' + category : window.location.pathname)">

    <!-- Page header -->
    <div class="border-b border-white/[0.06] bg-bg-body">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-10">

            <div class="mb-8">
                <p class="section-label">{{ __('messages.nav.products') }}</p>
                <h1 class="text-3xl md:text-5xl font-bold text-white tracking-tight mb-3">
                    {{ __('products.title') }}
                </h1>
                <p class="text-slate-400 text-sm md:text-base max-w-2xl leading-relaxed">
                    {{ __('products.subtitle') }}
                </p>
            </div>

            <!-- Category Tab Filters -->
            <div class="flex flex-wrap gap-2">
                <button @click="category = null"
                        :class="category === null
                            ? 'bg-white text-slate-950 shadow-sm'
                            : 'bg-white/5 border border-white/10 text-slate-400 hover:text-white hover:bg-white/8'"
                        class="px-4 py-2 rounded-xl text-xs font-semibold transition-all duration-200 cursor-pointer">
                    {{ __('products.category_all') }}
                </button>
                <button @click="category = 'application-product'"
                        :class="category === 'application-product'
                            ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20'
                            : 'bg-white/5 border border-white/10 text-slate-400 hover:text-white hover:bg-white/8'"
                        class="px-4 py-2 rounded-xl text-xs font-semibold transition-all duration-200 cursor-pointer flex items-center gap-1.5">
                    <i data-lucide="rocket" class="w-3 h-3"></i>
                    {{ __('products.category_application_product') }}
                </button>
                <button @click="category = 'developer-tool'"
                        :class="category === 'developer-tool'
                            ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/20'
                            : 'bg-white/5 border border-white/10 text-slate-400 hover:text-white hover:bg-white/8'"
                        class="px-4 py-2 rounded-xl text-xs font-semibold transition-all duration-200 cursor-pointer flex items-center gap-1.5">
                    <i data-lucide="code-2" class="w-3 h-3"></i>
                    {{ __('products.category_developer_tool') }}
                </button>
                <button @click="category = 'concept'"
                        :class="category === 'concept'
                            ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/20'
                            : 'bg-white/5 border border-white/10 text-slate-400 hover:text-white hover:bg-white/8'"
                        class="px-4 py-2 rounded-xl text-xs font-semibold transition-all duration-200 cursor-pointer flex items-center gap-1.5">
                    <i data-lucide="sparkles" class="w-3 h-3"></i>
                    {{ __('products.category_concept') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($products as $product)
                <x-products.index-card :product="$product" />
            @endforeach
        </div>
    </div>
</div>
@endsection
