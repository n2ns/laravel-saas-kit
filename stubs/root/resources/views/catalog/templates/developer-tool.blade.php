{{--
    Developer tool catalog detail template.
    Used by catalog_item_details.template_key = developer-tool-detail-v1.
--}}

@extends('layouts.app')

@php
    $profile = $catalogItem->profile;
    $profileImage = $profile?->image;
    $profileLinks = is_array($profile?->links) ? $profile->links : [];
    // Prefer the catalog item's dedicated SEO fields; fall back to name/short description.
    $seoTitle = $catalogItem->getLocalized('seo_title') ?: ($config['hero']['title'] ?? $catalogItem->getLocalized('name'));
    $seoDescription = $catalogItem->getLocalized('seo_description') ?: ($config['hero']['subtitle'] ?? $catalogItem->getLocalized('short_description'));
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
            'data' => \App\Support\StructuredData::catalogItemWebPage($catalogItem),
        ])
    @endpush
@endsection

@section('content')
@php
    $sectionsContent = $sectionsContent ?? [];
    $detailPayload = is_array($catalogItem->detail?->structure_payload) ? $catalogItem->detail->structure_payload : [];
    $metadata = is_array($detailPayload['metadata'] ?? null) ? $detailPayload['metadata'] : [];
    $package = data_get($detailPayload, 'content_payload.package', []);
    $sourceLinks = is_array($profileLinks['sources'] ?? null) ? $profileLinks['sources'] : [];

    $previewImage = $profileImage;
    $heroCtas = is_array($profileLinks['ctas'] ?? null) ? $profileLinks['ctas'] : [];
    $remainingSections = [];
    $heroArticle = '';

    // The data only declares which content blocks exist; this template decides
    // where each one goes. It lifts the preview and the article block into the
    // hero, and renders everything else in the body below.
    foreach ($config['sections'] as $section) {
        $sectionType = $section['type'] ?? null;

        if ($sectionType === 'preview' && $previewImage === $profileImage) {
            $previewImage = $section['images'][0]['src'] ?? $previewImage;
            $heroCtas = $section['ctas'] ?? $heroCtas;
        } elseif ($sectionType === 'text-block' && ($section['data_source'] ?? null) === 'article') {
            $heroArticle = is_string($sectionsContent['article'] ?? null) ? $sectionsContent['article'] : '';
        } else {
            $remainingSections[] = $section;
        }
    }

    $title = $config['hero']['title'] ?? $catalogItem->getLocalized('name');
    $subtitle = $config['hero']['subtitle'] ?? $catalogItem->getLocalized('short_description');
    $productType = $config['hero']['product_type'] ?? $profile?->product_type;
    $releaseStatus = $config['hero']['release_status'] ?? $profile?->release_status;
    $statusDot = ['stable' => 'bg-emerald-400', 'beta' => 'bg-amber-400', 'alpha' => 'bg-sky-400'][$releaseStatus] ?? 'bg-slate-400';
    $tags = $config['hero']['product_tags'] ?? [];

    $heroArticleHtml = $heroArticle !== '' ? app(\Spatie\LaravelMarkdown\MarkdownRenderer::class)->toHtml($heroArticle) : '';
@endphp

<div class="min-h-screen bg-white pb-20">
    <section class="relative overflow-hidden border-b border-black/[0.08]">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(251,146,60,0.22),transparent_34%),radial-gradient(circle_at_80%_10%,rgba(163,79,31,0.10),transparent_28%)] pointer-events-none"></div>

        <div class="relative z-10 mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 py-12 sm:px-6 lg:grid-cols-12 lg:items-start lg:px-8 lg:py-16">
            <div class="lg:col-span-7">
                <div class="mb-5 flex flex-wrap items-center gap-2">
                    @if($productType)
                        <span class="inline-flex items-center gap-2 rounded-md border border-black/10 bg-white px-3 py-1.5 text-sm font-semibold text-slate-800">
                            <span class="h-2 w-2 rounded-full bg-[#a34f1f]"></span>
                            {{ __("products.product_type.{$productType}") }}
                        </span>
                    @endif

                    @if($releaseStatus)
                        <span class="inline-flex items-center gap-2 rounded-md border border-black/10 bg-white px-3 py-1.5 text-sm font-semibold text-slate-800">
                            <span class="h-2 w-2 rounded-full {{ $statusDot }}"></span>
                            {{ __("products.release_status.{$releaseStatus}") }}
                        </span>
                    @endif
                </div>

                <h1 class="max-w-4xl text-2xl font-bold tracking-tight text-slate-950 md:text-5xl">
                    {{ $title }}
                </h1>

                <p class="mt-5 max-w-2xl text-base leading-8 text-slate-700 md:text-lg">
                    {{ $subtitle }}
                </p>

                @if($heroArticleHtml !== '')
                    <div class="mt-5 max-w-2xl text-[15px] leading-7 text-slate-600 prose prose-p:my-0 [&_p+p]:mt-3 prose-a:text-[var(--brand-primary)]">
                        {!! $heroArticleHtml !!}
                    </div>
                @endif

                @if(! empty($tags))
                    <div class="mt-6 flex max-w-2xl flex-wrap gap-2">
                        @foreach($tags as $tag)
                            <span class="rounded-md border border-black/10 bg-white px-3 py-1.5 text-sm font-medium text-slate-700">
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>
                @endif

                @if(count($heroCtas) > 0)
                    <div class="mt-8 flex flex-wrap gap-3">
                        @foreach($heroCtas as $cta)
                            <x-products.cta-button :cta="$cta" :product-code="$catalogItem->code" />
                        @endforeach
                    </div>
                @endif
            </div>

            <aside class="lg:col-span-5">
                <div class="rounded-lg border border-black/[0.10] bg-white p-5 shadow-2xl shadow-orange-900/10">
                    @if($previewImage)
                        <x-products.responsive-image
                            :src="$previewImage"
                            :alt="$title"
                            picture-class="block overflow-hidden rounded-md border border-black/[0.08] bg-white"
                            img-class="aspect-[16/10] max-h-[300px] w-full object-cover object-top"
                        />
                    @else
                        <div class="flex aspect-[16/10] items-center justify-center rounded-md border border-black/[0.08] bg-white">
                            <i data-lucide="package-open" class="h-12 w-12 text-slate-600"></i>
                        </div>
                    @endif

                    <dl class="mt-5 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                        <div class="rounded-md border border-black/[0.08] bg-white p-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Version</dt>
                            <dd class="mt-1 text-slate-800">{{ $package['version'] ?? $profile?->version ?? '-' }}</dd>
                        </div>

                        <div class="rounded-md border border-black/[0.08] bg-white p-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">License</dt>
                            <dd class="mt-1 text-slate-800">{{ $package['license'] ?? '-' }}</dd>
                        </div>
                    </dl>

                    @if(! empty($sourceLinks))
                        <div class="mt-5 border-t border-black/[0.08] pt-5">
                            <h2 class="text-sm font-semibold text-slate-950">Project links</h2>
                            <div class="mt-3 space-y-2">
                                @foreach($sourceLinks as $kind => $source)
                                    @php
                                        $sourceUrl = $source['url'] ?? null;
                                        $sourceRef = $source['ref'] ?? (is_string($source) ? $source : null);
                                        $isUrl = is_string($sourceUrl) && str_starts_with($sourceUrl, 'http');
                                    @endphp

                                    @if($isUrl)
                                        <a href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between gap-3 rounded-md border border-black/[0.08] bg-white px-3 py-2 text-sm text-slate-700 transition hover:border-orange-300 hover:text-slate-950">
                                            <span class="font-semibold uppercase text-xs tracking-wide text-slate-500">{{ $kind }}</span>
                                            <span class="truncate">{{ $sourceRef ?? $sourceUrl }}</span>
                                        </a>
                                    @else
                                        <div class="flex items-center justify-between gap-3 rounded-md border border-black/[0.08] bg-white px-3 py-2 text-sm text-slate-700">
                                            <span class="font-semibold uppercase text-xs tracking-wide text-slate-500">{{ $kind }}</span>
                                            <span class="truncate">{{ $sourceRef ?? '-' }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    </section>

    <main class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        @foreach($remainingSections as $section)
            @switch($section['type'])
                @case('feature-grid')
                    <x-products.feature-grid :section="$section" :content="$sectionsContent" />
                    @break

                @case('text-block')
                    <x-products.text-block :section="$section" :content="$sectionsContent" class="mx-0 max-w-5xl" />
                    @break

                @case('code-block')
                    <x-products.code-block :section="$section" />
                    @break

                @case('preview')
                    <x-products.preview :section="$section" :has-paid-plans="false" />
                    @break
            @endswitch
        @endforeach
    </main>
</div>
@endsection
