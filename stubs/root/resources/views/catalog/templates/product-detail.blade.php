{{--
    Commercial catalog detail template.
    Used by catalog_item_details.template_key = product-detail-v1.
--}}

@extends('layouts.app')

@php
    $heroTitle = $config['hero']['title'] ?? __($config['hero']['title_key'] ?? '');
    $heroSubtitle = $config['hero']['subtitle'] ?? __($config['hero']['subtitle_key'] ?? '');
    $profile = $catalogItem->profile;
    $profileImage = $profile?->image;
    $profileFacts = is_array($profile?->facts) ? $profile->facts : [];
    // Prefer the catalog item's dedicated SEO fields; fall back to hero/name.
    $seoTitle = $catalogItem->getLocalized('seo_title') ?: ($heroTitle ?: $catalogItem->getLocalized('name'));
    $seoDescription = $catalogItem->getLocalized('seo_description') ?: ($heroSubtitle ?: $catalogItem->getLocalized('short_description'));
    $seoPayload = is_array($profile?->seo_payload) ? $profile->seo_payload : [];
    $seoImage = asset($seoPayload['og_image'] ?? $profileImage);
    $seoKeywords = collect($catalogItem->getLocalized('tags') ?? [])
        ->push($catalogItem->getLocalized('name'))
        ->push(config('app.name'))
        ->filter()
        ->unique()
        ->implode(', ');
@endphp

@section('title', $seoTitle)
@section('meta_description', $seoDescription)
@section('meta_keywords', $seoKeywords)
@section('og_image', $seoImage)
@section('og_image_alt', $catalogItem->getLocalized('name'))

@section('extra_meta')
    @push('structured_data')
        @include('partials.structured-data.json-ld', [
            'data' => $product
                ? \App\Support\StructuredData::productWebPage($product)
                : \App\Support\StructuredData::catalogItemWebPage($catalogItem),
        ])
    @endpush
@endsection

@section('content')
@php
    $previewImage = null;
    $previewAspect = 'square';
    $heroCtas = [];
    $remainingSections = [];

    foreach($config['sections'] as $section) {
        if ($section['type'] === 'preview' && !$previewImage) {
            $previewImage = $section['images'][0]['src'] ?? null;
            $previewAspect = $section['aspect'] ?? 'square';
            $heroCtas = $section['ctas'] ?? [];
        } else {
            $remainingSections[] = $section;
        }
    }

    $sectionsContent = $sectionsContent ?? [];
    $heroData = $config['hero'];
    $heroData['preview_image'] = $previewImage ?? $profileImage;
    $heroData['preview_aspect'] = $previewAspect;
    $heroData['ctas'] = $heroCtas;
    $heroData['product_type'] = $config['hero']['product_type'] ?? $profile?->product_type;
    $heroData['theme_profile'] = $profile?->theme_profile ?? data_get($profileFacts, 'metadata.theme_profile');
    $heroData['release_status'] = $config['hero']['release_status'] ?? $profile?->release_status;
    $heroData['product_tags'] = $heroData['product_tags'] ?? [];
    $heroData['metadata'] = $heroData['metadata'] ?? data_get($profileFacts, 'metadata', []);
    $heroData['stats'] = $heroData['metadata']['stats'] ?? data_get($profileFacts, 'metadata.stats', []);
    $heroData['description'] = $sectionsContent['description'] ?? null;
    $heroData['has_paid_plans'] = $product?->hasPaidPlans() ?? false;
    $heroData['code'] = $catalogItem->code;

    $privacyUrl = ($hasPrivacyPage ?? false)
        ? localized_route('catalog.privacy', ['slug' => $catalogItem->code])
        : null;
@endphp

<div class="product-detail-page bg-black min-h-screen pb-20">
    <x-products.hero-split :hero="$heroData" />

    <div class="product-content-area max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @foreach($remainingSections as $section)
            @switch($section['type'])
                @case('preview')
                    <x-products.preview :section="$section" :has-paid-plans="$product?->hasPaidPlans() ?? false" />
                    @break

                @case('feature-grid')
                    <x-products.feature-grid :section="$section" :content="$sectionsContent" />
                    @break

                @case('text-block')
                    <x-products.text-block :section="$section" :content="$sectionsContent" />
                    @break

                @case('sub-products')
                    <x-products.sub-products :section="$section" />
                    @break

                @case('code-block')
                    <x-products.code-block :section="$section" />
                    @break
            @endswitch
        @endforeach

        <x-products.guide-list :guides="$guides" :product-code="$product?->code" />

        @if($privacyUrl)
        <div class="mt-12 flex justify-end border-t border-black/[0.08] pt-6">
            <a href="{{ $privacyUrl }}"
               class="group inline-flex items-center gap-2 text-base text-slate-700 hover:text-slate-950 transition-colors">
                <i data-lucide="shield-check" class="w-5 h-5 text-emerald-400/80"></i>
                {{ __('privacy.nav') }}
                <i data-lucide="arrow-right" class="w-4 h-4 -ml-1 opacity-0 group-hover:opacity-100 group-hover:ml-0 transition-all"></i>
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
